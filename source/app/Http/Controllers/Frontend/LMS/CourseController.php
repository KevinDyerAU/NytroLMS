<?php

namespace App\Http\Controllers\Frontend\LMS;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use Carbon\Carbon;

class CourseController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Course $course, $slug = '')
    {
        // Check LLND exclusion early to avoid unnecessary processing
        $excludeLLND = \App\Helpers\Helper::isLLNDExcluded($course->category);

        // Only apply LLND redirection for non-excluded categories
        if (!$excludeLLND && $course->id === intval(config('lln.course_id'))) {
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
            ['name' => 'Course'],
            ['name' => 'Back', 'link' => route('frontend.dashboard')],
        ];
        $lessons = $course->lessons()->with('course')->orderBy('order', 'ASC')->get();

        $currentStatus = auth()->user()->detail->status;

        if (in_array($currentStatus, ['ONBOARDED', 'ENROLLED', 'CREATED'])) {
            AdminReportService::setStudentActive(auth()->user());
        }

        $enrolment = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])->where('user_id', auth()->user()->id)->where('course_id', $course->id)->first();

        // Sync course progress (comprehensive progress update)
        CourseProgressService::syncCourseProgress(auth()->user()->id, $course->id, $enrolment);

        if (!$excludeLLND) {
            $isMainCourse = $enrolment->course->is_main_course || !\Str::contains(\Str::lower($enrolment->course->title), 'emester 2');

            // Only apply LLND logic for main courses
            if ($isMainCourse) {
                CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
                $enrolment->refresh();

                $preCourse = QuizAttempt::find($enrolment->enrolmentStats->pre_course_attempt_id);
                $preCourseSatisfactory = !empty($preCourse) && $preCourse->status === "SATISFACTORY";
                $oldPreCourseSatisfactory = false;
                $pre_course_attempt = QuizAttempt::where('user_id', auth()->user()->id)->where('quiz_id', config('constants.precourse_quiz_id', 0))->first();
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
                $byPassPreCourseAssessment = false;
            }
        } else {
            // For excluded categories, set default values without any LLND checks
            $isMainCourse = false; // Changed from true to false for excluded categories
            $preCourseSatisfactory = false; // Changed from true to false
            $oldPreCourseSatisfactory = false; // Changed from true to false
            $byPassPreCourseAssessment = false;
        }

        $lessons = $lessons->map(function (Lesson $lesson, $index) use ($byPassPreCourseAssessment, $enrolment, $lessons, $course, $excludeLLND, $isMainCourse, $preCourseSatisfactory, $oldPreCourseSatisfactory) {
            // Special handling for first lesson with LLND completion
            if ($index === 0 && !$excludeLLND && $isMainCourse) {
                $topicCount = $lesson->topics()->count();

                if ($topicCount === 1 && ($preCourseSatisfactory || $oldPreCourseSatisfactory)) {
                    // If only 1 topic and LLND is completed, mark lesson as completed
                    $lesson['is_completed'] = true;
                    $lesson['is_submitted'] = true;
                } elseif ($topicCount > 1) {
                    // If more than 1 topic, check if all topics are completed/submitted including LLND
                    $allTopicsCompleted = true;
                    $allTopicsSubmitted = true;

                    $topics = $lesson->topics()->orderBy('order')->get();
                    foreach ($topics as $topicIndex => $topic) {
                        if ($topicIndex === 0 && ($preCourseSatisfactory || $oldPreCourseSatisfactory)) {
                            // First topic (LLND) is completed
                            continue;
                        } else {
                            // Check other topics
                            if (!$topic->isComplete()) {
                                $allTopicsCompleted = false;
                            }
                            if (!$topic->isSubmitted()) {
                                $allTopicsSubmitted = false;
                            }
                        }
                    }

                    $lesson['is_completed'] = $allTopicsCompleted;
                    $lesson['is_submitted'] = $allTopicsSubmitted;
                } else {
                    // No topics or other cases, use standard logic
                    $lesson['is_completed'] = $lesson->isComplete();
                    $lesson['is_submitted'] = $lesson->isSubmitted();
                }
            } else {
                // Use direct model methods for other lessons
                $lesson['is_completed'] = $lesson->isComplete();
                $lesson['is_submitted'] = $lesson->isSubmitted();
            }
            $lesson['is_pre_course_lesson'] = (intval($lesson->order) === 0);
            $course_start_date = $lesson->release_key === 'XDAYS' ? Carbon::parse($enrolment?->getRawOriginal('course_start_at')) : null;
            $lesson['is_allowed'] = (bool) $lesson->isAllowed($course_start_date);
            $lesson['release_plan'] = $lesson->releasePlan($course_start_date, auth()->user()->id, $course->id);
            $lesson['match_pre_course_lesson_title'] = \Str::contains(\Str::lower($lesson->title), ['pre-course', 'study tip', 'course assessment']);
            $lesson['byPassPreCourseAssessment'] = $byPassPreCourseAssessment;
            $lesson['is_unlocked'] = \App\Models\LessonUnlock::isUnlockedForUser($lesson->id, auth()->user()->id, $course->id);

            // Set go_green based on previous lesson's completion status
            if ($index > 0) {
                $previousLesson = $lessons[$index - 1];
                $lesson['go_green'] = $lesson['is_allowed'] && $previousLesson['is_completed'] && $previousLesson['is_submitted'];
            } else {
                $lesson['go_green'] = false; // First lesson can't go green
            }

            return $lesson;
        });

        // Set preCourse to false for excluded categories to skip LLND checks
        if ($excludeLLND) {
            $preCourse = false;
        }

        return view()->make('frontend.content.lms.detail')->with([
            'type' => 'course',
            'title' => $course->title,
            'activeUser' => auth()->user(),
            'post' => $course,
            'hasRelated' => (count($lessons) > 0),
            'courseEnrolment' => $enrolment,
            'pre_course_assessment' => !empty($preCourse) ? $preCourse : null,
            'pre_course_satisfactory' => $preCourseSatisfactory,
            'old_pre_course_satisfactory' => $oldPreCourseSatisfactory,
            'byPassPreCourseAssessment' => $byPassPreCourseAssessment,
            'isMainCourse' => $isMainCourse,
            'excludeLLND' => $excludeLLND,
            'is_pre_course_lesson' => false, // Courses don't have pre-course lessons
            'match_pre_course_lesson_title' => false, // Courses don't have pre-course lessons
            'related' => [
                'type' => 'lesson',
                'title' => \Str::plural('Lesson', count($lessons)),
                'route' => 'frontend.lms.lessons.show',
                'data' => $lessons,
            ],
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
        ]);
    }

    public function markComplete(Course $course)
    {
        $progress = CourseProgressService::markComplete('course', ['user_id' => auth()->user()->id, 'course_id' => $course->id]);
        //        $next = CourseProgressService::getNextLink($progress, ['course' => $course]);

        $this->activityService->setActivity([
            'user_id' => auth()->user()->id,
            'activity_event' => 'COURSE MARKED',
            'activity_details' => [
                'user_id' => auth()->user()->id,
                'status' => 'COMPLETE',
                'by' => 'user',
            ],
        ], $course);

        //        if (!empty($next['link'])) {
        //            return redirect()->to($next['link']);
        //        }
        return redirect()->route('frontend.dashboard');
        //        return redirect()->route('frontend.lms.courses.show', $course);
    }
}
