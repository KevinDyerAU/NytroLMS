<?php

namespace App\DataTables;

use App\Helpers\Helper;
use App\Models\Evaluation;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AssessmentDatatable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (QuizAttempt $attempt) {
            $trainers = $attempt->user?->trainers?->map(function ($trainer) {
                return $trainer->name;
            })->implode(', ');
            $status = $attempt->status;
            if (in_array($attempt->status, ['FAIL', 'RETURNED'])) {
                $status = 'NOT SATISFACTORY';
            }
            $assessed_by = 'Not assessed yet.';
            $activity_time = 'Not assessed yet.';
            $evaluation = $attempt->evaluation()?->latest()->first();
            if (!empty($evaluation)) {
                $activity_time = Carbon::parse(
                    $evaluation->getRawOriginal('updated_at')
                )
                    ->timezone(Helper::getTimeZone())
                    ->format('j F, Y g:i A');

                if (
                    $attempt->system_result === 'EVALUATED' ||
                    $attempt->system_result === 'MARKED'
                ) {
                    $assessed_by = 'Auto Competent';
                } else {
                    $assessed_by_user = User::find($evaluation->evaluator_id);
                    $assessed_by = $assessed_by_user?->name;
                }
            } elseif ($attempt->system_result === 'EVALUATED') {
                // 'INPROGRESS','COMPLETED','EVALUATED'
                $assessed_by = 'Auto Competent';
                $activity_time = 'Auto Competent';
            }

            return [
                'ID' => $attempt->id,
                'Student ID' => $attempt->user_id,
                'Student Name' => $attempt->user?->name,
                'Student Email' => $attempt->user?->email,
                'Quiz' => $attempt->quiz->title,
                'Course' => $attempt->course->title,
                'Status' => $status,
                'Submitted At' => $attempt->submitted_at,
                'Assigned Trainer' => $trainers,
                'Assessed By' => $assessed_by,
                'Assessed On' => $activity_time,
                'Study Type' => $attempt->user?->study_type ?? '',
            ];
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
            ->editColumn('student', function (QuizAttempt $attempt) {
                if (empty($attempt->user)) {
                    return '';
                }
                $user = $attempt->user;

                return '<a href="' .
                    route('account_manager.students.show', $user) .
                    '">' .
                    $user->name .
                    '</a>';
            })
            ->editColumn('trainer', function (QuizAttempt $attempt) {
                if (empty($attempt->user)) {
                    return '';
                }
                if (
                    auth()
                        ->user()
                        ->hasRole(['Leader'])
                ) {
                    return $attempt->user?->trainers?->map(function ($trainer) {
                        return '<span>' . $trainer->name . '</span>';
                    })->implode(', ');
                }

                return $attempt->user?->trainers?->map(function ($trainer) {
                    return '<a href="' .
                        route('account_manager.trainers.show', $trainer->id) .
                        '">' .
                        $trainer->name .
                        '</a>';
                })->implode(', ');
            })
            ->editColumn('status', function (QuizAttempt $attempt) {
                $status = $attempt->status;
                $color = config('lms.status.' . $status . '.class');
                $output =
                    '<strong class="text-' .
                    $color .
                    '">' .
                    $status .
                    '</strong>';
                if (in_array($attempt->status, ['FAIL', 'RETURNED'])) {
                    $output =
                        '<strong class="text-' .
                        $color .
                        '">NOT SATISFACTORY</strong>';
                }
                if ($attempt->status === 'FAIL') {
                    $output .= "<button type='button' onclick='Assessment.returnToStudent({$attempt->id})' class='btn btn-sm btn-outline-primary float-end ' >Return to Student</button>";
                }

                return $output;
            })
            ->editColumn('assessed_by', function (QuizAttempt $attempt) {
                $output = '';
                $evaluation = Evaluation::latestEvaluationOf(
                    intval($attempt->id)
                )?->first();
                // SELECT * FROM `student_activities` WHERE `activity_details` LIKE '%accessed%' AND `activity_details` NOT LIKE '%"accessor_role":"Student"%';
                if ($attempt->system_result === 'EVALUATED') {
                    // 'INPROGRESS','COMPLETED','EVALUATED'
                    $output .= 'Auto Competent';
                } elseif (!empty($evaluation)) {
                    // {"activity_on":"11 May, 2023 8:43 PM","status":"SATISFACTORY","student_id":6026,"student":6026,"accessor_id":73,"accessor_role":"Trainer","accessed_at":"2023-05-11 11:13:23","user_id":6026,"ip":"101.114.180.127","activity_by__at":"2023-05-11 11:13:23","activity_by_id":73,"activity_by_role":"Trainer","is_cron_job":"No"}
                    $activity_details = null;
                    $activity = $attempt->activity ?? null;
                    if (
                        !empty($activity) &&
                        !empty($activity->activity_details)
                    ) {
                        $activity_details = json_decode(
                            $activity->getRawOriginal('activity_details'),
                            true
                        );
                    }
                    if (
                        $attempt->system_result === 'EVALUATED' ||
                        (!empty($activity_details['accessor_role']) &&
                            \Str::lower($activity_details['accessor_role']) ===
                                'student' &&
                            $attempt->system_result === 'MARKED')
                    ) {
                        $output .= 'System/Auto Competent';
                    } elseif (!empty($evaluation->evaluator_id)) {
                        $assessed_by_user = User::find($attempt->accessor_id);
                        if (empty($assessed_by_user)) {
                            $output .= '';
                        } else {
                            $output .= $assessed_by_user->name;
                        }
                    } else {
                        $output .= 'N/A';
                    }
                } else {
                    $output .= 'Not assessed yet.';
                }

                return $output;
            })
            ->editColumn('assessed_on', function (QuizAttempt $attempt) {
                $output = '';
                $evaluation = $attempt->evaluation()?->latest()->first();
                if ($attempt->system_result === 'EVALUATED') {
                    // 'INPROGRESS','COMPLETED','EVALUATED'
                    $output .= 'Auto Competent';
                } elseif (!empty($evaluation)) {
                    $output .= Carbon::parse(
                        $attempt->getRawOriginal('accessed_at')
                    )
                        ->timezone(Helper::getTimeZone())
                        ->format('j F, Y g:i A');
                } else {
                    $output .= 'Not assessed yet.';
                }

                return $output;
            })
            ->addColumn('action', 'assessmentdatatable.action')
            ->filterColumn('status', function ($query, $keyword) {
                if ($keyword === 'All' || !isset($keyword)) {
                    return $query;
                }
                if ($keyword === 'PENDING') {
                    return $query->latestAttemptSubmittedOnly()->onlyPending();
                }

                // 'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
                return $query->where('status', $keyword);
            })
            ->filterColumn('submitted_at', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse(
                        $searchVal->start
                    )->toDateString();
                    $endDate = Carbon::parse($searchVal->end)->toDateString();

                    return $query
                        ->whereDate('submitted_at', '>=', $startDate)
                        ->whereDate('submitted_at', '<=', $endDate);
                }

                return '';
            })
            ->rawColumns([
                'student',
                'status',
                'trainer',
                'accessed_by',
                'assessed_on',
            ]);
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(QuizAttempt $model)
    {
        $return = $model->newQuery()->with(['quiz', 'course', 'user']);

        $statusInput = $this->request()->get('status');
        if (strtolower($statusInput) === 'all' || empty($statusInput)) {
            $return = $return->latestAttempt();
        } elseif (strtolower($statusInput) === 'pending') {
            $return = $return->latestAttemptSubmittedOnly()->onlyPending();
        } else {
            $return = $return
                ->latestAttempt()
                ->where('quiz_attempts.status', '=', strtoupper($statusInput));
        }
        if (
            auth()
                ->user()
                ->isTrainer()
        ) {
            $return = $return
                ->relatedTrainer()
                ->where('quiz_attempts.system_result', '!=', 'EVALUATED');
        }

        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            $return = $return
                ->relatedLeader()
                ->where('quiz_attempts.system_result', '!=', 'EVALUATED');
        }

        $return = $return
            ->where('quiz_attempts.system_result', '!=', 'MARKED')
            ->where('system_result', '!=', 'INPROGRESS');

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
                ->whereDate('quiz_attempts.submitted_at', '>=', $date_start)
                ->whereDate('quiz_attempts.submitted_at', '<=', $date_end);
        }

        $return = $return
            ->orderBy(
                'quiz_attempts.submitted_at',
                strtolower($this->status) === 'pending' ? 'ASC' : 'DESC'
            )
            ->orderBy(
                'quiz_attempts.id',
                strtolower($this->status) === 'pending' ? 'ASC' : 'DESC'
            );

        return $return;
        //            ->where('status', 'SUBMITTED')->orWhere('status', 'REVIEWING')
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
        if ($this->request()->has('status')) {
            $urlData['status'] = $this->request()->get('status');
        }
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }

        return $this->builder()
            ->setTableId('assessments-table')
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
            //            Column::computed('action')
            //                  ->exportable(false)
            //                  ->printable(false)
            //                  ->width(60)
            //                  ->addClass('text-center text-nowrap'),
            Column::make('quiz.title')
                ->title('Quiz')
                ->orderable(false),
            Column::make('course.title')
                ->title('Course')
                ->orderable(false),
            Column::make('student')
                ->title('Student')
                ->data('student')
                ->name('user.first_name')
                ->orderable(false),
            Column::make('student_email')
                ->title('Student Email')
                ->data('user.email')
                ->name('user.email')
                ->orderable(false),
            Column::make('trainer')
                ->title('Assigned Trainer')
                ->searchable(false)
                ->orderable(false),
            Column::make('status')
                ->title('Status')
                ->data('status')
                ->name('status')
                ->orderable(false),
            Column::make('submitted_at')
                ->title('Submitted At')
                ->orderable(false),
            Column::make('assessed_by')
                ->title('Assessed By')
                ->searchable(false)
                ->orderable(false),
            Column::make('assessed_on')
                ->title('Assessed On')
                ->searchable(false)
                ->orderable(false),
            Column::make('study_type')
                ->title('Study Type')
                ->data('user.study_type')
                ->name('user.study_type')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'Assessment_' . date('YmdHis');
    }
}
