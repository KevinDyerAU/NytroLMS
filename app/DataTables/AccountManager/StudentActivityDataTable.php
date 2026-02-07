<?php

namespace App\DataTables\AccountManager;

use App\Helpers\Helper;
use App\Models\Course;
use App\Models\StudentActivity;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StudentActivityDataTable extends DataTable
{
    public static float $totalTime = 0.0;

    public static float $exportTotalTime = 0.0;

    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (StudentActivity $activity) {
            //            $activity_details = json_decode( $activity->getRawOriginal( 'activity_details' ), TRUE );
            $logged = $activity->total_hours ?? 0.0;
            //            if(is_array($activity_details) && isset($activity_details['topic_time'])){
            //                $logged = $activity_details['topic_time'];
            //            }
            self::$exportTotalTime += $logged;
            $user = User::find($activity->user_id);
            $course = Course::where($activity->course_id)->first();
            $logged_total = self::$exportTotalTime;
            $activity_period = '';
            if (!empty($activity->activity_period)) {
                if ($this->period === 'monthly') {
                    $activity_period = Carbon::parse(
                        $activity->activity_period
                    )->format('F, Y');
                } else {
                    $dateRange = $this->getStartAndEndDate(
                        $activity->activity_period
                    );
                    $activity_period = "{$dateRange['start']} &#8594; {$dateRange['end']}";
                }
            }

            return [
                'Student ID' => $activity->user_id,
                'Student' => $user?->name,
                'Activity Period' => $activity_period,
                'Logged' => Helper::formatHoursToHHMM(
                    number_format($logged, 2)
                ),
                'Total Hours Completed' => Helper::formatHoursToHHMM(
                    number_format($logged_total, 2)
                ),
            ];
        };
    }

    public function getStartAndEndDate($period)
    {
        $date = Carbon::parse($period);

        return [
            'start' => $date->startOfWeek()->toFormattedDateString(),
            'end' => $date->endOfWeek()->toFormattedDateString(),
            'period' => $period,
        ];
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('activity_duration', function (
                StudentActivity $activity
            ) {
                if (!empty($activity->activity_period)) {
                    if ($this->period === 'monthly') {
                        return Carbon::parse(
                            $activity->activity_period
                        )->format('F, Y');
                    } else {
                        $dateRange = $this->getStartAndEndDate(
                            $activity->activity_period
                        );

                        return "{$dateRange['start']} &#8594; {$dateRange['end']}";
                    }
                }

                return '';
            })
            ->editColumn('logged', function (StudentActivity $activity) {
                //                $activity_details = json_decode( $activity->getRawOriginal( 'activity_details' ), TRUE );
                $logged = floatval($activity->total_hours) ?? 0.0;
                //                if(is_array($activity_details['topic_time'])){
                //                    $logged = $activity_details['topic_time'];
                //                }
                //                if(is_array($activity_details) && isset($activity_details['topic_time'])){
                //                    $logged = $activity_details['topic_time'];
                //                }
                self::$totalTime += $logged;

                return Helper::formatHoursToHHMM(
                    floatval(number_format($logged, 2))
                );
                //                return number_format( $logged, 2 );
            })
            ->editColumn('total_hours_completed', function (
                StudentActivity $activity
            ) {
                //                $activity_details = json_decode( $activity->getRawOriginal( 'activity_details' ), TRUE );
                $loggedCompleted = self::$totalTime;

                //                $hours = intval( $loggedCompleted );
                //                $minutes = round( ( $loggedCompleted - $hours ) * 60 );
                //                return sprintf( "%02d:%02d", $hours, $minutes ).' '.$hours.'-'.$minutes.' '.$loggedCompleted.' '.number_format( $loggedCompleted, 2 );
                return Helper::formatHoursToHHMM(
                    floatval(number_format($loggedCompleted, 2))
                );
                //                return number_format( self::$totalTime, 2 );
            })
            ->filterColumn('activity_period', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse($searchVal->start);
                    $endDate = Carbon::parse($searchVal->end);
                    if ($this->period === 'monthly') {
                        $query->whereRaw(
                            "months.month_start_date >= '{$startDate->format(
                                'Y-m'
                            )}' AND months.month_start_date <= '{$endDate->format(
                                'Y-m'
                            )}'"
                        );
                    } else {
                        $query->whereRaw(
                            "weeks.week_starting_date >= '{$startDate->format(
                                'Y-m-d'
                            )}' AND weeks.week_starting_date <= '{$endDate->format(
                                'Y-m-d'
                            )}'"
                        );
                    }
                }

                return '';
            })
            ->rawColumns([
                'activity_duration',
                'logged',
                'total_hours_completed',
            ]);
    }

    public function query(StudentActivity $model): QueryBuilder
    {
        $input = [
            'student' => $this->student,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'period' => $this->period,
        ];
        $courses = StudentCourseEnrolment::where(
            'user_id',
            $input['student']
        )->get();

        if (empty($courses)) {
            return $model->newQuery();
        }

        $course_startDate = Carbon::now();
        $course_endDate = Carbon::now();
        $hasActiveCourse = false;
        foreach ($courses as $course) {
            if (
                Carbon::parse(
                    $course->getRawOriginal('course_start_at')
                )->lessThan($course_startDate)
            ) {
                $course_startDate = $course->getRawOriginal('course_start_at');
            }

            //            if ( Carbon::parse( $course->getRawOriginal( 'course_ends_at' ) )->lessThan( Carbon::now() ) ) {
            //                $course_end = $course->getRawOriginal( 'course_ends_at' );
            //            } else
            //            if ( $course->status === 'DELIST' && !$hasActiveCourse ) {
            //                $course_endDate = $course->getRawOriginal( 'updated_at' );
            //            } else {
            //                $course_endDate = Carbon::now();
            //                $hasActiveCourse = TRUE;
            //            }
            //        dump($course->toArray(),$course_start, $course_end);
        }
        $course_start = Carbon::parse($course_startDate)
            ->startOfWeek()
            ->format('Y-m-d');
        $course_end = Carbon::parse($course_endDate)
            ->endOfWeek()
            ->format('Y-m-d');

        if ($this->start_date && $this->end_date) {
            //            if(Carbon::parse( $this->start_date )->greaterThanOrEqualTo(Carbon::parse( $course_startDate ))) {
            $course_start = Carbon::parse($this->start_date)
                ->startOfWeek()
                ->format('Y-m-d');
            //            }
            $course_end = Carbon::parse($this->end_date)
                ->endOfWeek()
                ->format('Y-m-d');
        }
        //        dd( $input, $courses, $course_start, $course_end);
        $select =
            'DATE_SUB(weeks.week_starting_date, INTERVAL (DAYOFWEEK(weeks.week_starting_date) - 2) DAY) AS activity_period, COALESCE(SUM(student_activities.time_spent), 0) AS total_hours';
        $from = "(
                    SELECT DATE_ADD('{$course_start}', INTERVAL (t4*10000 + t3*1000 + t2*100 + t1*10 + t0) WEEK) AS week_starting_date
                    FROM (SELECT 0 t0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS tens
                    CROSS JOIN (SELECT 0 t1 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS units
                    CROSS JOIN (SELECT 0 t2 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS tens_of_units
                    CROSS JOIN (SELECT 0 t3 UNION SELECT 1 UNION SELECT 2) AS hundreds
                    CROSS JOIN (SELECT 0 t4) AS thousands
                    ) weeks";
        $join = [
            'student_activities',
            'DATE_SUB(weeks.week_starting_date, INTERVAL (DAYOFWEEK(weeks.week_starting_date) - 2) DAY)',
            '=',
            'DATE(DATE_SUB(student_activities.activity_on, INTERVAL WEEKDAY(student_activities.activity_on) DAY))',
        ];
        $joinWhere = "student_activities.activity_event = 'TOPIC END' AND student_activities.user_id = {$this->student}";
        $where = "weeks.week_starting_date >= '{$course_start}' AND weeks.week_starting_date <= '{$course_end}'";
        $groupBy = 'activity_period';
        $orderBy = 'activity_period';

        if ($this->period) {
            switch ($this->period) {
                case 'monthly':
                    $course_start = Carbon::parse(
                        $course_startDate
                    )->startOfMonth();
                    $course_end = Carbon::parse($course_endDate)
                        ->endOfMonth()
                        ->format('Y-m');

                    if ($this->start_date && $this->end_date) {
                        $course_start = Carbon::parse(
                            $this->start_date
                        )->startOfMonth();
                        $course_end = Carbon::parse($this->end_date)
                            ->endOfMonth()
                            ->format('Y-m');
                    }

                    $select =
                        'months.month_start_date AS activity_period, COALESCE(SUM(student_activities.time_spent), 0) AS total_hours';
                    $from = "(
                                SELECT DATE_FORMAT(DATE_ADD('{$course_start->format(
                        'Y-m-d'
                    )}', INTERVAL (t4*10000 + t3*1000 + t2*100 + t1*10 + t0) MONTH), '%Y-%m') AS month_start_date
                                FROM (SELECT 0 t0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS tens
                                CROSS JOIN (SELECT 0 t1 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS units
                                CROSS JOIN (SELECT 0 t2 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) AS tens_of_units
                                CROSS JOIN (SELECT 0 t3 UNION SELECT 1 UNION SELECT 2) AS hundreds
                                CROSS JOIN (SELECT 0 t4) AS thousands
                            ) months";
                    $where = "months.month_start_date >= '{$course_start->format(
                        'Y-m'
                    )}' AND months.month_start_date <= '{$course_end}'";
                    $join = [
                        'student_activities',
                        'months.month_start_date',
                        '=',
                        "DATE_FORMAT(student_activities.activity_on, '%Y-%m')",
                    ];
                    $groupBy = 'activity_period';
                    $orderBy = 'activity_period';

                    break;
                case 'weekly':
                default:
                    break;
            }
        }

        return $model
            ->newQuery()
            ->selectRaw($this->student . " as 'user_id', {$select}")
            ->fromRaw($from)
            ->whereRaw($where)
            ->leftJoin($join[0], function (JoinClause $joinQuery) use (
                $join,
                $joinWhere
            ) {
                $joinQuery
                    ->on(DB::raw($join[1]), $join[2], DB::raw($join[3]))
                    ->whereRaw($joinWhere);
            })
            //  ->join('student_course_enrolments', function(JoinClause $clause){
            //         $clause->on('student_activities.course_id','=','student_course_enrolments.id')
            //                 ->where('student_course_enrolments.status','!=','DELIST');
            //     })
            ->groupByRaw($groupBy)
            ->orderByRaw($orderBy);
    }

    public function html(): HtmlBuilder
    {
        $urlData = [];
        $url = url()->current();
        if ($this->request()->has('course')) {
            $urlData['course'] = $this->request()->get('course');
        }
        if ($this->request()->has('period')) {
            $urlData['period'] = $this->request()->get('period');
        }
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }

        return $this->builder()
            ->setTableId('studentactivitydatatable-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->paging(false)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    3, // here is the column number
                    'desc',
                ],
                //                'buttons' => ['csv'],
            ])
            ->buttons(
                Button::make('export')
                    ->text(
                        "<i class='font-small-4 me-50' data-lucide='share'></i>Export"
                    )
                    ->className(
                        'dt-button buttons-collection btn btn-outline-secondary dropdown-toggle me-2'
                    )
                    ->buttons([
                        Button::make('postCsv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                    ])
            );
    }

    protected function getColumns(): array
    {
        return [
            Column::make('user_id'),
            Column::make('activity_period'),
            Column::make('activity_duration')
                ->title('Activity Period')
                ->searchable(false)
                ->orderable(false),
            Column::make('logged')
                ->title('Logged')
                ->searchable(false)
                ->orderable(false),
            Column::make('total_hours_completed')
                ->title('Total Hours Completed')
                ->searchable(false)
                ->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'StudentActivity_' . date('YmdHis');
    }
}
