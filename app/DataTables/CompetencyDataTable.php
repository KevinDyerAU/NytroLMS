<?php

namespace App\DataTables;

use App\Helpers\Helper;
use App\Models\Competency;
use App\Services\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompetencyDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (Competency $competency) {
            $competent_on = $this->getDate($competency, 'competent_on');
            $course_start = $this->getDate($competency, 'course_start');
            $lesson_start = $this->getDate($competency, 'lesson_start');
            $lesson_end = $this->getDate($competency, 'lesson_end');

            $exportCols = [
                'ID' => $competency->id,
                'Student ID' => $competency->user->id,
                'Student' => $competency->user->name,
                'Course' => $competency->course->title,
                'Lesson' => $competency->lesson->title,
                'Is Competent' => $competency->is_competent ? 'Yes' : 'No',
                'Marked On' => $competency->is_competent
                    ? (!empty($competency->notes['marked_at'])
                        ? Carbon::parse(
                            $competency->notes['marked_at']
                        )->toDateString()
                        : Carbon::parse(
                            $competency->updated_at
                        )->toDateString())
                    : '',
                'Marked By' => !empty($competency->notes['added_by'])
                    ? $competency->notes['added_by']['user_name']
                    : '',
                'Competent On' => $competent_on,
                'Course Start' => $course_start,
                'Lesson Start' => $lesson_start,
                'Lesson End' => $lesson_end,
                'Study Type' => $competency->user->study_type ?? '',
                'Assigned trainer' => ($competency->user->trainers && !$competency->user->trainers->isEmpty())
                    ? $competency->user->trainers
                        ->map(function ($trainer) {
                            return $trainer->name;
                        })
                        ->implode(', ')
                    : '',
            ];

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
        return datatables()
            ->eloquent($query)
            ->addColumn('', '')
            ->addColumn('', '')
            ->editColumn('student', function (Competency $competency) {
                if (empty($competency->user)) {
                    return '';
                }
                $user = $competency->user;

                return '<a href="' .
                    route('account_manager.students.show', $user) .
                    '">' .
                    $user->name .
                    '</a>';
            })
            ->editColumn('marked_by', function (Competency $competency) {
                return !empty($competency->notes['added_by'])
                    ? $competency->notes['added_by']['user_name']
                    : '';
            })
            ->editColumn('is_competent', function (Competency $competency) {
                return $competency->is_competent ? 'Yes' : 'No';
            })
            ->editColumn('competent_on', function (Competency $competency) {
                if (empty($competency->competent_on)) {
                    return '';
                }

                return Carbon::parse(
                    $competency->getRawOriginal('competent_on')
                )
                    ->timezone(Helper::getTimeZone())
                    ->format('j F, Y');
            })
            ->editColumn('course_start', function (Competency $competency) {
                if (empty($competency->course_start)) {
                    return '';
                }

                return Carbon::parse(
                    $competency->getRawOriginal('course_start')
                )
                    ->timezone(Helper::getTimeZone())
                    ->format('j F, Y');
            })
            ->editColumn('lesson_start', function (Competency $competency) {
                if (empty($competency->lesson_start)) {
                    return '';
                }
                $lessonStartDate = StudentCourseService::lessonStartDate(
                    $competency->user_id,
                    $competency->lesson_id
                );
                if (!empty($lessonStartDate)) {
                    return $lessonStartDate;
                }

                return Carbon::parse(
                    $competency->getRawOriginal('lesson_start')
                )
                    ->timezone(Helper::getTimeZone())
                    ->format('j F, Y');
            })
            ->editColumn('lesson_end', function (Competency $competency) {
                if (empty($competency->lesson_end)) {
                    return '';
                }
                $lessonEndDate = StudentCourseService::getLessonEndDate(
                    $competency->user_id,
                    $competency->course_id,
                    $competency->lesson_id
                );
                if (!empty($lessonEndDate)) {
                    return StudentCourseService::lessonEndDateBeforeCompetency(
                        $lessonEndDate
                    );
                }

                return '';
            })
            ->editColumn('trainer', function (Competency $competency) {
                if (empty($competency->user) || empty($competency->user->trainers) || $competency->user->trainers->isEmpty()) {
                    return '';
                }

                return $competency->user->trainers
                    ->map(function ($trainer) {
                        return $trainer->name;
                    })
                    ->implode(', ');
            })
            ->addColumn('action', 'competencydatatable.action')
            ->filterColumn('student', function ($query, $keyword) {
                if (!isset($keyword)) {
                    return $query;
                }

                return $query->where('user_id', $keyword);
            })
            ->filterColumn('course', function ($query, $keyword) {
                if (!isset($keyword)) {
                    return $query;
                }

                // 'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
                return $query->where('course_id', $keyword);
            })
            ->filterColumn('competent_on', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse(
                        $searchVal->start
                    )->toDateString();
                    $endDate = Carbon::parse($searchVal->end)->toDateString();

                    return $query
                        ->whereDate('competent_on', '>=', $startDate)
                        ->whereDate('competent_on', '<=', $endDate);
                }

                return '';
            })
            ->rawColumns(['student', 'marked_by', 'trainer']);
    }

    public function query(Competency $model)
    {
        $return = $model
            ->newQuery()
            ->with([
                'lesson',
                'course',
                'user',
                'user.trainers',
                'userDetails',
                'studentCourseEnrolment',
            ]);

        $return = $return->whereHas('lesson', function ($query) {
            $query->where('title', 'not like', 'Study Tips%');
        });

        if ($this->request()->has('course')) {
            $return = $return->where(
                'course_id',
                $this->request()->get('course')
            );
        }

        //        $return = $return->whereNotNull('evidence_id');

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
                ->whereDate('competent_on', '>=', $date_start)
                ->whereDate('competent_on', '<=', $date_end);
        }

        if (
            auth()
                ->user()
                ->isTrainer()
        ) {
            $return = $return->whereHas('user', function ($query) {
                return $query->whereHas('trainers', function (Builder $query) {
                    $query->where('id', '=', auth()->user()->id);
                });
            });
        }

        $return = $return->whereDoesntHave('userDetails', function ($query) {
            $query
                ->where('is_competent', '!=', 1)
                ->whereDate('last_logged_in', '<', '2024-01-01');
        });
        $return = $return
            ->whereDoesntHave('studentCourseEnrolment', function ($query) {
                $query
                    ->whereColumn(
                        'student_course_enrolments.user_id',
                        'competencies.user_id'
                    )
                    ->where(function ($q) {
                        $q->where('cert_issued', '=', 1)->where(
                            'is_competent',
                            '!=',
                            1
                        );
                    });
            })
            //                         ->orWhere('is_competent', '=', 1)  // Include records where is_competent is true
            ->distinct();

        //        Helper::debug([$return->dump(), $return->toSql()],'dd');

        return $return
            ->orderBy('competencies.is_competent', 'ASC')
            ->orderBy('competencies.created_at', 'ASC')
            ->orderBy('competencies.lesson_start', 'ASC')
            ->orderBy('competencies.competent_on', 'DESC');
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
        if ($this->request()->has('course')) {
            $urlData['course'] = $this->request()->get('course');
        }
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }

        return $this->builder()
            ->setTableId('competency-table')
            ->addTableClass(['table-responsive', 'display'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    9, // here is the column number
                    'desc',
                ],
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
                    ->authorized(
                        auth()
                            ->user()
                            ->can('download reports')
                    )
            );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
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
            Column::make('student')
                ->title('Student')
                ->data('student')
                ->name('user.first_name')
                ->orderable(false),
            Column::make('course.title')->title('Course'),
            Column::make('lesson.title')->title('Lesson'),
            Column::make('is_competent'),
            Column::make('marked_by')
                ->title('Marked By')
                ->orderable(false)
                ->searchable(false),
            Column::make('competent_on'),
            Column::make('course_start'),
            Column::make('lesson_start'),
            Column::make('lesson_end'),
            Column::make('user.study_type')->title('Study Type'),
            Column::make('trainer')->title('Assigned trainer')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    public function getDate(Competency $competency, $dateColumn): string
    {
        if (empty($dateColumn)) {
            return '';
        }
        if ($dateColumn === 'lesson_end') {
            $lessonEndDate = StudentCourseService::getLessonEndDate(
                $competency->user_id,
                $competency->course_id,
                $competency->lesson_id
            );
            if (!empty($lessonEndDate)) {
                return StudentCourseService::lessonEndDateBeforeCompetency(
                    $lessonEndDate
                ) ?? '';
            }

            return '';
        }
        if ($dateColumn === 'lesson_start') {
            $lessonStartDate = StudentCourseService::lessonStartDate(
                $competency->user_id,
                $competency->lesson_id
            );
            if (!empty($lessonStartDate)) {
                return $lessonStartDate;
            }

            return Carbon::parse($competency->getRawOriginal('lesson_start'))
                ->timezone(Helper::getTimeZone())
                ->format('j F, Y');
        }

        return !empty($competency->{$dateColumn}) &&
            $competency->getRawOriginal($dateColumn) !== '0000-00-00 00:00:00'
            ? Carbon::parse($competency->getRawOriginal($dateColumn))->format(
                'j F, Y'
            )
            : '';
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Competency_' . date('YmdHis');
    }
}
