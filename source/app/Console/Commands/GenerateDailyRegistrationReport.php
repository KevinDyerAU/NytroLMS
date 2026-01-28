<?php

namespace App\Console\Commands;

use App\Notifications\DailyRegistrationReportFailure;
use App\Notifications\DailyRegistrationReportSuccess;
use App\Services\DailyRegistrationReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class GenerateDailyRegistrationReport extends Command
{
    protected $signature = 'report:daily-registrations {--date= : Generate report for specific date (Y-m-d format)}';

    protected $description = 'Generate daily registration report and send notifications';

    protected DailyRegistrationReportService $reportService;

    protected array $recipients = [
        'cain@keycompany.com.au',
        'danielle@keycompany.com.au',
        'luke@keycompany.com.au',
        //'mary@keycompany.com.au'
    ];

    protected array $failure_recipients = [
        'cain@keycompany.com.au',
        'danielle@keycompany.com.au',
        'luke@keycompany.com.au',
        'nigel@keycompany.com.au',
        //'mary@keycompany.com.au'
    ];

    public function __construct(DailyRegistrationReportService $reportService) {
        parent::__construct();
        $this->reportService = $reportService;
    }

    public function handle(): int {
        $date = $this->option('date');

        if ($date) {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $this->error('Invalid date format. Please use Y-m-d format (e.g., 2025-10-23)');
                return Command::FAILURE;
            }
            $this->info("Generating report for date: {$date}");
        } else {
            $this->info("Generating report for yesterday...");
        }

        // Generate report
        $result = $this->reportService->generateReport($date);

        if ($result['success']) {
            $this->info("✓ Report generated successfully");
            $this->info("  File: {$result['filename']}");
            $this->info("  Path: {$result['file_path']}");
            $this->info("  Count: {$result['count']} registration(s)");

            // Send success notification only if report is not empty
            if ($result['count'] > 0) {
                $this->sendSuccessNotification($result);
            } else {
                $this->info("  Report is empty, skipping email notification");
            }

            // Cleanup old reports
            $this->info("Cleaning up old reports...");
            $deletedCount = $this->reportService->cleanupOldReports();
            $this->info("  Deleted {$deletedCount} old report(s)");

            return Command::SUCCESS;
        } else {
            $this->error("✗ Report generation failed");
            $this->error("  Error: {$result['message']}");

            // Send failure notification
            $this->sendFailureNotification($result);

            return Command::FAILURE;
        }
    }

    protected function sendSuccessNotification(array $result): void {
        try {
            $reportData = [
                'count' => $result['count'],
                'report_date' => $result['report_date'],
                'filename' => $result['filename'],
                'file_url' => $result['sharepoint_url'] ?? $this->reportService->getSharePointUrl($result['filename']),
            ];

            Notification::route('mail', $this->recipients)
                ->notify(new DailyRegistrationReportSuccess($reportData));

            $this->info("✓ Success notification sent to " . count($this->recipients) . " recipient(s)");
        }
        catch (\Exception $e) {
            $this->warn("Failed to send success notification: " . $e->getMessage());
        }
    }

    protected function sendFailureNotification(array $result): void {
        try {
            $errorData = [
                'report_date' => $result['report_date'] ?? date('Y-m-d'),
                'error' => $result['error'] ?? 'Unknown error',
                'message' => $result['message'] ?? 'Failed to generate report'
            ];

            Notification::route('mail', $this->failure_recipients)
                ->notify(new DailyRegistrationReportFailure($errorData));

            $this->info("✓ Failure notification sent to " . count($this->failure_recipients) . " recipient(s)");
        }
        catch (\Exception $e) {
            $this->warn("Failed to send failure notification: " . $e->getMessage());
        }
    }
}
