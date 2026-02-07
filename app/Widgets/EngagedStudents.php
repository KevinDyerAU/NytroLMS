<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\AdminReport;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EngagedStudents extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $timeZoneOffset = Helper::getTimeZoneOffset();
        $count = (new AdminReport())->newQuery();
        $count = $count->where('course_status', 'ON SCHEDULE');
        $currentUser = auth()->user();
        if ($currentUser->isLeader()) {
            $count = $count->where('leader_id', $currentUser->id)->where('student_status', '!=', 'INACTIVE');
        }
        $count = $count->where('student_status', '!=', 'ENROLLED')->count();

        $query = (new AdminReport())->newQuery();

        $query = $query->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'))
            ->where('course_status', 'ON SCHEDULE')
            ->orderBy('date')
            ->groupBy('date')
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1');

        if ($currentUser->isLeader()) {
            $query = $query->where('leader_id', $currentUser->id)->where('student_status', '!=', 'INACTIVE');
        }
        $data = $query->where('student_status', '!=', 'ENROLLED')->get();

        // dd($data, Helper::getTimeZone());
        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.engaged_students', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Engaged Students...';
    }
}
