<?php

namespace App\DataTables\Reports;

use App\Helpers\Helper;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\StudentActivity;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CommencedUnitsDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        // Pre-load all competencies to avoid N+1 queries
        $competencies = DB::table('competencies')
            ->where('is_competent', 1)
            ->get()
            ->keyBy(function ($item) {
                return $item->user_id . '_' . $item->lesson_id;
            });

        return function ($row) use ($competencies) {
            $student = $row->user;
            $lesson = $row->actionable;
            $course = $row->course;

            // Determine student status (simplified to avoid detail queries)
            $student_status = $student->status ?? '';
            $student_active = $student->is_active;
            if (intval($student_active) === 0) {
                $student_status = 'INACTIVE';
            } else {
                if (\Str::lower($student_status) === 'enrolled') {
                    $student_status = 'REGISTERED';
                } elseif (\Str::lower($student_status) === 'onboarded') {
                    $student_status = 'ACTIVE';
                } else {
                    $student_status = 'ACTIVE';
                }
            }

            // Get competency date from pre-loaded data
            $competencyKey = $student->id . '_' . $lesson->id;
            $competencyDate = '';
            if (isset($competencies[$competencyKey])) {
                $competency = $competencies[$competencyKey];
                if (!empty($competency->lesson_end)) {
                    $competencyDate = Carbon::parse($competency->lesson_end)->format('d-m-Y');
                }
            }

            // Get lesson end date using the same method as competencies page
            $lessonEndDate = '';
            $lessonEndDateFormatted = StudentCourseService::getLessonEndDate(
                $student->id,
                $course->id,
                $lesson->id
            );
            if (!empty($lessonEndDateFormatted)) {
                $formatted = StudentCourseService::lessonEndDateBeforeCompetency($lessonEndDateFormatted);
                $lessonEndDate = $formatted ? Carbon::parse($formatted)->format('d-m-Y') : '';
            }

            return [
                'Student ID' => $student->id,
                'Student Name' => $student->name ?? '',
                'Status' => \Str::title(str_replace('_', ' ', $student_status)),
                'Study Type' => $student->study_type ?? '',
                'Course Name' => $course->title ?? '',
                'Lesson Name' => $lesson->title ?? '',
                'Course Start Date' => $row->enrolment_course_start_at ? Carbon::parse($row->enrolment_course_start_at)->format('d-m-Y') : '',
                'Lesson Start Date' => $this->getLessonStartDateForExport($student->id, $lesson->id, $row->activity_on),
                'Lesson End Date' => $lessonEndDate,
                'Competency End Date' => $competencyDate,
            ];
        };
    }

    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('student_name', function ($row) {
                if (empty($row->user)) {
                    return '';
                }

                return '<a href="' . route('account_manager.students.show', $row->user->id) . '">' .
                    $row->user->name . '</a>';
            })
            ->editColumn('status', function ($row) {
                $student = $row->user;
                $student_status = $student->status ?? '';
                $student_active = $student->is_active;
                if (intval($student_active) === 0) {
                    $student_status = 'INACTIVE';
                } else {
                    if (\Str::lower($student_status) === 'enrolled') {
                        $student_status = 'REGISTERED';
                    } elseif (\Str::lower($student_status) === 'onboarded') {
                        $student_status = 'ACTIVE';
                    } elseif (
                        empty($student->detail->onboard_at) ||
                        empty($student->detail->last_logged_in)
                    ) {
                        $student_status = 'ENROLLED';
                    } else {
                        $student_status = 'ACTIVE';
                    }
                }
                $color = config('constants.status.color.' . $student_status, 'primary');

                return '<span class="text-' . $color . '">' .
                    \Str::title(str_replace('_', ' ', $student_status)) . '</span>';
            })
            ->editColumn('study_type', function ($row) {
                return $row->user->study_type ?? '';
            })
            ->editColumn('course_name', function ($row) {
                return $row->course->title ?? '';
            })
            ->editColumn('lesson_name', function ($row) {
                return $row->actionable->title ?? '';
            })
            ->editColumn('course_start_date', function ($row) {
                return $row->enrolment_course_start_at ? Carbon::parse($row->enrolment_course_start_at)->format('d-m-Y') : '';
            })
            ->editColumn('lesson_start_date', function ($row) {
                return $this->getLessonStartDateForExport($row->user->id, $row->actionable->id, $row->activity_on);
            })
            ->editColumn('lesson_end_date', function ($row) {
                $lessonEndDate = StudentCourseService::getLessonEndDate(
                    $row->user->id,
                    $row->course->id,
                    $row->actionable->id
                );
                if (!empty($lessonEndDate)) {
                    $formattedDate = StudentCourseService::lessonEndDateBeforeCompetency($lessonEndDate);

                    return $formattedDate ? Carbon::parse($formattedDate)->format('d-m-Y') : '';
                }

                return '';
            })
            ->editColumn('competency_end_date', function ($row) {
                $competency = DB::table('competencies')
                    ->where('user_id', $row->user->id)
                    ->where('lesson_id', $row->actionable->id)
                    ->where('is_competent', 1)
                    ->first();
                if ($competency && !empty($competency->lesson_end)) {
                    return $competency->lesson_end;
                }

                return '';
            })
            ->filterColumn('lesson_start_date', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    $startDate = Carbon::parse($searchVal->start)->toDateString();
                    $endDate = Carbon::parse($searchVal->end)->toDateString();

                    return $query->whereDate('student_activities.activity_on', '>=', $startDate)
                        ->whereDate('student_activities.activity_on', '<=', $endDate);
                }

                return '';
            })
            ->rawColumns(['student_name', 'status']);
    }

    public function query(StudentActivity $model)
    {
        $query = $model->newQuery()
            ->select([
                'student_activities.*',
                'student_course_enrolments.course_start_at as enrolment_course_start_at',
            ])
            ->with(['user', 'course', 'actionable'])
            ->join('student_course_enrolments', function ($join) {
                $join->on('student_activities.user_id', '=', 'student_course_enrolments.user_id')
                    ->on('student_activities.course_id', '=', 'student_course_enrolments.course_id');
            })
            ->where('student_activities.activity_event', 'LESSON START')
            ->where('student_activities.actionable_type', \App\Models\Lesson::class)
            ->where('student_course_enrolments.status', '!=', 'DELIST')
            ->whereNotNull('student_activities.activity_on')
            ->where('student_activities.activity_on', '!=', '')
            ->whereHas('user')
            ->whereHas('course')
            ->whereHas('actionable');

        // Add date filtering if provided
        if ($this->request()->has('start_date') && $this->request()->has('end_date')) {
            $date_start = Carbon::parse($this->request()->get('start_date'))->toDateString();
            $date_end = Carbon::parse($this->request()->get('end_date'))->toDateString();
            $query->whereDate('student_course_enrolments.course_start_at', '>=', $date_start)
                ->whereDate('student_course_enrolments.course_start_at', '<=', $date_end);
        }

        // Add study type filtering if provided
        if ($this->request()->has('study_type') && !empty($this->request()->get('study_type'))) {
            $studyType = $this->request()->get('study_type');
            $query->whereHas('user', function ($q) use ($studyType) {
                if ($studyType === 'null') {
                    $q->whereNull('study_type');
                } else {
                    $q->where('study_type', $studyType);
                }
            });
        }

        // Add course filtering if provided
        if ($this->request()->has('course_name') && !empty($this->request()->get('course_name'))) {
            $courseIds = $this->request()->get('course_name');
            if (is_string($courseIds)) {
                // Handle comma-separated string from AJAX
                $courseIds = explode(',', $courseIds);
            }
            if (is_array($courseIds) && !empty($courseIds)) {
                $query->whereIn('student_activities.course_id', $courseIds);
            }
        }

        return $query->orderBy('student_activities.activity_on', 'DESC');
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        $urlData = [];
        $url = url()->current();
        if ($this->request()->has('start_date')) {
            $urlData['start_date'] = $this->request()->get('start_date');
        }
        if ($this->request()->has('end_date')) {
            $urlData['end_date'] = $this->request()->get('end_date');
        }
        if ($this->request()->has('study_type')) {
            $urlData['study_type'] = $this->request()->get('study_type');
        }
        if ($this->request()->has('course_name')) {
            $courseNames = $this->request()->get('course_name');
            if (is_array($courseNames)) {
                $urlData['course_name'] = implode(',', $courseNames);
            } else {
                $urlData['course_name'] = $courseNames;
            }
        }

        return $this->builder()
            ->setTableId('commenced-units-table')
            ->addTableClass(['table-responsive', 'display'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    7, // lesson_start_date column
                    'desc',
                ],
            ])
            ->buttons(
                Button::make('export')
                    ->text("<i class='font-small-4 me-50' data-lucide='share'></i>Export")
                    ->className('dt-button buttons-collection btn btn-outline-secondary dropdown-toggle me-2')
                    ->buttons([
                        Button::make('postCsv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                    ])
                    ->authorized(auth()->user()->can('download reports'))
            );
    }

    /**
     * Get columns.
     */
    protected function getColumns()
    {
        return [
            Column::make('id')->visible(false),
            Column::make('user_id')->title('Student ID'),
            Column::computed('student_name')->title('Student Name')->orderable(false),
            Column::computed('status')->title('Status')->orderable(false),
            Column::computed('study_type')->title('Study Type')->orderable(false),
            Column::computed('course_name')->title('Course Name')->orderable(false),
            Column::computed('lesson_name')->title('Lesson Name')->orderable(false),
            Column::computed('course_start_date')->title('Course Start Date')->orderable(false),
            Column::computed('lesson_start_date')->title('Lesson Start Date')->orderable(false),
            Column::computed('lesson_end_date')->title('Lesson End Date')->orderable(false),
            Column::computed('competency_end_date')->title('Competency End Date')->orderable(false),
        ];
    }

    /**
     * Get lesson start date for export.
     * Uses the same method as training plan to ensure consistency.
     */
    private function getLessonStartDateForExport($userId, $lessonId, $activityOn)
    {
        // Use the exact same method as training plan (StudentCourseService::lessonStartDate)
        // This ensures the date matches what's shown on the training plan
        $lessonStartDate = StudentCourseService::lessonStartDate($userId, $lessonId);

        if ($lessonStartDate) {
            // Parse the formatted date string (e.g., "8 January, 2026") and convert to d-m-Y
            // Use DateHelper to match training plan's formatDate method
            try {
                $parsed = \App\Helpers\DateHelper::parseWithTimeZone($lessonStartDate);
                return $parsed ? $parsed->format('d-m-Y') : '';
            } catch (\Exception $e) {
                // Fallback to direct parsing
                return Carbon::parse($lessonStartDate)->format('d-m-Y');
            }
        }

        // Fallback to activity_on if lessonStartDate returns null
        if ($activityOn) {
            $rawValue = is_object($activityOn) && method_exists($activityOn, 'getRawOriginal')
                ? $activityOn->getRawOriginal('activity_on')
                : $activityOn;

            if ($rawValue) {
                // Use same timezone conversion as lessonStartDate
                return Carbon::parse($rawValue)->timezone(Helper::getTimeZone())->format('d-m-Y');
            }
        }

        return '';
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'CommencedUnits_' . date('YmdHis');
    }
}
