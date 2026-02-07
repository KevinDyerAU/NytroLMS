<?php

namespace App\Http\Controllers\Frontend\LMS;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use App\Services\StudentTrainingPlanService;
use Carbon\Carbon;

class LessonController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService) {
        $this->activityService = $activityService;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Lesson $lesson, $slug = '') {
        $course = $lesson->course;

        // Check if course exists
        if (!$course) {
            abort(404, "Course not found for this lesson.");
        }

        // Check LLND exclusion early to avoid unnecessary processing
        $excludeLLND = \App\Helpers\Helper::isLLNDExcluded($course->category);

        // Only apply LLND redirection for non-excluded categories
        if (!$excludeLLND && $lesson->id === intval(config('lln.lesson_id'))) {
            return redirect()->route('frontend.dashboard');
        }

        $pageConfigs = [
            'showMenu' => true,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
            'contentLayout' => 'content-detached-right-sidebar',
            'bodyClass' => 'content-detached-right-sidebar',
        ];
        $breadcrumbs = [
            ['name' => 'Lesson'],
            ['name' => 'Back', 'link' => route('frontend.lms.courses.show', [$course->id, $course->slug])],
        ];
        $progress = CourseProgressService::getProgress(auth()->user()->id, $course->id);

        // Check if progress exists
        if (!$progress) {
            abort(500, "Unable to load course progress. Please contact support.");
        }

        $progress = $progress->toArray();

        $topics = $lesson->topics()->orderBy('order', 'ASC')->get();
        $countTopics = !(empty($topics)) ? count($topics) : 0;

        $enrolment = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])->where('user_id', auth()->user()->id)->where('course_id', $course->id)->first();
        if (empty($enrolment)) {
            abort(404, "Enrolment not found.");
        }

        // For excluded categories, skip LLND checks but still require lesson completion
        if ($excludeLLND) {
            $isMainCourse = true;
            $preCourseSatisfactory = true;
            $oldPreCourseSatisfactory = true;
            $match_pre_course_lesson_title = false;
            $byPassPreCourseAssessment = false;
            $is_pre_course = false;
            $preCourse = false;

            // Simple previous lesson check without LLND requirements
            if (!CourseProgressService::isPrevLessonCompleted($progress, ['id' => $lesson->id])) {
                abort(403, "Previous lesson not completed yet.");
            }
        } else {
            // Standard LLND logic for non-excluded categories (only for main courses)
            // Check if course relationship exists
            if (!$enrolment->course) {
                abort(500, "Course relationship not found for enrolment. Please contact support.");
            }

            $isMainCourse = $enrolment->course->is_main_course || !\Str::contains(\Str::lower($enrolment->course->title), 'semester 2');

            // Only apply LLND logic for main courses
            if ($isMainCourse) {
                CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
                $enrolment->refresh();

                // Check if enrolmentStats exists before accessing it
                $preCourseAttemptId = $enrolment->enrolmentStats ? $enrolment->enrolmentStats->pre_course_attempt_id : null;
                $preCourse = $preCourseAttemptId ? QuizAttempt::find($preCourseAttemptId) : null;
                $preCourseSatisfactory = !empty($preCourse) && $preCourse->status === "SATISFACTORY";
                $oldPreCourseSatisfactory = false;
                $pre_course_attempt = QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', config('constants.precourse_quiz_id', 0))
                    ->first();
                $match_pre_course_lesson_title = \Str::contains(\Str::lower($lesson->title), ['pre-course', 'study tip', 'course assessment']);

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
                    $isNewLLNDCompleted = ($newPreCourseAttempt->status === 'SATISFACTORY' && $newPreCourseAttempt->system_result === 'COMPLETED');

                    if ($isNewLLNDCompleted) {
                        $preCourseSatisfactory = true;
                        $oldPreCourseSatisfactory = true;
                    }
                }
            } else {
                // For non-main courses, set default values
                $preCourse = false;
                $preCourseSatisfactory = false;
                $oldPreCourseSatisfactory = false;
                $match_pre_course_lesson_title = false;
                $byPassPreCourseAssessment = false;
            }

            $is_pre_course = intval($lesson->order) === 0;

            // Previous lesson completion check with LLND requirements (only for main courses)
            if ($isMainCourse && !CourseProgressService::isPrevLessonCompleted($progress, ['id' => $lesson->id])
                && !($oldPreCourseSatisfactory || $preCourseSatisfactory)) {
                abort(403, "Previous lesson not completed yet.");
            } elseif (!$isMainCourse && !CourseProgressService::isPrevLessonCompleted($progress, ['id' => $lesson->id])) {
                // For non-main courses, just check lesson completion
                abort(403, "Previous lesson not completed yet.");
            }
        }

        // Debug: Map topics to show completion status with LLND logic
        $topics = $topics->map(function ($topic, $index) use ($progress, $course, $excludeLLND, $isMainCourse, $preCourseSatisfactory, $oldPreCourseSatisfactory, $lesson) {
            // Use StudentTrainingPlanService for LLND-adjusted completion status
            $studentTrainingPlanService = new StudentTrainingPlanService(auth()->user()->id);

            // Get LLND-adjusted progress for this topic
            $topicProgress = null;
            if (!$excludeLLND && $isMainCourse) {
                // Get progress details for this specific course using populateProgress
                $courseProgress = $studentTrainingPlanService->populateProgress($course->id);
                if ($courseProgress && !empty($courseProgress['topics']['list'])) {
                    $topicId = $topic->id;
                    if (isset($courseProgress['topics']['list'][$topicId])) {
                        $topicProgress = $courseProgress['topics']['list'][$topicId];
                    }
                }
            }

            // Special handling for first topic of first lesson with LLND completion
            if ($index === 0 && $lesson->order === 0 && !$excludeLLND && $isMainCourse && ($preCourseSatisfactory || $oldPreCourseSatisfactory)) {
                // If LLND is completed (already calculated above), mark first topic of first lesson as completed
                $topic['is_completed'] = true;
                $topic['is_submitted'] = true;
            } else {
                // Use LLND-adjusted completion status if available, otherwise fall back to direct model methods
                $topic['is_completed'] = $topicProgress ? ($topicProgress['stats']['completed'] ?? false) : $topic->isComplete();
                $topic['is_submitted'] = $topicProgress ? ($topicProgress['stats']['submitted'] ?? false) : $topic->isSubmitted();
            }

            return $topic;
        });

        $isLessonAlreadyStarted = $this->activityService->getActivityWhere(['actionable_id' => $lesson->id, 'user_id' => auth()->user()->id, 'activity_event' => 'LESSON START'])->count() < 1;

        if ($isLessonAlreadyStarted) {
            $this->activityService->setActivity([
                'user_id' => auth()->user()->id,
                'activity_event' => 'LESSON START',
                'activity_details' => [
                        'user_id' => auth()->user()->id,
                        'at' => Carbon::now(),
                        'by' => auth()->user()->roleName(),
                    ],
            ], $lesson);
        }

        $nextLink = CourseProgressService::getNextLink($progress, ['type' => 'lesson', 'parent' => intval($lesson->course_id), 'id' => intval($lesson->id)], $enrolment);

        $prevLink = CourseProgressService::getPrevLink($progress, ['type' => 'lesson', 'parent' => intval($lesson->course_id), 'id' => intval($lesson->id)]);

        return view()->make('frontend.content.lms.detail')->with([
            'type' => 'lesson',
            'title' => $lesson->title,
            'activeUser' => auth()->user(),
            'post' => $lesson,
            'hasRelated' => ($countTopics > 0),
            'is_pre_course_lesson' => $is_pre_course,
            'match_pre_course_lesson_title' => $match_pre_course_lesson_title,
            'old_pre_course_satisfactory' => $oldPreCourseSatisfactory,
            'pre_course_satisfactory' => $oldPreCourseSatisfactory || $preCourseSatisfactory,
            'byPassPreCourseAssessment' => $byPassPreCourseAssessment,
            'isMainCourse' => $isMainCourse,
            'excludeLLND' => $excludeLLND,
            'related' => [
                    'type' => 'topic',
                    'title' => \Str::plural('Topic', $countTopics),
                    'route' => 'frontend.lms.topics.show',
                    'data' => $topics,
                ],
            'next' => ($excludeLLND || $oldPreCourseSatisfactory || $preCourseSatisfactory) ? $nextLink : null,
            'previous' => ($excludeLLND || $oldPreCourseSatisfactory || $preCourseSatisfactory) ? $prevLink : null,
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
        ]);
    }

    public function markComplete(Lesson $lesson) {
        $course = $lesson->course;
        $student_id = auth()->user()->id;
        CourseProgressService::createStudentActivity(
            $lesson,
            'LESSON MARKED',
            $student_id,
            [
                'status' => 'COMPLETE',
                'student' => $student_id,
                'course_id' => $course->id,
                'by' => auth()->user()->roleName(),
                'marked_by_id' => auth()->user()->id,
                'marked_by_role' => auth()->user()->roleName(),
                'marked_at' => \Carbon\Carbon::now()->toDateTimeString(),
            ]
        );
        $progress = CourseProgressService::markComplete('lesson', ['user_id' => auth()->user()->id, 'course_id' => $course->id, 'lesson' => $lesson->id]);
        CourseProgressService::updateProgressSession($progress);

        StudentCourseService::addCompetency(auth()->user()->id, $lesson);

        //        $next = !empty( $progress ) ? CourseProgressService::getNextLink( $progress, [ 'lesson' => $lesson->id ] ) : "";

        //        if (!empty($next) && isset($next['link'])) {
        //            return redirect()->to($next['link']);
        //        }

        return redirect()->route('frontend.lms.courses.show', [$course->id, $course->slug]);
    }
}
