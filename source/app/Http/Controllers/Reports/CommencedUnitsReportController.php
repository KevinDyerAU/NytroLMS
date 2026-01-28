<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\CommencedUnitsDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommencedUnitsReportController extends Controller
{
    public function index(CommencedUnitsDataTable $dataTable, Request $request)
    {
        $this->authorize('view competency reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Commenced Units Report'],
        ];

        return $dataTable->with([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])
            ->render('content.reports.commenced-units.index', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }
}
