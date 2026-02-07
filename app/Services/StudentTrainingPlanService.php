<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Helpers\Helper;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\StudentLMSAttachables;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentTrainingPlanService
{
    protected int $user_id;

    protected StudentActivityService $activityService;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
        $this->activityService = app()[StudentActivityService::class];
    }

    public function getTrainingPlan(bool $raw = false): \Illuminate\Http\JsonResponse|array
    {
        try {
            if (empty($this->user_id)) {
                throw new \InvalidArgumentException('User ID is required');
            }

            $output = [];
            $progresses = $this->getStudentProgress();

            // Batch fetch all enrolments for this user (full records, keyed by course_id)
            $enrolments = \App\Models\StudentCourseEnrolment::where('user_id', $this->user_id)->get()->keyBy('course_id')->map(function ($enrolment) {
                return $enrolment->toArray();
            })->toArray();

            if (empty($progresses)) {
                return $raw ? [] : Helper::errorResponse('No progress made yet.', 404);
            }

            foreach ($progresses as $progress) {
                try {
                    if (!$this->isValidProgress($progress)) {
                        continue;
                    }

                    $course_id = $progress->course_id;
                    $details = $this->getProgressDetails($progress, $course_id);

                    if (empty($details)) {
                        \Log::warning('Empty progress details', [
                            'user_id' => $this->user_id,
                            'course_id' => $course_id,
                            'progress_id' => $progress->id,
                        ]);

                        continue;
                    }

                    $percentageData = $this->getTotalCounts($details, $progress->course_id);
                    // Pass enrolments to expectedPercentage and reCalculateProgress
                    $temp = $this->buildCourseResponse($progress, $details, $percentageData, $enrolments);

                    if (!empty($details['lessons']['list']) && $details['lessons']['count'] > 0) {
                        // Pass enrolments map to processLessons
                        $temp['children'] = $this->processLessons($progress, $details, $course_id, $enrolments);
                    }

                    $output[] = $temp;
                } catch (\Exception $e) {
                    \Log::error('Error processing course progress', [
                        'user_id' => $this->user_id,
                        'progress_id' => $progress->id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    continue;
                }
            }

            return $raw ? $output : Helper::successResponse($output, 'Training Plan For ' . count($progresses) . ' Course(s)');
        } catch (\Exception $e) {
            \Log::error('Error generating training plan', [
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $raw ? [] : Helper::errorResponse('Error generating training plan: ' . $e->getMessage(), 500);
        }
    }

    protected function getStudentProgress(): Collection
    {
        try {
            $query = CourseProgress::select([
                'id',
                'user_id',
                'course_id',
                'percentage',
                'details',
            ])
                ->where('user_id', $this->user_id)
                ->where('course_id', '!=', config('constants.precourse_quiz_id', 0));

            $query->with([
                'user:id,first_name,last_name,email',
                'course:id,title,slug,status,visibility,is_main_course,category',
                'course.lessons' => function ($query) {
                    $query->select([
                        'id',
                        'course_id',
                        'title',
                        'order',
                        'has_topic',
                        'has_work_placement',
                        'release_key',
                    ])
                        ->orderBy('order');
                },
                'course.lessons.topics' => function ($query) {
                    $query->select([
                        'id',
                        'lesson_id',
                        'title',
                        'has_quiz',
                    ]);
                },
                'course.lessons.topics.quizzes' => function ($query) {
                    $query->select([
                        'id',
                        'topic_id',
                        'title',
                        'has_checklist',
                    ]);
                },
                'course.lessons.topics.quizzes.attempts' => function ($query) {
                    $query->select([
                        'id',
                        'user_id',
                        'quiz_id',
                        'status',
                        'system_result',
                        'attempt',
                        'created_at',
                        'updated_at',
                        'submitted_at',
                        'accessed_at',
                    ])
                        ->where('user_id', $this->user_id)
                        ->latestThreeAttempts()
                        ->orderBy('created_at', 'ASC');
                },
            ]);

            $progresses = $query->get();

            foreach ($progresses as $progress) {
                if (!$progress->course || !$progress->user) {
                    \Log::warning('Missing required relationships in CourseProgress', [
                        'progress_id' => $progress->id,
                        'user_id' => $this->user_id,
                        'course_id' => $progress->course_id,
                    ]);
                } else {
                    $progressDetails = $progress->details?->toArray() ?? [];
                    $this->setProgressFieldsOnRelations($progress, $progressDetails);
                }
            }

            return $progresses;
        } catch (\Exception $e) {
            \Log::error('Error fetching student progress', [
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return collect();
        }
    }

    protected function setProgressFieldsOnRelations($progress, $progressDetails): void
    {
        $dateFields = [
            'submitted_at', 'passed_at', 'failed_at', 'attempted_at', 'marked_at', 'completed_at', 'lesson_end_at',
        ];
        $otherFields = [
            'attempted', 'submitted', 'passed', 'failed', 'marked',
        ];

        foreach ($progress->course->lessons as $lesson) {
            $lessonId = $lesson->id;
            if (isset($progressDetails['lessons']['list'][$lessonId])) {
                $lessonDetails = $progressDetails['lessons']['list'][$lessonId];
                $lesson->keyId = $lessonId;
                // Date fields for lesson
                foreach ($dateFields as $field) {
                    if (
                        isset($lessonDetails[$field]) &&
                        $lessonDetails[$field] !== null &&
                        !isset($lesson->$field)
                    ) {
                        $date = DateHelper::parse($lessonDetails[$field]);
                        $formatted = $date?->format('Y-m-d H:i:s');
                        if (strlen($formatted) === 10) {
                            $formatted .= ' 00:00';
                        }
                        $lesson->$field = $formatted ?? $lessonDetails[$field];
                    }
                }
                // Other fields for lesson
                foreach ($otherFields as $field) {
                    if (
                        isset($lessonDetails[$field]) &&
                        $lessonDetails[$field] !== null &&
                        !isset($lesson->$field)
                    ) {
                        $lesson->$field = $lessonDetails[$field];
                    }
                }

                // Topics
                if (!empty($lesson->topics)) {
                    foreach ($lesson->topics as $topic) {
                        $topicId = $topic->id;
                        if (isset($lessonDetails['topics']['list'][$topicId])) {
                            $topicDetails = $lessonDetails['topics']['list'][$topicId];
                            $topic->keyId = $topicId;
                            // Date fields for topic
                            foreach ($dateFields as $field) {
                                if (
                                    isset($topicDetails[$field]) &&
                                    $topicDetails[$field] !== null &&
                                    !isset($topic->$field)
                                ) {
                                    $date = DateHelper::parse($topicDetails[$field]);
                                    $formatted = $date?->format('Y-m-d H:i:s');
                                    if (strlen($formatted) === 10) {
                                        $formatted .= ' 00:00';
                                    }
                                    $topic->$field = $formatted ?? $topicDetails[$field];
                                }
                            }
                            // Other fields for topic
                            foreach ($otherFields as $field) {
                                if (
                                    isset($topicDetails[$field]) &&
                                    $topicDetails[$field] !== null &&
                                    !isset($topic->$field)
                                ) {
                                    $topic->$field = $topicDetails[$field];
                                }
                            }

                            // Quizzes
                            if (!empty($topic->quizzes)) {
                                foreach ($topic->quizzes as $quiz) {
                                    $quizId = $quiz->id;
                                    if (isset($topicDetails['quizzes']['list'][$quizId])) {
                                        $quizDetails = $topicDetails['quizzes']['list'][$quizId];
                                        $quiz->keyId = $quizId;

                                        // Date fields for quiz
                                        foreach ($dateFields as $field) {
                                            if (
                                                isset($quizDetails[$field]) &&
                                                $quizDetails[$field] !== null &&
                                                !isset($quiz->$field)
                                            ) {
                                                $date = DateHelper::parse($quizDetails[$field]);
                                                $formatted = $date?->format('Y-m-d H:i:s');
                                                if (strlen($formatted) === 10) {
                                                    $formatted .= ' 00:00';
                                                }
                                                $quiz->$field = $formatted ?? $quizDetails[$field];
                                            }
                                        }
                                        // Other fields for quiz
                                        foreach ($otherFields as $field) {
                                            if (
                                                isset($quizDetails[$field]) &&
                                                $quizDetails[$field] !== null &&
                                                !isset($quiz->$field)
                                            ) {
                                                $quiz->$field = $quizDetails[$field];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function reCalculateProgress($course_id, $enrolments = [])
    {
        if ($course_id === config('lln.course_id') || $course_id === config('ptr.course_id')) {
            return;
        }

        $courseProgress = $this->getProgress($this->user_id, $course_id);
        $enrolment = $enrolments[$course_id] ?? null;
        if (empty($enrolment)) {
            $enrolment = $this->getEnrolment($this->user_id, $course_id); // fallback
        }
        if (empty($enrolment)
            && intval(config('lln.course_id'), 11111) !== intval($course_id)
            && intval(config('ptr.course_id'), 11112) !== intval($course_id)) {
            if (!empty($courseProgress)) {
                $courseProgress->delete();
            }
            \Log::warning('No active enrolment found for user reCalculateProgress()', [
                'user_id' => $this->user_id,
                'course_id' => $course_id,
                'removed_progress' => !empty($courseProgress) ? $courseProgress->id : null,
            ]);

            return;
        }

        $newProgress = $this->populateProgress($course_id);

        if (empty($courseProgress)) {
            $totalCounts = $this->getTotalCounts($newProgress, $course_id);

            $courseProgress = CourseProgress::create([
                'user_id' => $this->user_id,
                'course_id' => $course_id,
                'percentage' => $totalCounts,
                'details' => $newProgress,
            ]);
        }

        $existingProgress = $courseProgress->details?->toArray() ?? [];
        $resultProgress = $this->getCleanProgressDetails($existingProgress, $newProgress);
        $progress = $this->reEvaluateProgress($this->user_id, $resultProgress);

        // Format all date fields in the progress details
        if (!empty($progress['lessons']['list'])) {
            foreach ($progress['lessons']['list'] as &$lesson) {
                $lesson['at'] = $this->formatDateForDatabase($lesson['at'] ?? null);
                $lesson['completed_at'] = $this->formatDateForDatabase($lesson['completed_at'] ?? null);
                $lesson['submitted_at'] = $this->formatDateForDatabase($lesson['submitted_at'] ?? null);
                $lesson['marked_at'] = $this->formatDateForDatabase($lesson['marked_at'] ?? null);
                $lesson['lesson_end_at'] = $this->formatDateForDatabase($lesson['lesson_end_at'] ?? null);

                if (!empty($lesson['topics']['list'])) {
                    foreach ($lesson['topics']['list'] as &$topic) {
                        $topic['at'] = $this->formatDateForDatabase($topic['at'] ?? null);
                        $topic['completed_at'] = $this->formatDateForDatabase($topic['completed_at'] ?? null);
                        $topic['submitted_at'] = $this->formatDateForDatabase($topic['submitted_at'] ?? null);
                        $topic['marked_at'] = $this->formatDateForDatabase($topic['marked_at'] ?? null);

                        if (!empty($topic['quizzes']['list'])) {
                            foreach ($topic['quizzes']['list'] as &$quiz) {
                                $quiz['at'] = $this->formatDateForDatabase($quiz['at'] ?? null);
                                $quiz['completed_at'] = $this->formatDateForDatabase($quiz['completed_at'] ?? null);
                                $quiz['submitted_at'] = $this->formatDateForDatabase($quiz['submitted_at'] ?? null);
                                $quiz['passed_at'] = $this->formatDateForDatabase($quiz['passed_at'] ?? null);
                                $quiz['failed_at'] = $this->formatDateForDatabase($quiz['failed_at'] ?? null);
                                $quiz['marked_at'] = $this->formatDateForDatabase($quiz['marked_at'] ?? null);
                            }
                        }
                    }
                }
            }
        }

        $totalCounts = $this->getTotalCounts($progress, $course_id);

        $courseProgress->percentage = $totalCounts;
        $courseProgress->details = $progress;
        $courseProgress->save();

        $this->updateAdminReportProgress($this->user_id, $course_id);

        return $this->updateProgressSession($courseProgress);
    }

    protected function getEnrolment($user_id, $course_id)
    {
        return StudentCourseEnrolment::where('user_id', $user_id)
            ->where('course_id', $course_id)->first();
    }

    protected function attachModels($progress)
    {
        if (empty($progress['course'])) {
            return $progress;
        }

        $course = Course::find($progress['course']);
        $progress['data'] = $course->toArray();

        if ($progress['lessons']['count'] > 0) {
            foreach ($progress['lessons']['list'] as $lesson_id => &$lesson) {
                $lesson['data'] = $course->lessons->firstWhere('id', $lesson_id)?->toArray() ?? [];
                if ($lesson['topics']['count'] > 0) {
                    foreach ($lesson['topics']['list'] as $topic_id => &$topic) {
                        $topic['data'] = $course->lessons->firstWhere('id', $lesson_id)
                            ->topics->firstWhere('id', $topic_id)?->toArray() ?? [];
                        if ($topic['quizzes']['count'] > 0) {
                            foreach ($topic['quizzes']['list'] as $quiz_id => &$quiz) {
                                $quiz['data'] = $course->lessons->firstWhere('id', $lesson_id)
                                    ->topics->firstWhere('id', $topic_id)
                                    ->quizzes->firstWhere('id', $quiz_id)?->toArray() ?? [];
                            }
                        }
                    }
                }
            }
        }

        $progress['models_attached'] = true;

        return $progress;
    }

    protected function getStatus($for): string
    {
        $status = 'ATTEMPTING';
        if ($for['completed'] || !empty($for['marked_at'])) {
            $status = 'COMPLETED';
        } elseif (!empty($for['submitted'])) {
            $status = 'SUBMITTED';
        }

        return $status;
    }

    public function calculatePercentage($data, $user_id, $course_id): float
    {
        if ($data['course_completed']) {
            return 100.00;
        }

        $percentage = 0.00;
        $adjust = ($data['passed'] === $data['empty']) ? $data['passed'] : ($data['passed'] - $data['empty']);
        $adjust = $data['total'] - $adjust;

        if ($data['total'] === 0) {
            $percentage = $data['course_completed'] ? 100.00 : 0.00;
        } else {
            $isMainCourse = cache()->remember("is_main_course_{$course_id}", now()->addHours(12), function () use ($course_id) {
                return Course::mainCourseOnly()->where('id', $course_id)->exists();
            });
            $user = User::find($user_id);
            $onboarded = $user->detail->onboard_at;
            //calculate percentage
            $percent = (($data['processed']) / $data['total']) * 100;
            //adjustment for main course
            if ($isMainCourse) {
                $percentage = ((empty($onboarded)) ? 0.00 : 5.00) + ($percent * 0.95);
            } else {
                // For non-main courses (like semester 2), use the calculated percentage directly
                $percentage = $percent;
            }

            $percentage = floatval(number_format($percentage, 2));
            if ($percentage > 100) {
                $percentage = 100.00;
            }
        }

        return $percentage;
    }

    protected function expectedPercentage($course_id, $percentage, $enrolments = [])
    {
        if ($percentage === 0) {
            return 0;
        }

        $enrolment = $enrolments[$course_id] ?? null;
        if (empty($enrolment)) {
            $enrolment = $this->getEnrolment($this->user_id, $course_id); // fallback
        }

        if (empty($enrolment)) {
            return 0;
        }

        // Always get raw values to avoid accessor formatting issues
        $startDate = \Carbon\Carbon::parse($enrolment['course_start_at'] ?? $enrolment->getRawOriginal('course_start_at'));

        $endDateRaw = is_array($enrolment)
            ? ($this->getEnrolment($this->user_id, $course_id)?->getRawOriginal('course_ends_at') ?? null)
            : $enrolment->getRawOriginal('course_ends_at');
        $endDate = \Carbon\Carbon::parse($endDateRaw);

        $now = \Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone());

        if ($startDate->greaterThan($now)) {
            return 0;
        }

        if ($endDate->lessThanOrEqualTo($now)) {
            return 100;
        }

        $totalDays = $startDate->diffInDays($endDate);
        $daysPassed = $startDate->diffInDays($now);
        $expectedVal = number_format(($daysPassed / $totalDays) * 100, 2);

        return ($totalDays <= 0) ? 0 : min($expectedVal, 100);
    }

    protected function updateOrCreateStudentActivity($model, $event, $student_id, $data)
    {
        if (empty($model)) {
            return false;
        }

        $activity = $this->activityService->getActivityWhere([
            'activity_event' => $event,
            'actionable_type' => $model::class,
            'actionable_id' => $model->id,
            'user_id' => $student_id,
        ]);

        $data['user_id'] = $student_id ?? 0;

        if ($activity->isEmpty()) {
            return $this->activityService->setActivity([
                'activity_event' => $event,
                'activity_details' => $data,
                'user_id' => $student_id,
            ], $model);
        }

        return $this->activityService->updateActivity([
            'activity_details' => $data,
        ], $activity->first(), $model);
    }

    protected function getCurrentChecklistStatus(Quiz $quiz, $submitted)
    {
        $tempChecklist = [
            'status' => 'NOT ATTEMPTED',
            'is_submitted' => $submitted,
            'quiz_id' => $quiz->id,
        ];

        if (!empty($quiz) && $submitted) {
            $currentChecklists = $quiz->attachedChecklistsFor($this->user_id);
            $checklistCount = $currentChecklists->count();

            $tempChecklist['is_submitted'] = true;
            $tempChecklist['count'] = $checklistCount;
            $tempChecklist['query'] = $currentChecklists->toSql();
            $tempChecklist['items'] = $currentChecklists->get()->toArray();

            $tempCL = $currentChecklists->orderBy('id', 'desc');
            $notSatisfactory = 0;
            foreach ($tempCL->get() as $checkListItem) {
                $status = $checkListItem->properties['status'] ?? 'N/A';
                if ($status === 'NOT SATISFACTORY') {
                    $notSatisfactory++;
                }
            }

            $firstChecklist = $tempCL->first();
            $tempChecklist['firstChecklist'] = $firstChecklist;

            if (!empty($firstChecklist) && !empty($firstChecklist->properties['status'])) {
                if ($firstChecklist->properties['status'] === 'NOT SATISFACTORY' || $notSatisfactory === 3) {
                    $tempChecklist['failed'] = true;
                    $tempChecklist['status'] = 'FAILED';
                    $tempChecklist['failed_on'] = $firstChecklist->created_at;
                } elseif ($firstChecklist->properties['status'] === 'SATISFACTORY') {
                    $tempChecklist['failed'] = false;
                    $tempChecklist['status'] = 'COMPLETED';
                    $tempChecklist['completed_on'] = $firstChecklist->submitted_at ?? $firstChecklist->created_at;
                }
            }
        }

        return $tempChecklist;
    }

    public function renderTrainingPlan($trainingPlan, $student, $studentActive = true): array
    {
        $output = '';

        if (!empty($trainingPlan)) {
            $output = $this->creatAccordion($trainingPlan, 'course', $student, $studentActive);
        }

        return ['html' => $output, 'raw' => $trainingPlan];
    }

    protected function creatAccordion($items, $type, $student, $studentActive, $inner = false, $count = '')
    {
        $output = '';
        $log = [];

        if (!empty($items) && count($items) > 0) {
            if ($inner) {
                $type = ($type === 'course') ? 'lesson' : (($type === 'lesson') ? 'topic' : (($type === 'topic') ? 'quiz' : 'attempt'));
            }

            $plural = Str::plural($type);
            $output = '<div class="accordion accordion-margin" id="accordion-' . $plural . ($count ?? '') . '">';

            foreach ($items as $id => $item) {
                $log[$type][$id] = $item['data'] ?? [];
                if ($type === 'attempt') {
                    $activity = $this->activityService->getActivityWhere([
                        'activity_event' => 'ASSESSMENT MARKED',
                        'actionable_type' => QuizAttempt::class,
                        'actionable_id' => $item['data']['id'] ?? 0,
                        'user_id' => $student->id,
                    ])->sortByDesc('id')->first();
                    $activity_time = $item['data']['accessed_at'] ?? ($activity ? $activity->activity_on : null);

                    if (!empty($item['data'])) {
                        $output .= '<div class="alert alert-' . config('lms.status.' . $item['data']['status'] . '.class') . '" role="alert">
                                <div class="alert-body d-flex flex-row" data-attempt="' . $item['data']['id'] . '">
                                    <span class="me-2">Attempt#' . $item['data']['attempt'] . '</span>
                                    <span> </span>
                                    <span data-status="' . $item['data']['status'] . '" class="d-flex flex-grow-1 fw-bolder">' .
                            (in_array($item['data']['status'], ['RETURNED', 'FAIL', 'OVERDUE']) ? 'NOT SATISFACTORY' : $item['data']['status']) . ':</span>';
                        $output .= '<strong class="me-4">' . Carbon::parse($activity_time)->timezone(Helper::getTimeZone())->format('j F, Y g:i A') . '</strong>';
                        if (auth()->user()->can('mark assessments') && $item['data']['system_result'] !== 'INPROGRESS') {
                            $output .= '<a class="btn btn-primary btn-sm d-flex align-items-end" href="' . $item['link'] . '" target="_blank">Click here</a>';
                        }
                        $output .= '</div></div>';
                    }
                } else {
                    $output .= "<div class='accordion-item' data-id='" . ($item['data']['id'] ?? $id) . "'>";
                    $additional = '';
                    $checklistStatus = '';
                    $isCompetent = false;

                    if ($item['type'] === 'lesson') {
                        if (!empty($item['data']['has_topic']) && !empty($item['checklist'])) {
                            $checklistStatus = 'Checklist: ' . ($item['checklist']['status'] ?? '');
                        }
                        $additional .= $this->lessonTitleAdditional($item, $student);
                        if (!empty($item['competency']) && $item['competency']['is_competent']) {
                            $isCompetent = true;
                        }
                    }

                    $statusClass = 'primary';
                    if ($item['type'] === 'course') {
                        if (!empty($item['stats']['all_lessons_competent'])) {
                            $statusClass = 'success';
                        } else {
                            $statusClass = config('lms.status.' . ($item['status'] ?? 'ATTEMPTING') . '.class');
                        }
                    } elseif ($isCompetent) {
                        $statusClass = 'purple';
                    } else {
                        $statusClass = config('lms.status.' . (!empty($item['stats']['is_marked_complete']) ? 'MARKED' : ($item['status'] ?? 'ATTEMPTING')) . '.class');
                    }

                    $output .= '<h2 class="accordion-header " id="heading' . ucfirst($item['type']) . $id . '">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#accordion' . ucfirst($item['type']) . $id . '"
                                    aria-expanded="false"
                                    aria-controls="accordion' . ucfirst($item['type']) . $id . '">
                                        <span data-satus="' . ($isCompetent ? 'COMPETENT' : ($item['status'] ?? 'ATTEMPTING')) . '"
                                                class="circle-icon bg-' . $statusClass . ' m-50"></span>
                                    <span class="' . ($item['type'] === 'lesson' ? 'd-flex justify-content-between flex-grow-1' : '') . '">
                                        <span class="flex-grow-1"> ' . ucfirst($item['type']) . ': ' . ($item['title'] ?? '') . '</span>
                                        <small class="text-purple fw-bold flex-grow-1">' . ($isCompetent ? 'Competency Achieved' : '') . '</small>
                                        <small class="text-info fw-bold flex-grow-1">' . $checklistStatus . '</small>
                                        <small class="text-muted nowrap">' . $additional . '</small>
                                        <small class="text-primary ms-1 me-1" data-type="' . $item['type'] . '" data-checklist="' . (!empty($item['data']) ? $item['data']['has_checklist'] ?? 'NO' : '') . '">' .
                        ((auth()->user()->can('upload checklist') && $item['type'] === 'quiz' && !empty($item['data']) && intval($item['data']['has_checklist'] ?? 0) === 1) ? '* Checklist Required' : '') . '</small>
                                    </span>
                                </button>
                            </h2>';

                    $output .= '<div
                                id="accordion' . ucfirst($item['type']) . $id . '"
                                class="accordion-collapse collapse"
                                aria-labelledby="heading' . ucfirst($item['type']) . $id . '"
                                data-bs-parent="#accordion-' . $plural . $count . '">';

                    $output .= '<div class="accordion-body">';
                    $output .= '<div class="d-flex flex-row justify-content-between">';
                    if ($item['type'] === 'quiz') {
                        $output .= $this->quizAddons($item, $student, $id);
                    }
                    if ($item['type'] === 'lesson') {
                        $output .= $this->lessonAddons($item, $student, $id, $studentActive);
                    }
                    if ($item['type'] === 'topic') {
                        $output .= $this->topicAddons($item, $studentActive, $id);
                    }
                    $output .= '</div>';
                    if (isset($item['children']) && !empty($item['children'])) {
                        $output .= $this->creatAccordion($item['children'], $item['type'], $student, $studentActive, true, $id);
                    }
                    $output .= '</div></div></div>';
                }
            }

            $output .= '</div>';
        }

        return $output;
    }

    protected function formatDate($date, $format = 'j F, Y'): string
    {
        if (empty($date)) {
            return '';
        }

        return DateHelper::parseWithTimeZone($date)->format($format);
    }

    protected function generateStatusBadge($status, $text, $class = ''): string
    {
        $statusClass = config('lms.status.' . $status . '.class', 'primary');

        return "<span class='fw-normal ms-1 me-1'><span class='fw-bold text-{$statusClass} {$class}'>{$text}</span>";
    }

    protected function generateButton($text, $onClick, $class = 'btn-primary', $item = null, $student = null, $id = null): string
    {
        $data = '';
        if ($item && $student && $id) {
            $data = json_encode([
                'item_id' => $item['data']['id'],
                'student_id' => $student->id,
                'id' => $id,
            ]);
        }

        return "<button class='btn {$class} btn-sm d-flex align-items-end' onclick='{$onClick}'" . ($data ? " data-input='{$data}'" : '') . ">{$text}</button>";
    }

    protected function lessonTitleAdditional($item, $student)
    {
        $output = '';
        $lesson = Lesson::find($item['data']['id']);
        $enrolment = $item['enrolment'] ?? null;
        if (empty($enrolment)) {
            $enrolment = $this->getEnrolment($student->id, $item['data']['course_id']); // fallback
        }
        $courseStartDate = $lesson->release_key === 'XDAYS' && $enrolment ? \Carbon\Carbon::parse($enrolment['course_start_at'] ?? $enrolment->getRawOriginal('course_start_at')) : null;

        if ($lesson->release_key !== 'IMMEDIATE') {
            $releaseDate = $lesson->releasePlan(
                $courseStartDate,
                $student->id,
                $item['data']['course_id']
            );
            if ($releaseDate !== null && !$lesson->isAllowed($courseStartDate)) {
                $output .= "<i class='me-50' data-lucide='calendar'></i> Available On: " . (\Carbon\Carbon::parse($releaseDate)->format('d-m-Y'));
            }
        }

        $lessonStartDate = $this->getLessonStartDate($item, $student);
        if (!empty($lessonStartDate)) {
            $output .= ' Start Date: ' . $this->formatDate($lessonStartDate);
        }

        // Always try to get the lesson end date, regardless of availability
        $lessonEndDate = StudentCourseService::lessonEndDate($student->id, $item['data']['id'], true);

        if (!empty($lessonEndDate)) {
            $output .= ' End Date: ' . $this->formatDate($lessonEndDate);
        } elseif (!empty($item['stats']['is_marked_complete'])) {
            // Only show "Marked Completed on" if there's no End Date
            $markedDate = $this->getMarkedCompletionDate($item, $student);
            if (!empty($markedDate)) {
                $output .= ' Marked Completed on: ' . $markedDate;
            }
        }

        return $output;
    }

    protected function getActivityTime($item, $student): ?string
    {
        if (isset($item['start_date'])) {
            return $item['start_date'];
        }

        if (!empty($item['first_attempt'])) {
            $firstAttempt = $item['first_attempt']['quiz'];
            $quizAttempt = $item['first_attempt']['attempt'] ?? QuizAttempt::where('quiz_id', $firstAttempt['data']['id'])
                ->where('user_id', $student->id)
                ->first()?->toArray();
            $activityTime = $quizAttempt['accessed_at'] ?? null;

            if (empty($activityTime)) {
                $activityS = $this->activityService->getActivityWhere([
                    'activity_event' => 'ASSESSMENT MARKED',
                    'actionable_type' => QuizAttempt::class,
                    'actionable_id' => $item['data']['id'],
                    'user_id' => $student->id,
                ])->sortByDesc('id')->first();

                return $activityS ? $activityS->getRawOriginal('activity_on') : ($firstAttempt['submitted_at'] ?? $firstAttempt['attempted_at'] ?? null);
            }

            return $activityTime;
        }

        $activity = $this->activityService->getActivityWhere([
            'activity_event' => 'LESSON START',
            'actionable_type' => Lesson::class,
            'actionable_id' => $item['data']['id'],
            'user_id' => $student->id,
        ])->first();

        return $activity ? $activity->getRawOriginal('activity_on') : ($item['submitted_at'] ?? $item['attempted_at'] ?? null);
    }

    protected function getMarkedCompletionDate($item, $student): ?string
    {
        $activityMarked = $this->activityService->getActivityWhere([
            'activity_event' => 'LESSON MARKED',
            'actionable_type' => Lesson::class,
            'actionable_id' => $item['data']['id'],
            'user_id' => $student->id,
        ])->first();

        if (empty($activityMarked) && !empty($item['marked_at'])) {
            return $this->formatDate($item['marked_at']);
        } elseif (!empty($activityMarked)) {
            return $this->formatDate($activityMarked->activity_on);
        }

        return null;
    }

    protected function lessonAddons($item, $student, $id, $studentActive)
    {
        $studentActive = true;
        $output = '';

        $lessonData = Lesson::find($item['data']['id']);
        if (\Str::contains($lessonData->title, 'Study Tips')) {
            return $output;
        }

        // Work Placement Section
        if (auth()->user()->can('mark work placement') && $item['type'] === 'lesson' && intval($item['data']['has_work_placement'] ?? 0) === 1) {
            $output .= $this->renderWorkPlacementSection($item, $student, $id);
        }

        // Mark Complete Section
        if (auth()->user()->can('mark complete') && $item['type'] === 'lesson' && $item['status'] !== 'COMPLETED' && $studentActive) {
            $output .= $this->generateButton('Mark Lesson Complete', "LMS.MarkLessonComplete({$id}, {$item['user_id']})", 'btn-success', $item, $student, $id);
        }

        // Competency Section - Only show if lesson is completed and all requirements are met
        if ($item['type'] === 'lesson' && $item['status'] === 'COMPLETED' && $studentActive) {
            $allRequirementsMet = true;
            $requirementChecks = [];

            // Check if work placement is required and completed
            if (intval($item['data']['has_work_placement'] ?? 0) === 1) {
                $workPlacement = StudentLMSAttachables::forEvent('WORK_PLACEMENT')
                    ->forAttachable(Lesson::class, $item['data']['id'])
                    ->where('student_id', $student->id)
                    ->first();
                if (!$workPlacement) {
                    $allRequirementsMet = false;
                    $requirementChecks['work_placement'] = 'Missing';
                } else {
                    $requirementChecks['work_placement'] = 'Complete';
                }
            } else {
                $requirementChecks['work_placement'] = 'Not required';
            }

            // Use the new method for checklist validation
            $checklistComplete = $this->verifyChecklistCompletion($item, $student);
            if (!$checklistComplete) {
                $allRequirementsMet = false;
                $requirementChecks['checklist'] = 'Incomplete';
            } else {
                $requirementChecks['checklist'] = 'Complete';
            }

            if ($allRequirementsMet && auth()->user()->can('mark competency')) {
                if (is_array($item['competency']) && $item['competency']['is_competent']) {
                    $output .= $this->generateCompetencyBadge($item);
                } else {
                    $output .= $this->generateCompetencyButton($item, $student, $lessonData, $id);
                }
            }
        }

        // Add unlock button for admins if lesson is not immediately available
        if (auth()->user() && auth()->user()->can('unlock lessons')) {
            $lesson = $lessonData;
            // Only show lock/unlock if lesson is NOT completed and NOT submitted
            $isCompleted = !empty($item['stats']['completed']);
            $isSubmitted = !empty($item['stats']['submitted']);
            if ($lesson && $lesson->release_key !== 'IMMEDIATE' && !$isCompleted && !$isSubmitted) {
                // Use enrolment from item if available
                $courseStartDate = null;
                if (isset($item['enrolment']['course_start_at']) && $lesson->release_key === 'XDAYS') {
                    $courseStartDate = \Carbon\Carbon::parse($item['enrolment']['course_start_at']);
                } elseif ($lesson->release_key === 'XDAYS') {
                    // fallback, but should rarely happen
                    $enrolment = $this->getEnrolment($item['user_id'], $item['data']['course_id']);
                    $courseStartDate = $enrolment ? \Carbon\Carbon::parse($enrolment->getRawOriginal('course_start_at')) : null;
                }
                $releaseDate = $lesson->releasePlan(
                    $courseStartDate,
                    $item['user_id'],
                    $item['data']['course_id']
                );
                // Only show lock/unlock if releaseDate is in the future
                if ($releaseDate) {
                    $releaseDateObj = \App\Helpers\DateHelper::parse($releaseDate);
                    $now = \Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone())->startOfDay();
                    if ($releaseDateObj && $releaseDateObj->greaterThan($now)) {
                        $isUnlocked = LessonUnlock::isUnlockedForUser($lesson->id, $item['user_id'], $item['data']['course_id']);
                        $statusLabel = $isUnlocked
                            ? '<span class="text-success me-2">UNLOCKED</span>'
                            : '<span class="text-danger me-2">LOCKED</span>';
                        $output .= "<span>Lesson is {$statusLabel}";
                        if ($isUnlocked) {
                            $output .= '<button class="btn btn-sm btn-warning" onclick="LMS.LockLesson(' . $lesson->id . ',' . $item['user_id'] . ')" title="Lock this lesson">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Lock</button>';
                        } else {
                            $output .= '<button class="btn btn-sm btn-success" onclick="LMS.UnlockLesson(' . $lesson->id . ',' . $item['user_id'] . ')" title="Unlock this lesson">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4M17 16a2 2 0 1 1-4 0"/></svg>
                                        Unlock</button>';
                        }
                        $output .= '</span>';
                    }
                }
            }
        }

        return $output;
    }

    protected function renderWorkPlacementSection($item, $student, $id): string
    {
        $attachment = StudentLMSAttachables::forEvent('WORK_PLACEMENT')
            ->forAttachable(Lesson::class, $item['data']['id'])
            ->where('student_id', $student->id)?->first();
        if (!empty($attachment)) {
            return '<span class="fw-bold text-primary ms-2 me-2">Work Placement Completed</span>';
        }

        return $this->generateButton('Mark Work Placement Completed', "LMS.MarkWorkPlacement({$id}, {$item['user_id']})", 'btn-primary d-flex align-items-start me-2', $item, $student, $id);
    }

    protected function renderCompetencySection($item, $student, $lessonData, $lessonCompetentReady, $id): string
    {
        if (($lessonCompetentReady || $item['topic_count'] === 0 || $item['quiz_count'] === 0) && $item['status'] === 'COMPLETED') {
            if (is_array($item['competency']) && $item['competency']['is_competent']) {
                return $this->generateCompetencyBadge($item);
            } else {
                return $this->generateCompetencyButton($item, $student, $lessonData, $id);
            }
        }

        return '';
    }

    protected function generateCompetencyBadge($item): string
    {
        $competentDate = $this->formatDate($item['competency']['competent_on'], 'j F, Y');
        $userName = $item['competency']['notes']['added_by']['user_name'] ?? 'Unknown';

        //        if($item['data']['id'] === 100633){
        //            Helper::debug([
        //                'competent_on' =>  $item[ 'competency' ][ 'competent_on' ],
        //                'competency' => $competentDate,
        //                'user_id' => $this->user_id,
        //                'lesson_id' => $item['data']['id']
        //            ],'dd');
        //        }
        return '<div class="d-flex">' .
            '<span class="border-light mx-1"></span>' .
            '<span class="fw-normal ms-1 me-1">' .
            '<span class="fw-bold text-purple">Marked Competent:</span> ' .
            $competentDate .
            ' ( ' . $userName . ' )</span>' .
            '</div>';
    }

    protected function generateCompetencyButton($item, $student, $lessonData, $id): string
    {
        // Get start date using the new method
        $lessonStartDate = $this->getLessonStartDate($item, $student);
        $formattedStartDate = $this->formatDate($lessonStartDate, 'd-m-Y');

        // Get end date using the same method as the accordion display
        $lessonEndDate = StudentCourseService::lessonEndDate($student->id, $item['data']['id'], true);
        $formattedEndDate = $lessonEndDate;

        //        if($id === 100846) {
        //            Helper::debug( [
        //                'lessonStartDate' => $lessonStartDate,
        //                'formattedStartDate' => $formattedStartDate,
        //                'lessonEndDate' => $lessonEndDate,
        //                'formattedEndDate' => $formattedEndDate,
        //                'student_id' => $student->id,
        //                'user_id' => $this->user_id,
        //                'lesson_id' => $item[ 'data' ][ 'id' ],
        //                'id' => $id,
        //                'minDate' => Carbon::parse( $formattedEndDate )->lessThan( '1-1-2025' ) ? '2025-1-1' : $formattedEndDate
        //            ],'dd' );
        //        }
        // If dates couldn't be parsed, use current date as fallback
        if (empty($formattedStartDate)) {
            $formattedStartDate = $now = \Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone())->format('d-m-Y');
            \Log::warning('Using current date as fallback for start date', [
                'student_id' => $student->id,
                'item' => $item['data'],
                'original_start_date' => $lessonStartDate,
                'user_id' => $this->user_id,
                'lesson_id' => $item['data']['id'],
            ]);
        }

        if (empty($formattedEndDate)) {
            $formattedEndDate = \Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone())->format('d-m-Y');
            \Log::warning('Using current date as fallback for end date', [
                'student_id' => $student->id,
                'item' => $item['data'],
                'original_end_date' => $lessonEndDate,
                'user_id' => $this->user_id,
                'lesson_id' => $item['data']['id'],
            ]);
        }

        // Use the lesson end date as minDate, or 2025-01-01 as fallback
        if ($lessonEndDate) {
            $minDate = $lessonEndDate;
        } else {
            $minDate = '2025-01-01';
        }

        // Format dates in user's local timezone using Y-m-d format for JavaScript compatibility
        // This ensures consistent date handling between PHP and JavaScript
        $formattedStartDateYmd = $this->formatDate($lessonStartDate, 'Y-m-d');

        // lessonEndDate is now returned as raw database value (Y-m-d format), just ensure proper formatting
        $formattedEndDateYmd = $lessonEndDate ? Carbon::parse($lessonEndDate)->timezone(Helper::getTimeZone())->format('Y-m-d') : null;
        $formattedMinDateYmd = Carbon::parse($minDate)->format('Y-m-d');

        $data = [
            'lessonID' => $id,
            'studentID' => $item['user_id'],
            'title' => $lessonData->title ?? '',
            'start_date' => $formattedStartDateYmd,
            'end_date' => $formattedEndDateYmd,
            'min_date' => $formattedMinDateYmd,
        ];

        $dataStr = json_encode($data);

        return $this->generateButton('Mark Lesson Competent', "LMS.ShowLessonCompetent(JSON.stringify({$dataStr}))", 'btn-purple', $item, $student, $id);
    }

    protected function quizAddons($item, $student, $id)
    {
        $output = '';
        if (auth()->user()->can('upload checklist') && !empty($item['data']) && intval($item['data']['has_checklist'] ?? 0) === 1) {
            $checklist = StudentLMSAttachables::forEvent('CHECKLIST')
                ->forAttachable(Quiz::class, $item['data']['id'])
                ->where('student_id', $student->id)
                ->get();
            $checkListCount = count($checklist);

            $notSatisfactory = 0;
            $output .= '<div class="col-12 mb-2"><div class="d-flex flex-wrap align-items-center">';
            $output .= '<span class="fw-bold me-1" data-count="' . $checkListCount . '">Checklist(s):</span>';

            if (!empty($checklist)) {
                $output .= '<div class="col-12 col-md-8">';
                $count = 1;
                foreach ($checklist as $checkListItem) {
                    $status = $checkListItem->properties['status'] ?? 'N/A';
                    if ($status === 'NOT SATISFACTORY') {
                        $notSatisfactory++;
                    }
                    $color = ($status === 'SATISFACTORY' ? 'success' : ($status === 'NOT SATISFACTORY' ? 'danger' : 'dark'));
                    $output .= '<p class="fw-bold text-primary ms-2 me-2"><a href="' . Storage::url($checkListItem->properties['file']['destination']) . '" download="' . ($checkListItem->properties['file']['name'] ?? 'Obs_Checklist') . '">Obs Checklist #' . $count
                        . '</a> <span class="fw-normal text-' . $color . ' ms-2">' . $status . '</span><span class="fw-normal text-dark ms-2">'
                        . Carbon::parse($checkListItem->created_at)->timezone(Helper::getTimeZone())->format('j F, Y') . '</span></p>';
                    $count++;
                }
                $output .= '</div>';
            }

            if ($notSatisfactory === 3) {
                $output .= '<p class="fw-bold text-danger">FAILED</p>';
            }

            if (empty($checklist) || $checkListCount < 3) {
                $quiz = Quiz::with('topic.lesson.course')->find($item['data']['id']);
                $courseId = $quiz->topic->lesson->course->id ?? 0;

                if ($courseId) {
                    $output .= '<div class="col-12 col-md-4 mt-2 mt-md-0">';
                    $output .= '<div class="d-flex flex-column">';
                    $output .= '<input class="form-control mb-2" type="file" name="checklist_' . $courseId . '_' . $id . '" id="checklist_' . $courseId . '_' . $id . '"
                        data-format="pdf|doc|docx|zip|jpg|jpeg|xls|xlsx|ppt|pptx|png" accept=".pdf,.doc,.docx,.zip,.jpg,.jpeg,.xls,.xlsx,.ppt,.pptx,.png" />';
                    $output .= '<div class="d-flex align-items-center mb-2">';
                    $output .= '<label class="form-label fw-bold font-small-4 py-1 pe-1 mb-0">Status: </label>
                        <select data-placeholder="Status" class="form-select me-2" id="checklist_' . $courseId . '_' . $id . '_status" name="status">
                            <option></option><option value="SATISFACTORY">SATISFACTORY</option><option value="NOT SATISFACTORY">NOT SATISFACTORY</option>
                        </select>';
                    $output .= '</div>';
                    $output .= '<button class="btn btn-primary btn-sm" onclick="LMS.UploadChecklist(' . $item['data']['id'] . ', \'checklist_' . $courseId . '_' . $id . '\',' . $item['user_id'] . ')">Upload Obs Checklist</button>';
                    $output .= '</div></div>';
                }
            }

            $output .= '</div></div>';
        }

        return $output;
    }

    protected function topicAddons($item, $studentActive, $id)
    {
        $studentActive = true;
        $output = '';
        if (auth()->user()->can('mark complete') && $item['type'] === 'topic' && $item['status'] !== 'COMPLETED' && $studentActive) {
            $output .= '<div class="d-flex flex-row">
                <button class="btn btn-success btn-sm d-flex align-items-end" onclick="LMS.MarkTopicComplete(' . $id . ',' . $item['user_id'] . ')">Mark Topic Complete</button>
            </div>';
        }

        return $output;
    }

    public function getTotalCounts($progress, $course_id = null): array
    {
        $courseCompleted = false;
        $processed = 0;
        $completed = 0;
        $total = 0;
        $passed = 0;
        $failed = 0;
        $submitted = 0;
        $empty = 0;

        // dump('=== StudentTrainingPlanService getTotalCounts START ===');
        // dump('Progress data keys: ' . json_encode(array_keys($progress)));

        // Check if this is a main course for LLND logic
        $isMainCourse = false;
        if (isset($progress['course'])) {
            $course = $progress['course'];
            if (is_int($course) || is_string($course)) {
                $course = \App\Models\Course::find($course);
            }
            if ($course) {
                $isMainCourse = $course->is_main_course == 1 || !str_contains(strtolower($course->title), 'semester 2');
                // dump("StudentTrainingPlanService Course check - ID: {$course->id}, Title: {$course->title}, is_main_course: {$course->is_main_course}, isMainCourse: " . ($isMainCourse ? 'true' : 'false'));
            }
        } elseif ($course_id) {
            $course = \App\Models\Course::find($course_id);
            if ($course) {
                $isMainCourse = $course->is_main_course == 1 || !str_contains(strtolower($course->title), 'semester 2');
                // dump("StudentTrainingPlanService Course check (course_id) - ID: {$course->id}, Title: {$course->title}, is_main_course: {$course->is_main_course}, isMainCourse: " . ($isMainCourse ? 'true' : 'false'));
            }
        }

        if (!empty($progress['lessons']) && !empty($progress['lessons']['list'])) {
            $lessonIndex = 0;
            foreach ($progress['lessons']['list'] as $lesson) {
                if (!empty($lesson)) {
                    $total++;
                }

                $isFirstLesson = $lessonIndex === 0;
                $lessonIndex++;

                if ($lesson['completed'] || (isset($lesson['marked']) && $lesson['marked'])) {
                    $completed++;
                    $processed++;
                } elseif (!empty($lesson['submitted']) || !empty($lesson['attempted'])) {
                    $processed++;
                }
                if ($lesson['topics']['count'] === 0) {
                    $empty++;
                }
                if (!empty($lesson['topics']['list'])) {
                    $topicIndex = 0;
                    foreach ($lesson['topics']['list'] as $topic) {
                        if (!empty($topic)) {
                            $total++;
                        }

                        $isFirstTopic = $isFirstLesson && $topicIndex === 0;
                        $topicIndex++;

                        if ($topic['completed'] || (isset($topic['marked']) && $topic['marked'])) {
                            $completed++;
                            $processed++;
                        } elseif (!empty($topic['submitted']) || !empty($topic['attempted'])) {
                            $processed++;
                        }

                        if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                            $quizIndex = 0;
                            $originalQuizCount = $topic['quizzes']['count'];
                            $originalPassed = $topic['quizzes']['passed'];
                            $originalFailed = $topic['quizzes']['failed'] ?? 0;

                            // Check if this is the first quiz of first topic of first lesson
                            $isFirstQuiz = $isFirstTopic && $quizIndex === 0;

                            // Check if first quiz is already passed (old registration)
                            $firstQuizAlreadyPassed = false;
                            if ($isFirstQuiz && !empty($topic['quizzes']['list'])) {
                                $firstQuiz = reset($topic['quizzes']['list']);
                                $firstQuizAlreadyPassed = !empty($firstQuiz['status']) && $firstQuiz['status'] === 'SATISFACTORY';
                                // dump("StudentTrainingPlanService First quiz status: " . ($firstQuiz['status'] ?? 'null') . ", alreadyPassed: " . ($firstQuizAlreadyPassed ? 'true' : 'false'));
                            }

                            // Apply LLND logic only for main courses and when first quiz is not already passed
                            if ($isMainCourse && $isFirstQuiz && !$firstQuizAlreadyPassed) {
                                // dump("StudentTrainingPlanService LLND Logic Applied - Quiz ID: " . config('lln.quiz_id') . ", User ID: " . ($progress['user_id'] ?? $this->user_id ?? 0));
                                $llnQuizId = config('lln.quiz_id');
                                $userId = $progress['user_id'] ?? $this->user_id ?? 0;

                                // Get LLND quiz attempts
                                $llnAttempts = \App\Models\QuizAttempt::where('user_id', $userId)
                                    ->where('quiz_id', $llnQuizId)
                                    ->get();

                                $llnSubmitted = $llnAttempts->count();
                                $llnPassed = $llnAttempts->where('status', 'SATISFACTORY')->count();
                                $llnFailed = $llnAttempts->whereIn('status', ['FAIL', 'RETURNED'])->count();

                                // Replace first quiz counts with LLND counts
                                if ($llnSubmitted > 0) {
                                    $total += 1; // Add LLND quiz to total
                                    $submitted += $llnSubmitted;
                                    $processed += $llnSubmitted;

                                    if ($llnPassed > 0) {
                                        $passed += 1;
                                        // dump("After LLND quiz (+1): completed = " . ($completed + 1));
                                        $completed += 1; // LLND quiz
                                        // dump("After first topic (+1): completed = " . ($completed + 1));
                                        $completed += 1; // First topic (marked as completed)
                                        $processed += 1; // First topic (marked as completed)

                                        // Mark first topic as completed (always)
                                        $topic['completed'] = true;
                                        $topic['marked'] = true;

                                        // Only mark lesson as completed if it has only 1 topic
                                        if ($lesson['topics']['count'] === 1) {
                                            // dump("After first lesson (+1): completed = " . ($completed + 1));
                                            $completed += 1; // First lesson (marked as completed)
                                            $processed += 1; // First lesson (marked as completed)
                                            $lesson['completed'] = true;
                                            $lesson['marked'] = true;
                                        }
                                    } elseif ($llnFailed > 0) {
                                        $failed += 1;
                                    }
                                } else {
                                    // No LLND attempts, use original quiz counts
                                    $total += $originalQuizCount;
                                    $passed += $originalPassed;
                                    $failed += $originalFailed;
                                    $completed += $originalPassed;
                                }

                                // Process remaining quizzes (skip first quiz)
                                foreach ($topic['quizzes']['list'] as $quizIndex => $quiz) {
                                    if ($quizIndex === 0) {
                                        continue;
                                    } // Skip first quiz (replaced by LLND)

                                    if (!empty($quiz['submitted'])) {
                                        $processed++;
                                        $submitted++;
                                    } elseif (!empty($quiz['attempted'])) {
                                        $processed++;
                                    }
                                }
                            } else {
                                // Normal quiz processing (not first quiz or not main course)
                                $total += $originalQuizCount;
                                $passed += $originalPassed;
                                $failed += $originalFailed;
                                $completed += $originalPassed;

                                foreach ($topic['quizzes']['list'] as $quiz) {
                                    if (!empty($quiz['submitted'])) {
                                        $processed++;
                                        $submitted++;
                                    } elseif (!empty($quiz['attempted'])) {
                                        $processed++;
                                    }
                                }
                            }
                        } else {
                            $empty++;
                        }
                    }
                }
            }
        }

        if (!empty($progress['completed'])) {
            $courseCompleted = true;
        }

        $return = [
            'passed' => $completed,
            'failed' => $failed,
            'processed' => $processed,
            'attempted' => $processed,
            'submitted' => $submitted,
            'total' => $total,
            'quizzes_passed' => $passed,
            'quizzes_failed' => $failed,
            'course_completed' => $courseCompleted,
            'empty' => $empty,
        ];

        // dump("=== StudentTrainingPlanService getTotalCounts FINAL RESULTS ===");
        // dump("Final counts - total: {$total}, passed: {$passed}, failed: {$failed}, processed: {$processed}, submitted: {$submitted}, completed: {$completed}");
        // dump("Return array: " . json_encode($return));

        // dd($return);
        return $return;
    }

    protected function getProgress($user_id, $course_id)
    {
        return CourseProgress::where('user_id', $user_id)
            ->where('course_id', $course_id)
            ->where('course_id', '!=', config('constants.precourse_quiz_id', 0))
            ->first();
    }

    public function populateProgress($course_id)
    {
        $course = Course::with('lessons')->where('id', $course_id)->first();
        $progress = ['course' => $course_id, 'completed' => false, 'at' => null];

        if (empty($course)) {
            return $progress;
        }

        $progress['lessons'] = [
            'passed' => 0,
            'count' => 0,
            'submitted' => 0,
            'list' => [],
        ];

        $previousLesson = 0;
        $previousTopic = 0;
        $previousQuiz = 0;

        if (!empty($course->lessons)) {
            $lessons = $course->lessons()->with('topics')->orderBy('order')->get();
            foreach ($lessons as $lesson) {
                $progress['lessons']['count']++;
                $progress['lessons']['submitted'] = 0;
                $progress['lessons']['list'][$lesson->id] = [
                    'completed' => false,
                    'submitted' => false,
                    'at' => null,
                    'completed_at' => null,
                    'submitted_at' => null,
                    'marked_at' => null,
                    'lesson_end_at' => null,
                    'previous' => $previousLesson,
                ];
                $previousLesson = $lesson->id;
                $progress['lessons']['list'][$lesson->id]['topics'] = [
                    'passed' => 0,
                    'count' => 0,
                    'submitted' => 0,
                    'list' => [],
                ];
            }
        }

        return $progress;
    }

    protected function getCleanProgressDetails($existingProgress, $newProgress)
    {
        if (empty($existingProgress)) {
            return $newProgress;
        }

        if (empty($newProgress)) {
            return $existingProgress;
        }

        $result = $newProgress;

        if (!empty($existingProgress['lessons']['list'])) {
            foreach ($existingProgress['lessons']['list'] as $lesson_id => $lesson) {
                if (isset($result['lessons']['list'][$lesson_id])) {
                    $result['lessons']['list'][$lesson_id] = array_merge(
                        $result['lessons']['list'][$lesson_id],
                        $lesson
                    );
                }
            }
        }

        return $result;
    }

    protected function reEvaluateProgress($user_id, $progress)
    {
        if (empty($progress) || empty($progress['course']) || empty($progress['lessons'])) {
            return false;
        }

        if (empty($user_id)) {
            return false;
        }

        $course = Course::find($progress['course']);
        if (empty($course)) {
            return false;
        }

        $lessons = $course->lessons()->with(['topics.quizzes.attempts' => function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        }])->get();

        foreach ($lessons as $lesson) {
            if (isset($progress['lessons']['list'][$lesson->id])) {
                $lessonProgress = &$progress['lessons']['list'][$lesson->id];

                // Update lesson progress based on topics and quizzes
                $this->updateLessonProgress($lessonProgress, $lesson, $user_id);
            }
        }

        return $progress;
    }

    protected function updateAdminReportProgress($user_id, $course_id)
    {
        $adminReportService = new AdminReportService($user_id, $course_id);

        return $adminReportService->updateProgress();
    }

    protected function updateProgressSession($courseProgress)
    {
        if (empty($courseProgress)) {
            return;
        }

        $courseProgress->load(['user', 'course.lessons.topics.quizzes.attempts' => function ($query) {
            $query->where('user_id', $this->user_id)->latestThreeAttempts()->orderBy('created_at', 'ASC');
        }]);

        return $courseProgress;
    }

    protected function updateLessonProgress(&$lessonProgress, $lesson, $user_id)
    {
        $allTopicsCompleted = true;
        $hasTopics = false;

        foreach ($lesson->topics as $topic) {
            if (isset($lessonProgress['topics']['list'][$topic->id])) {
                $hasTopics = true;
                $topicProgress = &$lessonProgress['topics']['list'][$topic->id];

                // Update topic progress based on quizzes
                $this->updateTopicProgress($topicProgress, $topic, $user_id);

                if (!$topicProgress['completed']) {
                    $allTopicsCompleted = false;
                }
            }
        }

        // Update lesson completion status
        if ($hasTopics && $allTopicsCompleted) {
            $lessonProgress['completed'] = true;
            $lessonProgress['completed_at'] = $this->formatDateForDatabase(\Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone()));
        }
    }

    protected function updateTopicProgress(&$topicProgress, $topic, $user_id)
    {
        $allQuizzesPassed = true;
        $hasQuizzes = false;

        foreach ($topic->quizzes as $quiz) {
            if (isset($topicProgress['quizzes']['list'][$quiz->id])) {
                $hasQuizzes = true;
                $quizProgress = &$topicProgress['quizzes']['list'][$quiz->id];

                // Check quiz attempts
                $latestAttempt = $quiz->attempts()
                    ->where('user_id', $user_id)
                    ->latest()
                    ->first();

                if ($latestAttempt && $latestAttempt->status === 'SATISFACTORY') {
                    $quizProgress['passed'] = true;
                    $quizProgress['completed_at'] = $this->formatDateForDatabase($latestAttempt->submitted_at);
                } else {
                    $allQuizzesPassed = false;
                }
            }
        }

        // Update topic completion status
        if ($hasQuizzes && $allQuizzesPassed) {
            $topicProgress['completed'] = true;
            $topicProgress['completed_at'] = $this->formatDateForDatabase(\Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone()));
        }
    }

    protected function isValidProgress($progress): bool
    {
        if (!$progress || !$progress->course) {
            \Log::warning('Invalid progress or missing course relationship', [
                'progress_id' => $progress ? $progress->id : null,
                'user_id' => $this->user_id,
            ]);

            return false;
        }

        if (intval($progress->course_id) === intval(config('lln.course_id')) || intval($progress->course_id) === intval(config('ptr.course_id'))) {
            return false;
        }

        $enrolment = StudentCourseEnrolment::where('user_id', $this->user_id)
            ->where('course_id', $progress->course_id)
            ->select(['id', 'user_id', 'course_id', 'status'])
            ->first();

        if (empty($enrolment)) {
            if (intval(config('lln.course_id')) !== intval($progress->course_id)
                && intval(config('ptr.course_id')) !== intval($progress->course_id)) {
                $this->reCalculateProgress($progress->course_id);
                \Log::warning('No enrolment found for course', [
                    'progress_id' => $progress->id,
                    'user_id' => $this->user_id,
                    'course_id' => $progress->course_id,
                ]);
            }

            return false;
        }

        return $enrolment->status !== 'DELIST' && !empty($progress->course_id);
    }

    public function getProgressDetails(CourseProgress $progress, int $course_id): array
    {
        try {
            if (empty($progress) || empty($course_id)) {
                throw new \InvalidArgumentException('Progress and course ID are required');
            }

            $details = $progress->details ? $progress->details->toArray() : [];

            if (empty($details['data'])) {
                try {
                    $newProgress = $this->reCalculateProgress($course_id);
                    if (empty($newProgress)) {
                        throw new \RuntimeException('Failed to recalculate progress');
                    }
                    $details = $this->attachModels($newProgress->details->toArray());
                } catch (\Exception $e) {
                    \Log::error('Error recalculating progress', [
                        'user_id' => $this->user_id,
                        'course_id' => $course_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }
            }

            return $details;
        } catch (\Exception $e) {
            \Log::error('Error getting progress details', [
                'user_id' => $this->user_id,
                'course_id' => $course_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function buildCourseResponse(CourseProgress $progress, array $details, array $percentageData, $enrolments = []): array
    {
        $allLessonsCompetent = true;
        $hasLessons = false;

        if (!empty($details['lessons']['list'])) {
            $hasLessons = true;
            foreach ($details['lessons']['list'] as $lesson) {
                // Skip lessons that are not completed
                if (!boolval($lesson['completed'] ?? false)) {
                    $allLessonsCompetent = false;

                    break;
                }

                // Get competency status from Competency model
                $competency = StudentCourseService::getCompetency($this->user_id, $lesson['data']['id']);
                if (!$competency || !$competency->is_competent) {
                    $allLessonsCompetent = false;

                    break;
                }
            }
        }

        // Only set allLessonsCompetent to true if there are lessons and all are competent
        $allLessonsCompetent = $hasLessons && $allLessonsCompetent;

        return [
            'user_id' => $this->user_id,
            'type' => 'course',
            'title' => $details['data']['title'] ?? '',
            'link' => route('lms.courses.show', $details['data']['id'] ?? ''),
            'stats' => [
                'completed' => boolval($details['completed'] ?? false),
                'all_lessons_competent' => $allLessonsCompetent,
            ],
            'status' => $this->getStatus($details),
            'percentage' => $this->calculatePercentage($percentageData, $this->user_id, $progress->course_id),
            'expected_percentage' => $this->expectedPercentage($progress->course_id, $progress->percentage, $enrolments),
        ];
    }

    // app/Services/StudentTrainingPlanService.php

    protected function processLessons(CourseProgress $progress, array $details, int $course_id, array $enrolments = []): array
    {
        try {
            if (empty($progress) || empty($details) || empty($course_id)) {
                throw new \InvalidArgumentException('Progress, details, and course ID are required');
            }

            // Fetch LLN statuses on-demand if this is the main course and LLND is not excluded. This is more performant
            // than updating the CourseProgress JSON on every page load.
            $llnStatuses = [
                'old' => null,
                'new' => null,
            ];
            $isMainCourse = $progress->course && $progress->course->is_main_course;

            // Check if LLND should be excluded for this course (using already loaded course data)
            $excludeLLND = $progress->course ? \App\Helpers\Helper::isLLNDExcluded($progress->course->category) : false;

            if ($isMainCourse && !$excludeLLND) {
                // Fetch latest old LLN attempt status
                $oldLlnQuizId = config('constants.precourse_quiz_id', 0);
                $oldLlnAttempt = QuizAttempt::where('user_id', $this->user_id)
                    ->where('quiz_id', $oldLlnQuizId)
                    ->latest('id')
                    ->first();
                $llnStatuses['old'] = $oldLlnAttempt ? $oldLlnAttempt->status : null;

                // Fetch latest new LLN attempt status
                $newLlnQuizId = config('lln.quiz_id');
                $newLlnAttempt = QuizAttempt::where('user_id', $this->user_id)
                    ->where('quiz_id', $newLlnQuizId)
                    ->latest('id')
                    ->first();
                $llnStatuses['new'] = $newLlnAttempt ? $newLlnAttempt->status : null;
            }

            $lessons = [];
            $enrolment = $enrolments[$course_id] ?? null;
            foreach ($details['lessons']['list'] as $lesson_id => $lesson) {
                try {
                    $lesson_id = (int)$lesson_id;
                    $lessonData = $progress->course->lessons->firstWhere('id', $lesson_id)?->toArray();

                    if (empty($lessonData)) {
                        \Log::warning('Missing Lesson', [
                            'user_id' => $this->user_id,
                            'lesson_id' => $lesson_id,
                            'data' => $lessonData,
                        ]);

                        continue;
                    }

                    // Inject the fetched LLN statuses for the first lesson of the main course (only if LLND not excluded)
                    if ($isMainCourse && !$excludeLLND && isset($lessonData['order']) && (int)$lessonData['order'] === 0) {
                        $lesson['lln_old_status'] = $llnStatuses['old'] ?? null;
                        $lesson['lln_new_status'] = $llnStatuses['new'] ?? null;
                    }

                    $competency = StudentCourseService::getCompetency($this->user_id, $lesson_id);
                    $LLNLessonComplete = $this->isLLNLessonComplete($lessonData, $lesson, $isMainCourse, $progress->course);
                    $lesson['data'] = $lessonData;
                    // Inject full enrolment for this lesson
                    $lesson['enrolment'] = $enrolment;
                    $lessons[$lesson_id] = $this->buildLessonResponse($lesson, $competency, $LLNLessonComplete, $llnStatuses, $progress->course);
                    $llnLesson = [
                        'is_lln_lesson' => $lessons[$lesson_id]['is_lln_lesson'] ?? false,
                        'lln_old_status' => $lessons[$lesson_id]['lln_old_status'] ?? null,
                        'lln_new_status' => $lessons[$lesson_id]['lln_new_status'] ?? null,
                    ];

                    // Process topics if lesson is configured to have topics, regardless of progress details
                    if (($lessonData['has_topic'] > 0)) {
                        $lessons[$lesson_id]['children'] = $this->processTopics($progress, $lesson, $lesson_id, $course_id, $llnLesson);

                        // Recalculate counts after processing topics
                        $processedTopics = $lessons[$lesson_id]['children'];
                        $lessons[$lesson_id]['topic_count'] = count($processedTopics);

                        // Count total quizzes across all processed topics
                        $totalQuizCount = 0;
                        foreach ($processedTopics as $topic) {
                            if (isset($topic['quizzes']) && is_array($topic['quizzes'])) {
                                $totalQuizCount += count($topic['quizzes']);
                            }
                        }
                        $lessons[$lesson_id]['quiz_count'] = $totalQuizCount;
                    } else {
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing lesson', [
                        'user_id' => $this->user_id,
                        'lesson_id' => $lesson_id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    continue;
                }
            }

            return $lessons;
        } catch (\Exception $e) {
            \Log::error('Error processing lessons', [
                'user_id' => $this->user_id,
                'course_id' => $course_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function isLLNLessonComplete(array $lessonData, array $lesson, $isMainCourse, $course = null): bool
    {
        // Only for main course and first lesson (order = 0)
        if (!empty($lesson) && $isMainCourse) {
            // Check if LLND should be excluded for this lesson's course (using passed course data)
            $excludeLLND = $course ? \App\Helpers\Helper::isLLNDExcluded($course->category) : false;

            // Only apply LLND logic if not excluded
            if (!$excludeLLND) {
                // Check for old or new LLND completion (marked satisfactory)
                $llnOldSatisfactory = !empty($lesson['lln_old_status']) && $lesson['lln_old_status'] === 'SATISFACTORY';
                $llnNewSatisfactory = !empty($lesson['lln_new_status']) && $lesson['lln_new_status'] === 'SATISFACTORY';
                if ($llnOldSatisfactory || $llnNewSatisfactory) {
                    return true;
                }
            }
        }

        // Fallback to current logic
        return !empty($lessonData) &&
            (int)$lessonData['order'] === 0 &&
            boolval($lesson['marked_at'] ?? $lesson['completed'] ?? false);
    }

    protected function buildLessonResponse(array $lesson, mixed $competency, bool $LLNLessonComplete, $llnStatuses, $course = null): array
    {
        $checklistStatus = '';
        $hasChecklistQuizzes = false;

        // Check if LLND should be excluded for this lesson's course (using passed course data)
        $excludeLLND = $course ? \App\Helpers\Helper::isLLNDExcluded($course->category) : false;

        // Check if lesson has topics with quizzes that have checklists
        if (!empty($lesson['data']['has_topic'])) {
            foreach ($lesson['topics']['list'] ?? [] as $topic) {
                foreach ($topic['quizzes']['list'] ?? [] as $quiz) {
                    if (!empty($quiz['data']['has_checklist']) && $quiz['data']['has_checklist'] == 1) {
                        $hasChecklistQuizzes = true;

                        break 2;
                    }
                }
            }
        }

        if ($hasChecklistQuizzes) {
            $checklistStatus = $this->getLessonChecklistStatus($lesson);
        }

        $lessonTopics = $lesson['data']['topics'] ?? [];
        unset($lesson['data']['topics']);

        // Calculate actual counts from the lesson data
        $actualTopicCount = count($lessonTopics);
        $actualQuizCount = 0;

        // Count quizzes from all topics
        foreach ($lessonTopics as $topic) {
            if (isset($topic['quizzes']) && is_array($topic['quizzes'])) {
                $actualQuizCount += count($topic['quizzes']);
            }
        }

        return [
            'user_id' => $this->user_id,
            'type' => 'lesson',
            'title' => $lesson['data']['title'],
            'link' => route('lms.lessons.show', $lesson['data']['id']),
            'stats' => [
                'completed' => $LLNLessonComplete || boolval($lesson['completed'] ?? false),
                'submitted' => boolval($lesson['submitted'] ?? false),
                'is_marked_complete' => boolval($lesson['marked_at'] ?? false),
            ],
            'marked_at' => $lesson['marked_at'] ?? '',
            'competency' => $competency ? $competency->toArray() : false,
            'status' => $LLNLessonComplete ? 'COMPLETED' : $this->getStatus($lesson),
            'data' => $lesson['data'] ?? [],
            'topic_count' => $actualTopicCount,
            'quiz_count' => $actualQuizCount,
            'evidence' => ['status' => 'NOT COMPLETED'],
            'has_checklist_quizzes' => $hasChecklistQuizzes,
            'checklist' => $checklistStatus,
            'has_work_placement' => !empty($lesson['data']['has_work_placement']) && $lesson['data']['has_work_placement'] == 1,
            'is_lln_lesson' => !$excludeLLND && $LLNLessonComplete,
            'lln_old_status' => $llnStatuses['old'] ?? null,
            'lln_new_status' => $llnStatuses['new'] ?? null,
        ];
    }

    protected function getLessonChecklistStatus(array $lesson): array
    {
        $status = 'NOT ATTEMPTED';
        $hasChecklist = false;
        $allSatisfactory = true;
        $notSatisfactoryCount = 0;

        if (!empty($lesson['topics']['list'])) {
            foreach ($lesson['topics']['list'] as $topic) {
                if (!empty($topic['quizzes']['list'])) {
                    foreach ($topic['quizzes']['list'] as $quiz) {
                        if (!empty($quiz['data']['has_checklist'])) {
                            $hasChecklist = true;
                            $checklist = StudentLMSAttachables::forEvent('CHECKLIST')
                                ->forAttachable(Quiz::class, $quiz['data']['id'])
                                ->where('student_id', $this->user_id)
                                ->get();

                            if ($checklist->isEmpty()) {
                                $allSatisfactory = false;
                            } else {
                                foreach ($checklist as $checkListItem) {
                                    if (($checkListItem->properties['status'] ?? '') === 'NOT SATISFACTORY') {
                                        $notSatisfactoryCount++;
                                        $allSatisfactory = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($hasChecklist) {
            if ($notSatisfactoryCount >= 3) {
                $status = 'FAILED';
            } elseif ($allSatisfactory) {
                $status = 'COMPLETED';
            } else {
                $status = 'NOT ATTEMPTED';
            }
        }

        return [
            'status' => $status,
            'has_checklist' => $hasChecklist,
        ];
    }

    protected function processTopics(CourseProgress $progress, array $lesson, int $lesson_id, int $course_id, array $llnLesson): array
    {
        try {
            if (empty($progress) || empty($lesson) || empty($lesson_id) || empty($course_id)) {
                throw new \InvalidArgumentException('Progress, lesson, lesson ID, and course ID are required');
            }

            // Determine if this is an LLN lesson based on lesson data and course category
            $isLLNLesson = false;
            $excludeLLND = $progress->course ? \App\Helpers\Helper::isLLNDExcluded($progress->course->category) : false;

            if (!$excludeLLND && $progress->course->is_main_course) {
                // Check if this is the first lesson (order = 0) which is typically the LLN lesson
                $lessonData = $progress->course->lessons->firstWhere('id', $lesson_id);
                $isLLNLesson = $lessonData && (int)$lessonData->order === 0;
            }

            $topics = [];
            $topicCount = 0;

            // If progress details don't contain topics, fetch them from the database
            if (empty($lesson['topics']['list']) || $lesson['topics']['count'] == 0) {
                // Fetch topics directly from the database
                $lessonModel = $progress->course->lessons->firstWhere('id', $lesson_id);
                if ($lessonModel && $lessonModel->topics) {
                    $dbTopics = $lessonModel->topics()->with('quizzes')->orderBy('order')->get();
                    foreach ($dbTopics as $dbTopic) {
                        $lesson['topics']['list'][$dbTopic->id] = [
                            'completed' => false,
                            'submitted' => false,
                            'at' => null,
                            'completed_at' => null,
                            'submitted_at' => null,
                            'marked_at' => null,
                            'quizzes' => [
                                'passed' => 0,
                                'count' => 0,
                                'submitted' => 0,
                                'list' => [],
                            ],
                        ];

                        // Add quizzes if they exist
                        if ($dbTopic->quizzes) {
                            foreach ($dbTopic->quizzes as $dbQuiz) {
                                $lesson['topics']['list'][$dbTopic->id]['quizzes']['list'][$dbQuiz->id] = [
                                    'passed' => false,
                                    'failed' => false,
                                    'submitted' => false,
                                    'at' => null,
                                    'marked_at' => null,
                                    'passed_at' => null,
                                    'failed_at' => null,
                                    'submitted_at' => null,
                                ];
                                $lesson['topics']['list'][$dbTopic->id]['quizzes']['count']++;
                            }
                        }
                    }
                    $lesson['topics']['count'] = count($dbTopics);
                }
            }

            $totalTopics = (int)$lesson['topics']['count'];

            foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                try {
                    $topicData = $progress->course->lessons->firstWhere('id', $lesson_id)
                        ->topics->firstWhere('id', $topic_id)?->toArray();

                    if (empty($topicData)) {
                        \Log::warning('Missing Topic', [
                            'user_id' => $this->user_id,
                            'topic_id' => $topic_id,
                            'data' => $topicData,
                        ]);

                        continue;
                    }

                    $topic['data'] = $topicData;
                    $topics[$topic_id] = $this->buildTopicResponse($topic, $llnLesson, $progress->course);

                    if (!empty($topic['quizzes']['list']) && ($topic['quizzes']['count'] > 0 || $topicData['has_quiz'] > 0)) {
                        // Pass the LLN lesson information to processQuizzes
                        $llnLessonInfo = [
                            'is_lln_lesson' => $isLLNLesson,
                            'lln_old_status' => $llnLesson['lln_old_status'] ?? null,
                            'lln_new_status' => $llnLesson['lln_new_status'] ?? null,
                        ];
                        $processedQuizzes = $this->processQuizzes($progress, $topic, $lesson_id, $topic_id, $course_id, $llnLessonInfo);
                        $topics[$topic_id]['children'] = $processedQuizzes;

                        // Update quiz count based on processed quizzes
                        $topics[$topic_id]['quiz_count'] = count($processedQuizzes);
                    }

                    $topicCount++;
                    if ($topicCount === $totalTopics) {
                        $topics[$topic_id]['last_topic'] = $topic;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing topic', [
                        'user_id' => $this->user_id,
                        'topic_id' => $topic_id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    continue;
                }
            }

            return $topics;
        } catch (\Exception $e) {
            \Log::error('Error processing topics', [
                'user_id' => $this->user_id,
                'lesson_id' => $lesson_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function buildTopicResponse(array $topic, array $llnLesson, $course = null): array
    {
        $isLLNLesson = $llnLesson['is_lln_lesson'] ?? false;
        $llnNewStatus = $llnLesson['lln_new_status'] ?? null;
        $llnOldStatus = $llnLesson['lln_old_status'] ?? null;

        // Check if LLND should be excluded for this topic's course (using passed course data)
        $excludeLLND = $course ? \App\Helpers\Helper::isLLNDExcluded($course->category) : false;

        // Only apply LLND logic if not excluded
        $llnComplete = !$excludeLLND && $isLLNLesson && ($llnNewStatus === 'SATISFACTORY' || $llnOldStatus === 'SATISFACTORY');

        $topicQuizzes = $topic['data']['quizzes'] ?? [];
        unset($topic['data']['quizzes']);

        // Calculate actual quiz count from the processed quiz data
        $actualQuizCount = 0;
        if (isset($topic['quizzes']['list']) && is_array($topic['quizzes']['list'])) {
            $actualQuizCount = count($topic['quizzes']['list']);
        } elseif (is_array($topicQuizzes)) {
            // Fallback to raw data if processed data not available
            $actualQuizCount = count($topicQuizzes);
        }

        return [
            'user_id' => $this->user_id,
            'type' => 'topic',
            'title' => $topic['data']['title'],
            'link' => route('lms.topics.show', $topic['data']['id']),
            'stats' => [
                'completed' => boolval($topic['completed'] ?? false),
                'submitted' => boolval($topic['submitted'] ?? false),
                'is_marked_complete' => boolval($topic['marked_at'] ?? false),
            ],
            'status' => $llnComplete ? 'COMPLETED' : $this->getStatus($topic),
            'data' => $topic['data'] ?? [],
            'quizzes' => $topicQuizzes,
            'quiz_count' => $actualQuizCount,
        ];
    }

    protected function processQuizzes(CourseProgress $progress, array $topic, int $lesson_id, int $topic_id, int $course_id, array $llnLesson): array
    {
        try {
            if (empty($progress) || empty($topic) || empty($lesson_id) || empty($topic_id) || empty($course_id)) {
                throw new \InvalidArgumentException('Progress, topic, lesson ID, topic ID, and course ID are required');
            }

            $quizzes = [];
            $quizCount = 0;
            $totalQuizzes = (int)$topic['quizzes']['count'];
            $pendingChecklist = false;
            $notSatisfactoryChecklist = false;

            // Check if LLND should be excluded for this course (using already loaded course data)
            $excludeLLND = $progress->course ? \App\Helpers\Helper::isLLNDExcluded($progress->course->category) : false;

            $isLLNLesson = $llnLesson['is_lln_lesson'] ?? false;
            $llnNewStatus = $llnLesson['lln_new_status'] ?? null;
            $llnOldStatus = $llnLesson['lln_old_status'] ?? null;
            // Only apply LLND logic if not excluded
            $llnComplete = !$excludeLLND && $isLLNLesson && ($llnNewStatus === 'SATISFACTORY' || $llnOldStatus === 'SATISFACTORY');
            $llnQuizId = config('lln.quiz_id');

            // Check if this is the pre-course topic (first lesson, first topic) at course level
            $isPreCourseTopic = false;
            if (!$excludeLLND && $progress->course->is_main_course && $isLLNLesson) {
                // Get the first lesson and first topic of the course
                $firstLesson = $progress->course->lessons->sortBy('order')->first();
                $firstTopic = $firstLesson ? $firstLesson->topics->sortBy('order')->first() : null;

                // Only replace if this is the FIRST lesson (order = 0) AND the FIRST topic (order = 0)
                $isPreCourseTopic = $firstLesson && (int)$firstLesson->order === 0 &&
                    $firstTopic && (int)$firstTopic->order === 0 &&
                    $lesson_id === $firstLesson->id &&
                    $topic_id === $firstTopic->id;

                // Additional check: Only replace if student has submitted OR passed the NEW LLND (quiz ID 11111)
                if ($isPreCourseTopic) {
                    $newLLNDAttempt = \App\Models\QuizAttempt::where('user_id', $this->user_id)
                        ->where('quiz_id', config('lln.quiz_id'))
                        ->where(function ($query) {
                            $query->where('status', 'SATISFACTORY')
                                ->orWhere('status', 'SUBMITTED')
                                ->orWhere('status', 'EVALUATED')
                                ->orWhere('status', 'MARKED');
                        })
                        ->latest()
                        ->first();

                    // Only replace if student has submitted or passed the new LLND
                    $isPreCourseTopic = $newLLNDAttempt !== null;
                }
            }

            // Process quizzes and handle LLND replacement for pre-course topic
            $processedQuizzes = [];
            $quizCount = 0;
            $firstQuizReplaced = false;

            foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                try {
                    // Skip the first quiz if this is the pre-course topic and we need to replace it with LLND
                    if ($isPreCourseTopic && !$firstQuizReplaced) {
                        // Replace first quiz with LLND quiz
                        $llnQuiz = Quiz::with(['questions', 'attempts' => function ($q) {
                            $q->where('user_id', $this->user_id)->orderBy('id', 'desc');
                        }])->find($llnQuizId);

                        if ($llnQuiz) {
                            $llnQuizAttempts = $this->getQuizAttempts($llnQuiz);
                            $llnQuizResponse = $this->buildQuizResponse([], $llnQuiz, $llnQuizAttempts, $llnLesson, $progress->course);
                            $llnQuizResponse['id'] = 'lln_' . $llnQuizId;
                            $llnQuizResponse['is_lln_quiz'] = true;
                            $processedQuizzes['lln_' . $llnQuizId] = $llnQuizResponse;

                            $firstQuizReplaced = true;
                            $quizCount++;

                            continue; // Skip the original first quiz
                        }
                    }

                    // Process regular quiz
                    $quizData = $progress->course->lessons->firstWhere('id', $lesson_id)
                        ->topics->firstWhere('id', $topic_id)
                        ->quizzes->firstWhere('id', $quiz_id);

                    if (empty($quizData)) {
                        \Log::warning('Missing Quiz', [
                            'progress_id' => $progress->id,
                            'user_id' => $this->user_id,
                            'lesson_id' => $lesson_id,
                            'topic_id' => $topic_id,
                            'quiz_id' => $quiz_id,
                            'data' => $quizData,
                        ]);

                        continue;
                    }

                    $quiz['data'] = $quizData->toArray();
                    $quizAttempts = $this->getQuizAttempts($quizData);

                    // Only handle LLN lesson quiz if not excluded
                    if (!$excludeLLND && $llnComplete && $quizAttempts->isEmpty()) {
                        $this->handleLLNLessonQuiz($quiz_id, $course_id, $lesson_id, $topic_id, $quiz);
                    }

                    $processedQuizzes[$quiz_id] = $this->buildQuizResponse($quiz, $quizData, $quizAttempts, $llnLesson, $progress->course);
                    $this->processQuizChecklist($quizData, $quiz, $quizAttempts, $pendingChecklist, $notSatisfactoryChecklist, $quizCount, $topic_id, $quiz_id);

                    $quizCount++;
                } catch (\Exception $e) {
                    \Log::error('Error processing quiz', [
                        'user_id' => $this->user_id,
                        'quiz_id' => $quiz_id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    continue;
                }
            }

            // Debug: Log the final quiz structure
            $firstLesson = $progress->course->lessons->sortBy('order')->first();
            $firstTopic = $firstLesson ? $firstLesson->topics->sortBy('order')->first() : null;

            return $processedQuizzes;
        } catch (\Exception $e) {
            \Log::error('Error processing quizzes', [
                'user_id' => $this->user_id,
                'topic_id' => $topic_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function getQuizAttempts(Quiz $quizData): Collection
    {
        return $quizData->attempts()
            ->where('user_id', $this->user_id)
            ->latestThreeAttempts()
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    protected function handleLLNLessonQuiz(int $quiz_id, int $course_id, int $lesson_id, int $topic_id, array &$quiz): void
    {
        $currentDateTime = \Carbon\Carbon::now(\App\Helpers\Helper::getTimeZone())->toDateTimeString();
        $attempt = QuizAttempt::firstOrCreate(
            ['user_id' => $this->user_id, 'quiz_id' => $quiz_id],
            [
                'user_id' => $this->user_id,
                'course_id' => $course_id,
                'lesson_id' => $lesson_id,
                'topic_id' => $topic_id,
                'quiz_id' => $quiz_id,
                'questions' => [],
                'submitted_answers' => [],
                'attempt' => 1,
                'system_result' => 'MARKED',
                'status' => 'SATISFACTORY',
                'user_ip' => request()->ip(),
                'accessor_id' => null,
                'accessed_at' => null,
                'submitted_at' => null,
                'created_at' => $currentDateTime,
                'updated_at' => $currentDateTime,
            ]
        );

        if ($attempt) {
            $this->updateOrCreateStudentActivity(
                $attempt,
                'ASSESSMENT MARKED',
                $this->user_id,
                [
                    'activity_on' => $attempt->getRawOriginal('updated_at'),
                    'student' => $this->user_id,
                    'status' => $attempt->status,
                    'accessor_id' => null,
                    'accessor_role' => null,
                    'accessed_at' => $attempt->created_at ?? $currentDateTime,
                ]
            );
            $quiz['passed'] = true;
            // Update LLND progress to ensure proper percentage calculation
            \App\Services\CourseProgressService::updateLLNDProgress($this->user_id, $course_id, $lesson_id, $topic_id, $quiz_id);
            $this->reCalculateProgress($course_id);
            event(new \App\Events\QuizAttemptStatusChanged($attempt));
        }
    }

    protected function buildQuizResponse(array $quiz, Quiz $quizData, Collection $quizAttempts, array $llnLesson, $course = null): array
    {
        $isLLNLesson = $llnLesson['is_lln_lesson'] ?? false;
        $llnNewStatus = $llnLesson['lln_new_status'] ?? null;
        $llnOldStatus = $llnLesson['lln_old_status'] ?? null;
        // Check if LLND should be excluded for this quiz's course (using passed course data)
        $excludeLLND = $course ? \App\Helpers\Helper::isLLNDExcluded($course->category) : false;
        // Only apply LLND logic if not excluded
        $llnComplete = !$excludeLLND && $isLLNLesson && ($llnNewStatus === 'SATISFACTORY' || $llnOldStatus === 'SATISFACTORY');

        $response = [
            'user_id' => $this->user_id,
            'type' => 'quiz',
            'title' => $quizData->title,
            'link' => route('lms.quizzes.show', $quizData->id),
            'stats' => [
                'passed' => isset($quiz['passed']) && boolval($quiz['passed']),
                'submitted' => isset($quiz['submitted']) && boolval($quiz['submitted']),
            ],
            'status' => $llnComplete ? 'SATISFACTORY' : $this->getQuizStatus($quiz),
            'data' => $quiz['data'] ?? [],
        ];

        // Only keep the latest attempt
        if ($quizAttempts->count() > 0) {
            $latestAttempt = $quizAttempts->first();
            $response['latest_attempt'] = [
                'data' => $latestAttempt->toArray(),
                'status' => $latestAttempt->status,
            ];
        } else {
            $response['latest_attempt'] = null;
        }

        if ($quizAttempts->count() > 0) {
            $response['children'] = $this->processQuizAttempts($quizAttempts, $quiz);
        }

        return $response;
    }

    protected function getQuizStatus($quiz): string
    {
        if (isset($quiz['passed']) && $quiz['passed']) {
            return 'SATISFACTORY';
        }
        if (isset($quiz['failed']) && $quiz['failed']) {
            return 'NOT SATISFACTORY';
        }
        if (isset($quiz['submitted']) && $quiz['submitted']) {
            return 'SUBMITTED';
        }

        return 'ATTEMPTING';
    }

    protected function processQuizAttempts(Collection $quizAttempts, array $quiz): array
    {
        $attempts = [];
        $firstAttempt = $quizAttempts->first();
        $firstAttemptArray = $firstAttempt->toArray();
        $attemptCount = 0;
        $passedAttempt = null;

        foreach ($quizAttempts as $attempt) {
            $attemptCount++;
            if ($attempt->status === 'SATISFACTORY' && empty($passedAttempt)) {
                $passedAttempt = $attempt;
            }

            $attempts[$attempt->id] = [
                'user_id' => $this->user_id,
                'type' => 'attempt',
                'link' => route('assessments.show', $attempt->id),
                'data' => $attempt->toArray(),
                'passedAttempt' => $passedAttempt,
                'status' => $attempt->status,
            ];
        }

        return $attempts;
    }

    protected function processQuizChecklist(Quiz $quizData, array $quiz, Collection $quizAttempts, bool &$pendingChecklist, bool &$notSatisfactoryChecklist, int $quizCount, int $topic_id, int $quiz_id): void
    {
        $hasChecklist = $quizData->hasChecklist();
        if ($hasChecklist) {
            $statusChecklist = $this->getCurrentChecklistStatus($quizData, (bool)$quiz['submitted']);
            $tempChecklist = [
                'status' => '',
                'failed' => false,
                'current_quiz' => $quizData->id,
                'user_id' => $this->user_id,
                'attempts' => $quizAttempts->count(),
            ];

            if (!$pendingChecklist && !$notSatisfactoryChecklist) {
                $this->updateChecklistStatus($quiz, $statusChecklist, $tempChecklist, $pendingChecklist, $notSatisfactoryChecklist, $quizAttempts, $quizCount, $topic_id, $quiz_id);
            } elseif ($notSatisfactoryChecklist) {
                $tempChecklist['status'] = 'FAILED';
            } else {
                $tempChecklist['status'] = 'NOT ATTEMPTED';
            }

            $quiz['checklist'] = $statusChecklist;
        }
    }

    protected function updateChecklistStatus(array $quiz, array $statusChecklist, array &$tempChecklist, bool &$pendingChecklist, bool &$notSatisfactoryChecklist, Collection $quizAttempts, int $quizCount, int $topic_id, int $quiz_id): void
    {
        // Get the next quiz from the database if needed
        if ($quizAttempts->count() > 0) {
            $nextQuiz = Quiz::where('topic_id', $topic_id)
                ->where('id', '>', $quiz_id)
                ->orderBy('id')
                ->first();

            if ($nextQuiz) {
                $nextQuizAttempt = QuizAttempt::where('quiz_id', $nextQuiz->id)
                    ->where('user_id', $this->user_id)
                    ->first();

                $tempChecklist['next_quiz'] = [
                    'data' => $nextQuiz->toArray(),
                    'is_submitted' => !empty($nextQuizAttempt),
                ];

                if ($statusChecklist['status'] === 'NOT ATTEMPTED' && !empty($nextQuizAttempt)) {
                    $pendingChecklist = true;
                }
            }
        }

        if (!empty($statusChecklist['failed']) && $statusChecklist['failed']) {
            $notSatisfactoryChecklist = true;
        }

        $tempChecklist = array_merge($tempChecklist, $statusChecklist);
        $tempChecklist[$topic_id]['details'][$quiz_id] = $statusChecklist;
    }

    public function getLessonStartDate($item, $student): ?string
    {
        return StudentCourseService::lessonStartDate($student->id, intval($item['data']['id']));

        //        return $this->getActivityTime($item, $student);
    }

    public function getLessonEndDate($item): ?string
    {
        return StudentCourseService::lessonEndDate($this->user_id, intval($item['data']['id']));
    }

    protected function getActualLessonEndDate($item, $student): ?string
    {
        $lesson = Lesson::find($item['data']['id']);
        $enrolment = $item['enrolment'] ?? null;
        if (empty($enrolment)) {
            $enrolment = $this->getEnrolment($student->id, $item['data']['course_id']);
        }
        $courseStartDate = $lesson->release_key === 'XDAYS' && $enrolment ? \Carbon\Carbon::parse($enrolment['course_start_at'] ?? $enrolment->getRawOriginal('course_start_at')) : null;

        if ($lesson->isAllowed($courseStartDate) || $lesson->isComplete()) {
            $lessonEndDate = StudentCourseService::lessonEndDate($student->id, $item['data']['id'], true);

            return $lessonEndDate;
        }

        return null;
    }

    protected function formatDateForDatabase($date): ?string
    {
        return DateHelper::parse($date);
    }

    /**
     * Checks if all quizzes with checklists for the lesson have a latest checklist for the student and all are SATISFACTORY.
     */
    protected function verifyChecklistCompletion(array $item, User $student): bool
    {
        $lessonId = $item['data']['id'] ?? null;
        if (!$lessonId) {
            return true; // No lesson ID means no checklists
        }

        // Get all quizzes for this lesson that have checklists
        $quizzesWithChecklist = Quiz::whereHas('topic', function ($query) use ($lessonId) {
            $query->where('lesson_id', $lessonId);
        })->where('has_checklist', 1)->get();

        $quizIdsWithChecklist = $quizzesWithChecklist->pluck('id')->toArray();

        // If no checklists are required, return true
        if (count($quizIdsWithChecklist) === 0) {
            return true;
        }

        $checklists = StudentLMSAttachables::forEvent('CHECKLIST')
            ->whereIn('attachable_id', $quizIdsWithChecklist)
            ->where('student_id', $student->id)
            ->where('attachable_type', Quiz::class)
            ->get()
            ->groupBy('attachable_id')
            ->map(function ($items) {
                return $items->sortByDesc('id')->first();
            });

        // Check if all required checklists are submitted
        if ($checklists->count() !== count($quizIdsWithChecklist)) {
            return false;
        }

        // Check if all submitted checklists are satisfactory
        foreach ($checklists as $quizId => $checkListItem) {
            $status = $checkListItem->properties['status'] ?? null;
            if (empty($status) || $status !== 'SATISFACTORY') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has submitted the new LLND quiz.
     */
    public function hasUserSubmittedNewLLND($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        $newLlnQuizId = config('lln.quiz_id');

        // Check if user has any attempt for the new LLND quiz
        $llnAttempt = \App\Models\QuizAttempt::where('user_id', $user_id)
            ->where('quiz_id', $newLlnQuizId)
            ->latest('id')
            ->first();

        return !empty($llnAttempt);
    }

    /**
     * Check if user's new LLND quiz attempt is satisfactory.
     */
    public function isUserNewLLNDSatisfactory($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        $newLlnQuizId = config('lln.quiz_id');

        // Check if user has a satisfactory attempt for the new LLND quiz
        // Use the same criteria as TopicController for consistency
        $llnAttempt = \App\Models\QuizAttempt::where('user_id', $user_id)
            ->where('quiz_id', $newLlnQuizId)
            ->where('status', 'SATISFACTORY')
            ->where('system_result', 'COMPLETED')
            ->first();

        return !empty($llnAttempt);
    }

    /**
     * Get comprehensive LLND status information for a user.
     */
    public function getUserLLNDStatus($user_id)
    {
        if (empty($user_id)) {
            return [
                'has_submitted' => false,
                'is_satisfactory' => false,
                'status' => 'NONE',
                'quiz_id' => config('lln.quiz_id'),
                'attempts' => [],
            ];
        }

        $newLlnQuizId = config('lln.quiz_id');

        // Get all LLND attempts for this user
        $llnAttempts = \App\Models\QuizAttempt::where('user_id', $user_id)
            ->where('quiz_id', $newLlnQuizId)
            ->orderBy('id', 'desc')
            ->get();

        $latestAttempt = $llnAttempts->first();

        return [
            'has_submitted' => $llnAttempts->isNotEmpty(),
            'is_satisfactory' => $latestAttempt && $latestAttempt->status === 'SATISFACTORY' && $latestAttempt->system_result === 'COMPLETED',
            'status' => $latestAttempt ? $latestAttempt->status : 'NONE',
            'quiz_id' => $newLlnQuizId,
        ];
    }

    /**
     * Debug method to manually test LLND status and log comprehensive information.
     */
    public function debugLLNDStatus($user_id)
    {
        $llnQuizId = config('lln.quiz_id');

        // Get all quiz attempts for this user with the LLND quiz ID
        $allAttempts = \App\Models\QuizAttempt::where('user_id', $user_id)
            ->where('quiz_id', $llnQuizId)
            ->orderBy('id', 'desc')
            ->get();

        // Get the latest attempt
        $latestAttempt = $allAttempts->first();

        // Check our methods
        $hasSubmitted = $this->hasUserSubmittedNewLLND($user_id);
        $isSatisfactory = $this->isUserNewLLNDSatisfactory($user_id);

        $debugInfo = [
            'user_id' => $user_id,
            'lln_quiz_id' => $llnQuizId,
            'config_lln_quiz_id' => config('lln.quiz_id'),
            'all_attempts_count' => $allAttempts->count(),
            'all_attempts' => $allAttempts->toArray(),
            'latest_attempt' => $latestAttempt ? $latestAttempt->toArray() : null,
            'hasUserSubmittedNewLLND' => $hasSubmitted,
            'isUserNewLLNDSatisfactory' => $isSatisfactory,
            'sql_queries' => [
                'all_attempts' => \App\Models\QuizAttempt::where('user_id', $user_id)
                    ->where('quiz_id', $llnQuizId)
                    ->toSql(),
                'satisfactory_attempt' => \App\Models\QuizAttempt::where('user_id', $user_id)
                    ->where('quiz_id', $llnQuizId)
                    ->where('status', 'SATISFACTORY')
                    ->toSql(),
            ],
        ];

        return $debugInfo;
    }
}
