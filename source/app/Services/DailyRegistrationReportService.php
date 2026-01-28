<?php

namespace App\Services;

use App\DataTables\Reports\AdminReportDataTable;
use App\Helpers\Helper;
use App\Models\AdminReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;

class DailyRegistrationReportService
{
    protected string $timezone = 'Australia/Sydney';
    protected string $sharePointPath = 'daily_registrations';
    protected AdminReportDataTable $dataTable;
    protected ?string $lastSharePointUrl = null;
    protected ?string $sharePointFolderPath = null;

    public function __construct()
    {
        $this->dataTable = new AdminReportDataTable();
    }

    /**
     * Generate daily registration report for a specific date
     *
     * @param string|null $date Date in Y-m-d format (defaults to yesterday)
     * @return array ['success' => bool, 'file_path' => string, 'count' => int, 'message' => string]
     */
    public function generateReport(?string $date = null): array {
        try {
            // Default to yesterday in Australia/Sydney timezone
            $reportDate = $date
                ? Carbon::parse($date, $this->timezone)->startOfDay()
                : Carbon::yesterday($this->timezone)->startOfDay();

            $reportDateStr = $reportDate->format('Y-m-d');

            // Get AdminReports using the same query logic as AdminReportDataTable
            $reports = $this->getAdminReports($reportDateStr);
            $count = $reports->count();

            // Append " - Empty" to filename if no registrations
            $filename = $count === 0
                ? "DailyRego_{$reportDateStr} - Empty.csv"
                : "DailyRego_{$reportDateStr}.csv";

            Log::info("Generating daily registration report", [
                'report_date' => $reportDateStr,
                'filename' => $filename,
                'count' => $count,
            ]);

            // Generate CSV using the same format as AdminReportDataTable export
            $csvContent = $this->generateCSV($reports, $count === 0);

            // Save to SharePoint
            $filePath = $this->saveToSharePoint($filename, $csvContent);

            // Get the actual SharePoint URL from the upload
            $sharePointUrl = $this->lastSharePointUrl ?? $this->getSharePointUrl($filename);

            Log::info("Report generated successfully", [
                'file_path' => $filePath,
                'count' => $count,
                'sharepoint_url' => $sharePointUrl,
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'count' => $count,
                'filename' => $filename,
                'report_date' => $reportDateStr,
                'sharepoint_url' => $sharePointUrl,
                'message' => "Report generated successfully with {$count} registration(s)",
            ];

        }
        catch (\Exception $e) {
            Log::error("Failed to generate daily registration report", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'file_path' => null,
                'count' => 0,
                'message' => "Failed to generate report: " . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get AdminReports for a specific date using the same query logic as AdminReportDataTable
     */
    protected function getAdminReports(string $registrationDate): \Illuminate\Support\Collection {
        $return = AdminReport::query()
            ->select('admin_reports.*')
            ->with(['student', 'student.detail', 'course'])
            ->where('admin_reports.student_details', '!=', '')
            ->join('student_course_enrolments', function ($join) {
                $join
                    ->on('admin_reports.course_id', '=', 'student_course_enrolments.course_id')
                    ->on('admin_reports.student_id', '=', 'student_course_enrolments.user_id');
            })
            ->where('student_course_enrolments.status', '!=', 'DELIST')
            ->where('admin_reports.course_id', '!=', config('constants.precourse_quiz_id', 0))
            ->whereNotNull('admin_reports.course_id')
            ->whereNotNull('admin_reports.course_details')
            ->whereDate('student_course_enrolments.registration_date', $registrationDate)
            ->where(function ($query) {
                return $query
                    ->where(function ($qry) {
                        return $qry->where('admin_reports.is_main_course', 1);
                    })
                    ->orWhere(function ($qry) {
                        return $qry
                            ->where('admin_reports.is_main_course', 0)
                            ->where(function ($q) {
                                return $q
                                    ->where(function ($q1) {
                                        return $q1->whereDate(
                                            'admin_reports.student_course_start_date',
                                            '<=',
                                            Carbon::today(Helper::getTimeZone())->toDateString()
                                        );
                                    })
                                    ->orWhere(function ($q2) {
                                        return $q2
                                            ->whereDate(
                                                'admin_reports.student_course_start_date',
                                                '>',
                                                Carbon::today(Helper::getTimeZone())->toDateString()
                                            )
                                            ->whereRaw(
                                                'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(admin_reports.course_details, \'$.is_chargeable\')), \'false\') = \'true\''
                                            );
                                    });
                            });
                    });
            })
            ->groupBy([
                'admin_reports.student_id',
                'admin_reports.course_id',
            ])
            ->get();

        return $return;
    }

    /**
     * Generate CSV content using FastExcel (same as AdminReportDataTable)
     */
    protected function generateCSV(\Illuminate\Support\Collection $reports, bool $isEmpty = false): string {
        // If empty, generate header-only CSV
        if ($isEmpty) {
            return $this->generateEmptyCSV();
        }

        // Set a user for auth context if none exists (for console/tinker)
        // Use the first admin user with permissions
        if (!auth()->check()) {
            $adminUser = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'Admin');
            })->first();

            if ($adminUser) {
                auth()->login($adminUser);
            } else {
                // Fallback: find any user with the permission
                $adminUser = \App\Models\User::whereHas('permissions', function($q) {
                    $q->where('name', 'view reports special columns');
                })->first();

                if ($adminUser) {
                    auth()->login($adminUser);
                } else {
                    // Last resort: use first user
                    $adminUser = \App\Models\User::first();
                    if ($adminUser) {
                        auth()->login($adminUser);
                    }
                }
            }
        }

        // Use the same callback as AdminReportDataTable
        $callback = $this->dataTable->fastExcelCallback();

        // Create a temporary file for FastExcel
        $tempFile = tempnam(sys_get_temp_dir(), 'daily_rego_');

        try {
            // Use FastExcel to generate CSV with the same formatting as admin report
            (new FastExcel($reports))
                ->export($tempFile, function ($report) use ($callback) {
                    return $callback($report);
                });

            // Read the generated CSV content
            $csvContent = file_get_contents($tempFile);

            return $csvContent;

        } catch (\Exception $e) {
            Log::error("Error generating CSV with FastExcel", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Generate empty CSV with header row only
     */
    protected function generateEmptyCSV(): string {
        // Get the CSV headers from the AdminReportDataTable export format
        $headers = [
            'ID',
            'Student ID',
            'Student',
            'Student Email',
            'Student Phone',
            'Purchase Order Number',
            'Employment Service',
            'Preferred Language',
            'Created At',
            'Student Status',
            'Deactivated On',
            'Last Active On',
            'Student Agreement Date',
            'Trainer',
            'Leader',
            'Leader Email',
            'Company Name',
            'Company Address',
            'Company Phone',
            'Company Email',
            'Course',
            'Course Status',
            'Course Completed On',
            'Course Expiry',
            'Start Date',
            'End Date',
            'Deferred',
            'Semester 1 Only',
            'Current Progress',
            'Expected Progress',
            'Course Total Time',
            'Total Time Spent',
            'Time Spent (Last Week)',
            'Total Assignments',
            'Satisfactory Assignments',
            'Not Satisfactory Assignments',
            'Pending Assignments',
            'Certificate Issued',
            'Cert. Issued On',
            'Registration Date',
            'Generate Invoice',
            'Course Locked',
            'Course Version',
            'Study Type',
        ];

        // Check if user has permission to view special columns
        // If not authenticated or doesn't have permission, remove 'Purchase Order Number'
        if (!auth()->check() || auth()->user()->cannot('view reports special columns')) {
            $headers = array_filter($headers, function($header) {
                return $header !== 'Purchase Order Number';
            });
        }

        // Generate CSV header row
        $csvContent = '"' . implode('","', $headers) . '"' . "\n";

        return $csvContent;
    }

    /**
     * Save file to SharePoint (SharePoint is required)
     */
    protected function saveToSharePoint(string $filename, string $content): string {
        echo "Uploading to SharePoint...\n";
        try {
            return $this->uploadToSharePoint($filename, $content);
        }
        catch (\Exception $e) {
            echo "SharePoint upload failed: " . $e->getMessage() . "\n";
            Log::error("SharePoint upload failed", [
                'error' => $e->getMessage(),
                'filename' => $filename,
            ]);

            throw new \Exception("SharePoint upload required but failed: " . $e->getMessage());
        }
    }

    /**
     * Get SharePoint access token
     */
    private function getSharePointAccessToken(): string {
        $tenantId = env('SHAREPOINT_TENANT_ID');
        $clientId = env('SHAREPOINT_CLIENT_ID');
        $clientSecret = env('SHAREPOINT_CLIENT_SECRET');

        if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
            throw new \Exception("SharePoint config missing: tenant_id=" . ($tenantId ? 'set' : 'missing') . ", client_id=" . ($clientId ? 'set' : 'missing') . ", client_secret=" . ($clientSecret ? 'set' : 'missing'));
        }

        $url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        Log::info("Requesting SharePoint access token", [
            'url' => $url,
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
        ]);

        $response = Http::withOptions([
            'verify' => false,
        ])->asForm()->post($url, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            $json = $response->json();

            Log::error("SharePoint token request failed", [
                'status' => $status,
                'body' => $body,
                'json' => $json,
            ]);

            $errorMsg = $json['error_description'] ?? $json['error'] ?? $body ?? 'Unknown error';
            throw new \Exception("Failed to get SharePoint access token (HTTP {$status}): {$errorMsg}");
        }

        return $response->json()['access_token'];
    }

    /**
     * Get SharePoint site ID
     */
    private function getSharePointSiteId(): string {
        $siteUrl = env('SHAREPOINT_SITE_URL');
        $siteName = basename(parse_url($siteUrl, PHP_URL_PATH));

        Log::info("Getting SharePoint site ID", [
            'site_url' => $siteUrl,
            'site_name' => $siteName,
        ]);

        $accessToken = $this->getSharePointAccessToken();

        $endpoints = [
            "https://graph.microsoft.com/v1.0/sites/{$siteUrl}",
            "https://graph.microsoft.com/v1.0/sites/keyinstitute.sharepoint.com:/sites/{$siteName}",
            "https://graph.microsoft.com/v1.0/sites?search={$siteName}",
        ];

        foreach ($endpoints as $endpoint) {
            echo "Trying endpoint: {$endpoint}\n";
            Log::info("Trying endpoint", ['endpoint' => $endpoint]);

            $response = Http::withOptions([
                'verify' => false,
            ])->withToken($accessToken)->get($endpoint);

            echo "Response status: " . $response->status() . "\n";

            if ($response->successful()) {
                $data = $response->json();
                $siteId = $data['id'] ?? $data['value'][0]['id'] ?? null;

                if ($siteId) {
                    echo "Successfully got site ID: {$siteId}\n";
                    Log::info("Successfully got site ID", ['site_id' => $siteId]);
                    return $siteId;
                }
            }
        }

        throw new \Exception("Failed to get SharePoint site ID from any endpoint");
    }

    /**
     * Get SharePoint URL for a file
     */
    public function getSharePointUrl(string $filename): string {
        $siteUrl = rtrim(env('SHAREPOINT_SITE_URL', 'https://keyinstitute.sharepoint.com/sites/Reports'), '/');
        $folderPath = env('SHAREPOINT_FOLDER_PATH', 'Reports');

        // Construct proper SharePoint file URL
        // Format: https://site.sharepoint.com/sites/SiteName/Shared%20Documents/Folder/File.csv
        $folderPathEncoded = str_replace(' ', '%20', $folderPath);
        $filenameEncoded = urlencode($filename);

        return "{$siteUrl}/Shared%20Documents/{$folderPathEncoded}/{$filenameEncoded}";
    }

    /**
     * Upload file to SharePoint using Microsoft Graph API
     */
    private function uploadToSharePoint(string $filename, string $content): string {
        $accessToken = $this->getSharePointAccessToken();
        $siteId = $this->getSharePointSiteId();
        $tempFile = tempnam(sys_get_temp_dir(), 'daily_rego_');
        file_put_contents($tempFile, $content);

        try {
            $folderPath = env('SHAREPOINT_FOLDER_PATH', 'Reports');
            $uploadPath = "{$folderPath}/{$filename}";
            $uploadUrl = "https://graph.microsoft.com/v1.0/sites/{$siteId}/drive/root:/{$uploadPath}:/content";

            Log::info("Uploading to SharePoint", [
                'upload_url' => $uploadUrl,
                'filename' => $filename,
                'upload_path' => $uploadPath,
                'site_id' => $siteId,
            ]);

            $fileContent = file_get_contents($tempFile);
            $response = Http::withOptions([
                'verify' => false,
            ])->withToken($accessToken)
                ->withBody($fileContent, 'application/octet-stream')
                ->put($uploadUrl);

            if ($response->successful()) {
                $responseData = $response->json();
                $sharePointUrl = $responseData['webUrl'] ?? $this->getSharePointUrl($filename);

                // Store the SharePoint URL for later retrieval
                $this->lastSharePointUrl = $sharePointUrl;

                Log::info("File uploaded to SharePoint", [
                    'filename' => $filename,
                    'sharepoint_path' => $uploadPath,
                    'sharepoint_url' => $sharePointUrl,
                ]);

                echo "SharePoint file uploaded successfully!\n";
                echo "File: {$filename}\n";
                echo "Link: {$sharePointUrl}\n";

                return "SharePoint: {$filename}";
            }

            Log::error("SharePoint upload failed", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new \Exception("SharePoint upload failed: " . $response->body());

        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Clean up old reports (keep last 90 days)
     */
    public function cleanupOldReports(): int {
        try {
            $cutoffDate = Carbon::now($this->timezone)->subDays(90);
            $deletedCount = 0;

            // Note: This only cleans up local storage
            // SharePoint files should be cleaned up manually or via SharePoint retention policies
            $files = Storage::disk('local')->files($this->sharePointPath);

            foreach ($files as $file) {
                $filename = basename($file);

                // Extract date from filename (DailyRego_YYYY-MM-DD.csv)
                if (preg_match('/DailyRego_(\d{4}-\d{2}-\d{2})\.csv/', $filename, $matches)) {
                    $fileDate = Carbon::parse($matches[1]);

                    if ($fileDate->lt($cutoffDate)) {
                        Storage::disk('local')->delete($file);
                        $deletedCount++;
                        Log::info("Deleted old report", ['filename' => $filename, 'date' => $fileDate->format('Y-m-d')]);
                    }
                }
            }

            Log::info("Cleanup completed", ['deleted_count' => $deletedCount]);

            return $deletedCount;

        }
        catch (\Exception $e) {
            Log::error("Failed to cleanup old reports", ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
