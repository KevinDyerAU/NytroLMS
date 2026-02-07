<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\WorkPlacementsDataTable;
use App\Http\Controllers\Controller;
use App\Models\WorkPlacement;
use Illuminate\Http\Request;

class WorkPlacementsReport extends Controller
{
    public function index(WorkPlacementsDataTable $dataTable, Request $request)
    {
        $this->authorize('view work placement reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Reports'],
        ];

        return $dataTable->render('content.reports.work-placement-report.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'reportTitle' => 'Work Placements Report',
        ]);
    }

    public function show(WorkPlacement $report)
    {
        $this->authorize('view work placement reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Admin Reports', 'link' => route('reports.work-placement.index')],
            ['name' => 'Details'],
        ];

        return view()->make('content.reports.work-placement-report.details')
            ->with([
                'report' => $report,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    public function getReport(Request $request, WorkPlacement $report)
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

    public function renderReport(WorkPlacement $report)
    {
        return view()->make('content.reports.work-placement-report.details')->with(['report' => $report])->render();
    }
}
