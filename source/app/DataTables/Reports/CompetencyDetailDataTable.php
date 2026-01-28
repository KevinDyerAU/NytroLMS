<?php

namespace App\DataTables\Reports;

use App\Helpers\Helper;
use App\Models\Lesson;
use App\Models\StudentCourseEnrolment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompetencyDetailDataTable extends DataTable
{
    protected bool $fastExcel = true;

    protected $columns = [];

    protected $data = [];

    public function fastExcelCallback(): \Closure
    {
        return function (StudentCourseEnrolment $model) {
            if (empty($this->columns['lessons'])) {
                $this->lessons($this->course_id);
            }
            $this->getData($this->query($model), false);
            $student_status = $model->student->status ?? '';
            $student_active = $model->student->is_active;
            if (intval($student_active) === 0) {
                $student_status = 'INACTIVE';
            } else {
                if (\Str::lower($student_status) === 'enrolled') {
                    $student_status = 'REGISTERED';
                } elseif (\Str::lower($student_status) === 'onboarded') {
                    $student_status = 'ACTIVE';
                } elseif (
                    empty($model->student->detail->onboard_at) ||
                    empty($model->student->detail->last_logged_in)
                ) {
                    $student_status = 'ENROLLED';
                } else {
                    $student_status = 'ACTIVE';
                }
            }
            $exportCols = [
                'ID' => $model->id,
                'Student ID' => $model->user_id,
                'Student' => $model->student->name ?? '',
                'Status' => \Str::title(str_replace('_', ' ', $student_status)),
                'Company' => $model->student->companies
                    ->map(function ($company) {
                        return $company->name;
                    })
                    ->implode(', '),
            ];

            foreach ($this->columns['lessons'] as $key => $data) {
                $lesson_id = $data['id'];
                $user_id = $model->user_id;
                $exportCols[$data['title']] =
                    $this->data['competencies'][$user_id][$lesson_id]['raw'] ??
                    '';
            }

            return $exportCols;
        };
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        if (empty($this->columns['lessons'])) {
            $this->lessons($this->course_id);
        }
        if (empty($this->data)) {
            $this->getData($query);
        }
        $datatable = datatables()
            ->eloquent($query)
            ->addColumn('', '')
            ->addColumn('', '')
            //            ->addColumn( 'action', 'reports/competencydetaildatatable.action' )
            //            ->filterColumn( 'course', function ( $query, $keyword ) {
            //                if ( !isset( $keyword ) ) {
            //                    return $query;
            //                }
            //
            //                //'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
            //                return $query->where( 'course_id', $keyword );
            //            } )
            ->editColumn('student', function (StudentCourseEnrolment $model) {
                //                dd($model, $model->student, $model->student->name);
                if (empty($model->user_id) || empty($model->student)) {
                    return '';
                }

                return '<a href="' .
                    route('account_manager.students.show', $model->user_id) .
                    '">' .
                    $model->student->name .
                    '</a>';
            })
            ->editColumn('status', function (StudentCourseEnrolment $model) {
                $student_status = $model->student->status ?? '';
                $student_active = $model->student->is_active;
                if (intval($student_active) === 0) {
                    $student_status = 'INACTIVE';
                } else {
                    if (\Str::lower($student_status) === 'enrolled') {
                        $student_status = 'REGISTERED';
                    } elseif (\Str::lower($student_status) === 'onboarded') {
                        $student_status = 'ACTIVE';
                    } elseif (
                        empty($model->student->detail->onboard_at) ||
                        empty($model->student->detail->last_logged_in)
                    ) {
                        $student_status = 'ENROLLED';
                    } else {
                        $student_status = 'ACTIVE';
                    }
                }
                $color = config(
                    'constants.status.color.' . $student_status,
                    'primary'
                );

                return '<span class="text-' .
                    $color .
                    '">' .
                    \Str::title(str_replace('_', ' ', $student_status)) .
                    '</span>';
            })
            ->editColumn('company', function (StudentCourseEnrolment $model) {
                //                dd($model->student, $model->student->companies);
                if (
                    empty($model->user_id) ||
                    empty($model->student->companies)
                ) {
                    return '';
                }

                return $model->student->companies
                    ->map(function ($company) {
                        return '<a href="' .
                            route(
                                'account_manager.companies.show',
                                $company->id
                            ) .
                            '">' .
                            $company->name .
                            '</a>';
                    })
                    ->implode(', ');
            });

        foreach ($this->columns['lessons'] as $key => $data) {
            //            dd( $key, $data );
            $datatable->editColumn($key, function (StudentCourseEnrolment $model) use ($data) {
                $lesson_id = $data['id'];
                $user_id = $model->user_id;

                return $this->data['competencies'][$user_id][$lesson_id][
                    'html'
                ] ?? '';
            });
        }

        $rawCols = array_merge(
            ['student', 'company', 'status'],
            array_keys($this->columns['lessons'])
        );

        return $datatable
            ->filterColumn('course_start', function ($query, $keyword) {
                if (!isset($keyword)) {
                    return $query;
                }

                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse(
                        $searchVal->start
                    )->toDateString();
                    $endDate = Carbon::parse($searchVal->end)->toDateString();

                    return $query
                        ->whereDate('course_start_at', '>=', $startDate)
                        ->whereDate('course_start_at', '<=', $endDate);
                }

                return '';
            })
            ->filterColumn('student', function ($query, $keyword) {
                if (!isset($keyword)) {
                    return $query;
                }
                $query->whereHas('student', function ($q) use ($keyword) {
                    $q->where(function ($subQ) use ($keyword) {
                        $subQ
                            ->where('users.first_name', 'like', "%{$keyword}%")
                            ->orWhere(
                                'users.last_name',
                                'like',
                                "%{$keyword}%"
                            );
                    });
                });
            })
            ->filterColumn('company', function ($query, $keyword) {
                if (!isset($keyword)) {
                    return $query;
                }
                $query->whereHas('student.companies', function ($q) use ($keyword) {
                    $q->where('companies.name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns($rawCols);
    }

    protected function getData($query, $applyLimit = true)
    {
        if (empty($this->columns['lessons'])) {
            $this->lessons($this->course_id);
        }
        $this->data['competencies'] = [];
        $length = intval($this->request->get('length'));
        $start = intval($this->request->get('start'));

        if ($applyLimit) {
            $studentCourse = $query
                ->clone()
                ->offset($start)
                ->limit($length)
                ->get();
        } else {
            $studentCourse = $query->clone()->get();
        }

        //        dd($length, $start, $studentCourse->count(), $studentCourse->toSql());
        //        dd($studentCourse->toSql());
        if (empty($studentCourse)) {
            return [];
        }
        $lessons = \Arr::pluck($this->columns['lessons'], 'id');
        foreach ($studentCourse as $row) {
            $lessonCompetency = DB::table('competencies')
                //                                            ->rightJoin('lessons', 'competencies.lesson_id','=','lessons.id')
                ->rightJoin('lessons', function ($join) use ($row) {
                    $join
                        ->on('competencies.lesson_id', '=', 'lessons.id')
                        ->where('competencies.user_id', $row->user_id);
                })
                ->leftJoin('student_activities', function ($join) use ($row) {
                    $join
                        ->on(
                            'lessons.id',
                            '=',
                            'student_activities.actionable_id'
                        )
                        ->where('student_activities.user_id', $row->user_id)
                        ->where(function ($query) {
                            $query
                                ->where(
                                    'student_activities.activity_event',
                                    'LESSON START'
                                )
                                ->orWhere(
                                    'student_activities.activity_event',
                                    'LESSON MARKED'
                                );
                        })
                        ->where(
                            'student_activities.actionable_type',
                            \App\Models\Lesson::class
                        );
                })
                ->whereIn('lessons.id', $lessons)
                //                                            ->where('competencies.user_id', $row->user_id)
                ->select(
                    'competencies.*',
                    'lessons.title',
                    'lessons.id as lesson_id',
                    'student_activities.actionable_id',
                    'student_activities.activity_on'
                )
                ->orderBy('lessons.order')
                ->get();

            foreach ($lessonCompetency as $competency) {
                $output = '';
                $raw = '';
                if (!empty($competency->is_competent)) {
                    $output .=
                        "<span class='text-success fw-bold'>COMPETENT</span>";
                    $raw .= 'COMPETENT';
                    if (!empty($competency->lesson_start)) {
                        $output .=
                            '<br><small>( ' .
                            $competency->lesson_start .
                            ' - ' .
                            $competency->lesson_end .
                            '  )</small>';
                        $raw .=
                            '( ' .
                            $competency->lesson_start .
                            ' - ' .
                            $competency->lesson_end .
                            '  )';
                    }
                } elseif (!empty($competency->activity_on)) {
                    $output .=
                        "<span class='text-secondary fw-normal'>COMMENCE</span>";
                    $output .=
                        '<br><small>( ' .
                        Carbon::parse($competency->activity_on)
                            ->timezone(Helper::getTimeZone())
                            ->toDateString() .
                        ' )</small>';
                    $raw .= 'COMMENCE';
                    $raw .=
                        '( ' .
                        Carbon::parse($competency->activity_on)
                            ->timezone(Helper::getTimeZone())
                            ->toDateString() .
                        ' )';
                }

                $this->data['competencies'][$row->user_id][
                    $competency->lesson_id
                ]['html'] = $output;
                $this->data['competencies'][$row->user_id][
                    $competency->lesson_id
                ]['raw'] = $raw;
            }
        }

        return $this->data;
    }

    public function query(StudentCourseEnrolment $model)
    {
        $return = $model
            ->newQuery()
            ->with(['student', 'student.companies', 'course']);

        //        if($this->request()->has('course')){
        //            $return = $return->where('course_id', $this->request()->get( 'course' ));
        //        }
        $return = $return
            ->where('status', '!=', 'DELIST')
            ->whereHas('course')
            ->whereHas('student');

        $return = $return->where('course_id', intval($this->course_id));

        if (
            $this->request()->has('start_date') &&
            $this->request()->has('end_date')
        ) {
            $date_start = Carbon::parse(
                $this->request()->get('start_date')
            )->toDateString();
            $date_end = Carbon::parse(
                $this->request()->get('end_date')
            )->toDateString();
            $return = $return
                ->whereDate('course_start_at', '>=', $date_start)
                ->whereDate('course_start_at', '<=', $date_end);
        }

        //        dd($return->toSql());
        return $return->orderBy('course_start_at', 'DESC');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $urlData = [];
        $url = url()->current();
        //        if ( $this->request()->has( "course" ) ) {
        //            $urlData[ 'course' ] = $this->request()->get( 'course' );
        //        }
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }

        return $this->builder()
            ->setTableId('competencyreportdatatable-table')
            ->addTableClass(['table-responsive', 'display'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
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
                        //                            Button::make('excel')
                        //                                ->text("<i data-lucide='file'></i>Excel")
                        //                                ->className('dropdown-item')
                        //                                ->exportOptions(['modifier' => ['selected' => null], 'columns' => ":visible"])
                    ])
                    ->authorized(
                        auth()
                            ->user()
                            ->can('download reports')
                    )
            );
    }

    protected function lessons($course_id)
    {
        $lessons = Lesson::where('course_id', $course_id)->get();
        $details = [];
        $output = [
            Column::computed('', '')
                ->exportable(false)
                ->printable(false)
                ->responsivePriority(2)
                ->addClass('control'),
            Column::computed('', '')
                ->responsivePriority(3)
                ->addClass('dt-checkboxes-cell'),
            Column::make('id'),
            Column::make('id')
                ->orderable(false)
                ->visible(false),
            Column::make('student'),
            Column::make('status'),
            Column::make('company'),
        ];
        foreach ($lessons as $lesson) {
            $details[$lesson->slug] = $lesson->toArray();
            $output[] = Column::make($lesson->slug)
                ->title($lesson->title)
                ->searchable(false)
                ->orderable(false);
        }
        $this->columns['make'] = $output;
        $this->columns['lessons'] = $details;
    }

    protected function getColumns()
    {
        $this->lessons($this->course_id);

        return $this->columns['make'];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CompetencyDetail_' . date('YmdHis');
    }
}
