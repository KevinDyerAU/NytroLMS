<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\EnrolmentReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Enrolment;
use Illuminate\Http\Request;

class EnrolmentReportController extends Controller
{
    public function index(EnrolmentReportDataTable $dataTable, Request $request)
    {
        $this->authorize('view enrolment reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Reports'],
        ];

        return $dataTable
//            ->with( [ 'course_status' => $request->course_status, 'company' => $request->company ] )
            ->render(
                'content.reports.enrolment-report.index',
                [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'reportTitle' => 'Enrolment Report',
            ]
            );
    }

    public function show($user_id)
    {
        $this->authorize('view enrolment reports');

        $report = Enrolment::selectRaw(
            "id, user_id ,
        JSON_EXTRACT(JSON_ARRAYAGG( JSON_OBJECT(`enrolment_key` , `enrolment_value`)), '$[0].basic') as basic,
        JSON_EXTRACT(JSON_ARRAYAGG( JSON_OBJECT(`enrolment_key` , `enrolment_value`)), '$[1].onboard') as onboard"
        )->where('user_id', $user_id)->groupBy('user_id')->first();

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Enrolment Reports', 'link' => route('reports.enrolments.index')],
            ['name' => 'Details'],
        ];

        return view()->make('content.reports.enrolment-report.details')
            ->with(
                [
                'report' => $report,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]
            );
    }
}
