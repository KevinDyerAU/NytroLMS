<?php

namespace App\Widgets;

use App\Helpers\Helper;
use App\Models\StudentCourseEnrolment;
use Arrilot\Widgets\AbstractWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyEnrolments extends AbstractWidget
{
    /**
     * The number of seconds before each reload.
     *
     * @var int|float
     */
    //    public $reloadTimeout = 30;
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
        $today = Carbon::today(Helper::getTimeZone())->format('Y-m-d');
        $count = StudentCourseEnrolment::join('courses', 'student_course_enrolments.course_id', '=', 'courses.id')
            ->where('student_course_enrolments.status', '!=', 'DELIST')
            ->where(function ($query) use ($today) {
                $query->whereRaw('DATE(CONVERT_TZ(student_course_enrolments.created_at, "+00:00", "+10:00")) = ?', [$today])
                    ->orWhere(function ($subQuery) use ($today) {
                        $subQuery->whereRaw('DATE(CONVERT_TZ(student_course_enrolments.registration_date, "+00:00", "+10:00")) = ?', [$today])
                            ->where(function ($innerQuery) {
                                $innerQuery->where('student_course_enrolments.is_chargeable', 1)
                                    ->orWhere('student_course_enrolments.registered_on_create', 1);
                            });
                    });
            })
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('student_course_enrolments.is_main_course', 1)
                        ->orWhere('student_course_enrolments.is_semester_2', 1);
                })
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('student_course_enrolments.is_main_course', 1)
                            ->where('student_course_enrolments.is_semester_2', 1);
                    });
            })
            ->groupBy('student_course_enrolments.user_id')
            ->select('student_course_enrolments.user_id', 'student_course_enrolments.course_id', DB::raw('count(distinct student_course_enrolments.course_id) as course_count'))
            ->having('course_count', '>', 0)
            ->count();

        $data = StudentCourseEnrolment::join('courses', 'student_course_enrolments.course_id', '=', 'courses.id')
            ->where('student_course_enrolments.status', '!=', 'DELIST')
            ->where(function ($query) {
                $query->whereRaw('DATE(CONVERT_TZ(student_course_enrolments.created_at, "+00:00", "+10:00")) = DATE(CONVERT_TZ(registration_date, "+00:00", "+10:00"))')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereRaw('DATE(CONVERT_TZ(student_course_enrolments.registration_date, "+00:00", "+10:00")) = DATE(CONVERT_TZ(registration_date, "+00:00", "+10:00"))')
                            ->where(function ($innerQuery) {
                                $innerQuery->where('student_course_enrolments.is_chargeable', 1)
                                    ->orWhere('student_course_enrolments.registered_on_create', 1);
                            });
                    });
            })
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('student_course_enrolments.is_main_course', 1)
                        ->orWhere('student_course_enrolments.is_semester_2', 1);
                })
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('student_course_enrolments.is_main_course', 1)
                            ->where('student_course_enrolments.is_semester_2', 1);
                    });
            })
            ->groupBy('date')
            ->select(DB::raw('DATE(CONVERT_TZ(registration_date, "+00:00", "+10:00")) as "date"'), DB::raw('count(distinct student_course_enrolments.user_id) as "total"'))
            ->having('date', '>', Carbon::now()->subMonths(3)->format('Y-m').'-1')
            ->orderBy('date')
            ->get();

        $dataset = $data->mapWithKeys(function ($item, $key) {
            return [
                $key => [
                    'x' => $item['date'],
                    'y' => $item['total'],
                ],
            ];
        })->toJson();

        return view('widgets.daily_enrolments', [
            'config' => $this->config,
            'date' => Carbon::today(Helper::getTimeZone())->toDateString(),
            'data' => [
                'count' => $count,
                'dataset' => $dataset,
            ],
        ]);
    }

    public function placeholder()
    {
        return 'Loading Daily Enrolments...';
    }
}
