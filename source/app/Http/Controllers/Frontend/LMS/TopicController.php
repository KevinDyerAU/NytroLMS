<?php

namespace App\Http\Controllers\Frontend\LMS;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\Topic;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use App\Services\StudentTrainingPlanService;
use Carbon\Carbon;

class TopicController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(Topic $topic, $slug = '')
    {
        // Eager load the relationships to avoid lazy loading
        $topic->load(['lesson.course']);
        $course = $topic->lesson->course;
        // Check LLND exclusion early to avoid unnecessary processing
        $excludeLLND = \App\Helpers\Helper::isLLNDExcluded($course->category);

        // Only apply LLND redirection for non-excluded categories
        if (!$excludeLLND && $topic->id === intval(config('lln.topic_id'))) {
            return redirect()->route('frontend.dashboard');
        }

        $lesson = $topic->lesson;
        $pageConfigs = [
            'showMenu' => true,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
            'contentLayout' => 'content-detached-right-sidebar',
            'bodyClass' => 'content-detached-right-sidebar',
        ];
        $breadcrumbs = [
            ['name' => 'Topic'],
            ['name' => 'Back', 'link' => route('frontend.lms.lessons.show', [$lesson->id, $lesson->slug])],
        ];
        $progress = CourseProgressService::getProgress(auth()->user()->id, $topic->course->id);

        // Skip topic completion check for excluded categories
        if (!$excludeLLND) {
            // Check if this is a main course for LLND logic
            $isMainCourse = $course->is_main_course || !\Str::contains(\Str::lower($course->title), 'semester 2');

            if ($isMainCourse) {
                // For main courses, check LLND completion for first topic
                $isFirstTopic = $topic->order === 0;
                $isFirstLesson = $lesson->order === 0;

                if ($isFirstTopic && $isFirstLesson) {
                    // This is the first topic of the first lesson - check LLND completion
                    $enrolment = StudentCourseEnrolment::where('user_id', auth()->user()->id)
                        ->where('course_id', $course->id)
                        ->with('enrolmentStats')
                        ->first();

                    if ($enrolment) {
                        $preCourse = QuizAttempt::find($enrolment->enrolmentStats->pre_course_attempt_id ?? null);
                        $preCourseSatisfactory = !empty($preCourse) && $preCourse->status === "SATISFACTORY";
                        $oldPreCourseSatisfactory = false;

                        $pre_course_attempt = QuizAttempt::where('user_id', auth()->user()->id)
                            ->where('quiz_id', config('constants.precourse_quiz_id', 0))
                            ->first();
                        if (!empty($pre_course_attempt) && $pre_course_attempt->status === 'SATISFACTORY' && $pre_course_attempt->system_result === 'COMPLETED') {
                            $preCourseSatisfactory = true;
                            $oldPreCourseSatisfactory = true;
                        }

                        // Check if new LLND is completed
                        $newPreCourseAttempt = QuizAttempt::where('user_id', auth()->user()->id)
                            ->where('quiz_id', config('lln.quiz_id'))
                            ->latest()
                            ->first();
                        if (!empty($newPreCourseAttempt) && config('lln.enforcement', false)) {
                            $isNewLLNDCompleted = ($newPreCourseAttempt->status === 'SATISFACTORY' && $newPreCourseAttempt->system_result === 'COMPLETED');
                            if ($isNewLLNDCompleted) {
                                $preCourseSatisfactory = true;
                                $oldPreCourseSatisfactory = true;
                            }
                        }

                        // If LLND is not completed, block access to first topic
                        if (!($preCourseSatisfactory || $oldPreCourseSatisfactory)) {
                            abort(403, "Please complete the Language, Literacy and Numeracy assessment first.");
                        }
                    }
                } else {
                    // For other topics, check if previous topic is completed using LLND logic
                    if (!self::isPrevTopicCompletedWithLLND($progress->toArray(), $topic, $lesson, $course)) {
                        abort(403, "Previous topic not completed yet.");
                    }
                }
            } else {
                // For non-main courses, use standard completion check
                if (!CourseProgressService::isPrevTopicCompleted($progress->toArray(), ['id' => $topic->id, 'lesson_id' => $lesson->id])) {
                    abort(403, "Previous topic not completed yet.");
                }
            }
        }

        $quizzes = $topic->quizzes()->orderBy('order', 'ASC')->get();
        $countQuizzes = !(empty($quizzes)) ? count($quizzes) : 0;
        $passedQuizzes = [];

        if (!empty($progress)) {
            $option = [
                'user_id' => intval(auth()->user()->id),
                'course_id' => intval($topic->course->id),
            ];

            $progressDetails = $progress->details->toArray();
            $details = CourseProgressService::reEvaluateProgress($option['user_id'], $progressDetails);
            $progress->details = $details;

            // Use StudentTrainingPlanService for LLND-adjusted progress calculation
            $studentTrainingPlanService = new \App\Services\StudentTrainingPlanService($option['user_id']);
            $totalCounts = $studentTrainingPlanService->getTotalCounts($details, $topic->course->id);

            // Calculate the percentage from the total counts
            $percentage = 0;
            if ($totalCounts['total'] > 0) {
                $percentage = round(($totalCounts['processed'] / $totalCounts['total']) * 100, 2);
            }

            $progress->percentage = $percentage;
            $progress->save();

            //AdminReport
            $data = [];
            $adminReportService = new AdminReportService($option['user_id'], $option['course_id']);
            $adminReportService->getCourseData($progress, $data);
            $adminReportService->update($data);

            $quizzesCounts = CourseProgressService::getQuizzes($progress, $lesson->id, $topic->id);
            $countQuizzes = $quizzesCounts['count'] ?? 0;
        }

        $enrolment = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])->where('user_id', auth()->user()->id)->where('course_id', $course->id)->first();
        if (empty($enrolment)) {
            abort(404, "Enrolment not found.");
        }

        // Create StudentTrainingPlanService instance once for LLND operations
        $studentTrainingPlanService = new StudentTrainingPlanService(auth()->user()->id);
        // For excluded categories, skip LLND checks
        if ($excludeLLND) {
            $isMainCourse = true;
            $preCourseSatisfactory = true;
            $oldPreCourseSatisfactory = true;
            $match_pre_course_lesson_title = false;
            $byPassPreCourseAssessment = false;
            $is_pre_course = false;
            $preCourse = null;
        } else {
            // Standard LLND logic for non-excluded categories (only for main courses)
            $isMainCourse = !empty($enrolment->course) && (($enrolment->course->is_main_course || !\Str::contains(\Str::lower($enrolment->course->title), 'semester 2')));

            // Only apply LLND logic for main courses
            if ($isMainCourse) {
                // Update student course stats and check LLND completion
                CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
                $enrolment->refresh();

                // Get pre-course assessment status
                $preCourse = QuizAttempt::find($enrolment->enrolmentStats->pre_course_attempt_id);
                $preCourseSatisfactory = !empty($preCourse) && $preCourse->status === "SATISFACTORY";
                $oldPreCourseSatisfactory = false;

                // Check if LLND quiz is completed
                $llnCompleted = false;
                if (!$isMainCourse) {
                    $llnAttempt = QuizAttempt::where('user_id', auth()->id())
                        ->where('quiz_id', config('lln.quiz_id'))
                        ->where('status', 'SATISFACTORY')
                        ->first();
                    $llnCompleted = !is_null($llnAttempt);
                }
                $pre_course_attempt = QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', config('constants.precourse_quiz_id', 0))
                    ->first();
                $match_pre_course_lesson_title = \Str::contains(\Str::lower($topic->lesson->title), ['pre-course', 'study tip', 'course assessment']);

                if ($isMainCourse && !empty($pre_course_attempt) && $pre_course_attempt->status === 'SATISFACTORY' && $pre_course_attempt->system_result === 'COMPLETED') {
                    $preCourseSatisfactory = true;
                    $oldPreCourseSatisfactory = true;
                }

                $byPassPreCourseAssessment = Carbon::parse($enrolment->getRawOriginal('course_start_at'))->greaterThanOrEqualTo(Carbon::today()->startOfYear());
                if ($byPassPreCourseAssessment) {
                    $oldPreCourseSatisfactory = false;
                }

                // Check if new LLND is completed
                $newPreCourseAttempt = QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', config('lln.quiz_id'))
                    ->latest()
                    ->first();

                if (!empty($newPreCourseAttempt) && config('lln.enforcement', false)) {
                    $preCourse = $newPreCourseAttempt;
                    // Use StudentTrainingPlanService for consistency
                    $isNewLLNDCompleted = $studentTrainingPlanService->isUserNewLLNDSatisfactory(auth()->user()->id);

                    if ($isNewLLNDCompleted) {
                        $preCourseSatisfactory = true;
                        $oldPreCourseSatisfactory = true;
                    }
                }

                $is_pre_course = intval($topic->lesson->order) === 0 && intval($topic->order) === 0;
                // Handle LLND quiz prepending for non-excluded categories and main courses only
                // Use the same logic as StudentTrainingPlanService for consistency
                if ($isMainCourse && !$excludeLLND &&
                    $studentTrainingPlanService->hasUserSubmittedNewLLND(auth()->user()->id)
                    && $studentTrainingPlanService->isUserNewLLNDSatisfactory(auth()->user()->id)) {
                    if ($is_pre_course) {
                        // Remove the first quiz
                        $quizzes = $quizzes->slice(1);
                        // Fetch the new LLND quiz
                        $llnQuizId = config('lln.quiz_id');
                        $llnQuiz = \App\Models\Quiz::find($llnQuizId);
                        if ($llnQuiz) {
                            // Prepend the new LLND quiz
                            $quizzes = $quizzes->prepend($llnQuiz);
                        }
                        $countQuizzes = count($quizzes);
                    }
                }
            } else {
                // For non-main courses, set default values
                $preCourse = null;
                $preCourseSatisfactory = false;
                $oldPreCourseSatisfactory = false;
                $match_pre_course_lesson_title = false;
                $byPassPreCourseAssessment = false;
                $is_pre_course = false;
            }
        }

        $nextLink = CourseProgressService::getNextLink($progress, ['type' => 'topic', 'parent' => intval($topic->lesson_id), 'id' => intval($topic->id)]);

        $prevLink = CourseProgressService::getPrevLink($progress, ['type' => 'topic', 'parent' => intval($topic->lesson_id), 'id' => intval($topic->id)]);

        return view()->make('frontend.content.lms.detail')->with([
            'type' => 'topic',
            'title' => $topic->title,
            'activeUser' => auth()->user(),
            'post' => $topic,
            'hasRelated' => ($countQuizzes > 0),
            'is_pre_course_lesson' => $is_pre_course,
            'match_pre_course_lesson_title' => $match_pre_course_lesson_title,
            'old_pre_course_satisfactory' => $oldPreCourseSatisfactory,
            'pre_course_satisfactory' => $oldPreCourseSatisfactory || $preCourseSatisfactory,
            'byPassPreCourseAssessment' => $byPassPreCourseAssessment,
            'isMainCourse' => $isMainCourse,
            'excludeLLND' => $excludeLLND,
            'related' => [
                'type' => 'quiz',
                'title' => \Str::plural('Quiz', $countQuizzes),
                'route' => 'frontend.lms.quizzes.show',
                'data' => $quizzes,
                'total' => $countQuizzes,
                'percentage' => $progress?->percentage,
                'passed' => $quizzesCounts['passed'] ?? 0,
                'submitted' => $quizzesCounts['submitted'] ?? 0,
            ],
            'next' => ($excludeLLND || ($isMainCourse && ($oldPreCourseSatisfactory || $preCourseSatisfactory))) ? $nextLink : null,
            'previous' => ($excludeLLND || ($isMainCourse && ($oldPreCourseSatisfactory || $preCourseSatisfactory))) ? $prevLink : null,
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
        ]);
    }

    public function markComplete(Topic $topic)
    {
        $lesson = $topic->lesson;
        $student_id = auth()->user()->id;
        $quizCount = $topic->quizzes()->count();
        CourseProgressService::createStudentActivity(
            $topic,
            'TOPIC MARKED',
            $student_id,
            [
                'status' => 'COMPLETE',
                'student' => $student_id,
                'lesson_id' => $lesson->id,
                'by' => auth()->user()->roleName(),
                'marked_by_id' => auth()->user()->id,
                'marked_by_role' => auth()->user()->roleName(),
                'marked_at' => \Carbon\Carbon::now()->toDateTimeString(),
                'time_spent' => $quizCount > 0 ? number_format(($topic->estimated_time / $quizCount), 2) : 0.00,
                'topic_time' => $topic->estimated_time,
                'total_quizzes' => $quizCount,
            ]
        );
        $progress = CourseProgressService::markComplete('topic', ['user_id' => auth()->user()->id, 'course_id' => $topic->course->id, 'lesson' => $lesson->id, 'topic' => $topic->id]);
        CourseProgressService::updateProgressSession($progress);

        StudentCourseService::addCompetency(auth()->user()->id, $lesson);
        //        $next = !empty($progress)?CourseProgressService::getNextLink($progress, ['lesson' => $lesson->id, 'topic' => $topic->id]):"";

        //        if (!empty($next['link'])) {
        //            return redirect()->to($next['link']);
        //        }

        return redirect()->route('frontend.lms.lessons.show', [$lesson->id, $lesson->slug]);
    }

    /**
     * Check if previous topic is completed with LLND logic.
     */
    private static function isPrevTopicCompletedWithLLND(array $progress, Topic $currentTopic, Lesson $lesson, \App\Models\Course $course): bool
    {
        if (empty($progress) || empty($progress['details']['lessons'])) {
            return false;
        }

        $details = $progress['details'];
        $topics = array_keys($details['lessons']['list'][$lesson->id]['topics']['list']);
        $currentIndex = array_search($currentTopic->id, $topics);

        // If this is the first topic, it should be completed if LLND is satisfied
        if ($currentIndex === 0) {
            return true; // First topic is handled separately in the main logic
        }

        // Get previous topic
        $prevIndex = $currentIndex - 1;
        if ($prevIndex < 0 || !isset($topics[$prevIndex])) {
            return false;
        }

        $prevTopicId = $topics[$prevIndex];
        $prevTopic = $details['lessons']['list'][$lesson->id]['topics']['list'][$prevTopicId];

        if (empty($prevTopic)) {
            return false;
        }

        // Check if the previous topic is the first topic (order = 0)
        // If it is, we need to check LLND completion instead of normal completion
        $prevTopicModel = Topic::find($prevTopicId);
        if ($prevTopicModel && $prevTopicModel->order === 0) {
            // This is the first topic - check LLND completion
            $enrolment = StudentCourseEnrolment::where('user_id', auth()->user()->id)
                ->where('course_id', $course->id)
                ->with('enrolmentStats')
                ->first();

            if ($enrolment) {
                $preCourse = QuizAttempt::find($enrolment->enrolmentStats->pre_course_attempt_id ?? null);
                $preCourseSatisfactory = !empty($preCourse) && $preCourse->status === "SATISFACTORY";
                $oldPreCourseSatisfactory = false;

                $pre_course_attempt = QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', config('constants.precourse_quiz_id', 0))
                    ->first();
                if (!empty($pre_course_attempt) && $pre_course_attempt->status === 'SATISFACTORY' && $pre_course_attempt->system_result === 'COMPLETED') {
                    $preCourseSatisfactory = true;
                    $oldPreCourseSatisfactory = true;
                }

                // Check if new LLND is completed
                $newPreCourseAttempt = QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', config('lln.quiz_id'))
                    ->latest()
                    ->first();
                if (!empty($newPreCourseAttempt) && config('lln.enforcement', false)) {
                    $isNewLLNDCompleted = ($newPreCourseAttempt->status === 'SATISFACTORY' && $newPreCourseAttempt->system_result === 'COMPLETED');
                    if ($isNewLLNDCompleted) {
                        $preCourseSatisfactory = true;
                        $oldPreCourseSatisfactory = true;
                    }
                }

                // Return true if LLND is completed
                return $preCourseSatisfactory || $oldPreCourseSatisfactory;
            }

            return false;
        } else {
            // This is not the first topic - use standard completion logic
            $isCompleted = $prevTopic['completed'] || $prevTopic['submitted'] || $prevTopic['marked_at'];
            $isAttempted = $prevTopic['quizzes']['count'] === $prevTopic['quizzes']['attempted'];

            return $isCompleted || $isAttempted;
        }
    }
}
