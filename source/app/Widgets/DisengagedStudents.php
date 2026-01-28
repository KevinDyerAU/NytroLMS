<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\User;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DisengagedStudents extends AbstractWidget
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
        $countQuery = (new User())->newQuery();
        $countQuery = $countQuery->where('users.is_active', 1)
                       ->whereIn('users.id', function ($query) {
                           $query->select('user_id')
                                 ->from('student_course_stats')
                                 ->where('course_status', 'BEHIND SCHEDULE');
                       });
        $currentUser = auth()->user();
        if ($currentUser->isLeader()) {
            $countQuery = $countQuery->isRelatedLeader();
        }
        $count = $countQuery->count();

        $query = (new User())->newQuery();

        $query = $query
            ->select(DB::raw('DATE(DATE_ADD(created_at, INTERVAL '.$timeZoneOffset.' HOUR)) as "date"'), DB::raw('count(id) as "total"'))
            ->where('users.is_active', 1)
            ->whereIn('users.id', function ($query) {
                $query->select('user_id')
                      ->from('student_course_stats')
                      ->where('course_status', 'BEHIND SCHEDULE');
            })
            ->orderBy('date')
            ->groupBy('date')
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1');

        if (auth()->user()->isLeader()) {
            $query = $query->isRelatedLeader();
        }
        $data = $query->get();

        // dd($data, Helper::getTimeZone());
        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.disengaged_students', [
            'config' => $this->config,
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Disengaged Students...';
    }
}
