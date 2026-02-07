<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\DailyEnrolmentReportDataTable;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyEnrolmentReportController extends Controller
{
    public function index(DailyEnrolmentReportDataTable $dataTable, Request $request)
    {
        $this->authorize('view students');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Reports'],
            ['name' => 'Daily Enrolment Report'],
        ];

        // Get registration date from request or default to today
        $registrationDate = $request->get('registration_date');
        if (empty($registrationDate)) {
            $registrationDate = Carbon::today(\App\Helpers\Helper::getTimeZone())->format('Y-m-d');
        }

        // Set registration date on dataTable
        $dataTable->setRegistrationDate($registrationDate);

        return $dataTable->render('content.reports.daily-enrolment-report.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'reportTitle' => 'Daily Enrolment Report',
            'registrationDate' => $registrationDate,
        ]);
    }
}
