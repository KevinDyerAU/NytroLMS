<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\AdminReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\AdminReport;
use App\Models\Company;
use App\Services\DailyRegistrationReportService;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(AdminReportDatatable $dataTable, Request $request)
    {
        $this->authorize('view admin reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Reports'],
        ];

        $companies = auth()->user()->isLeader()
            ? auth()->user()->companies
            : Company::all();

        return $dataTable->with(['course_status' => $request->course_status, 'company' => $request->company, 'registration_date' => $request->registration_date])
            ->render('content.reports.admin-report.index', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'reportTitle' => 'Admin Report',
                'companies' => $companies,
            ]);
    }

    public function show(AdminReport $adminReport)
    {
        $this->authorize('view admin reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Admin Reports', 'link' => route('reports.admins.index')],
            ['name' => 'Details'],
        ];

        return view()->make('content.reports.admin-report.details')
            ->with([
                'report' => $adminReport,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    public function getReport(Request $request, AdminReport $report)
    {
        return response()->json([
            'data' => [
                'raw' => $report,
                'rendered' => $this->renderReport($report),
            ],
            'success' => true, 'status' => 'success',
            'message' => 'Report Fetched',
        ]);
    }

    public function renderReport(AdminReport $report)
    {
        return view()->make('content.reports.admin-report.details')->with(['report' => $report])->render();
    }

    /**
     * Manually generate daily registration report for a specific date (admin only)
     */
    public function generateDailyReport(Request $request, DailyRegistrationReportService $reportService)
    {
        $this->authorize('view admin reports');

        $date = $request->input('date'); // Y-m-d format

        if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2025-10-23)',
            ], 400);
        }

        try {
            $result = $reportService->generateReport($date);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'count' => $result['count'],
                        'filename' => $result['filename'],
                        'report_date' => $result['report_date'],
                        'sharepoint_url' => $result['sharepoint_url'] ?? null,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }
}
