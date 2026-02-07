<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Setting;
use App\Models\StudentCourseEnrolment;
use App\Services\CourseProgressService;
use Carbon\Carbon;

class FrontendController extends Controller
{
    public function __constructor()
    {
        if (empty(auth()->user()->detail->onboard_at)) {
            $this->onboard(1);
        }
    }

    public function dashboard()
    {
        //        $courseProgress = new CourseProgressService();
        //        $progress = $courseProgress->populateProgress(1);
        //        ddd($courseProgress->getTotalQuizzes($progress));

        $pageConfigs = [
            'showMenu' => true,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
        ];
        $breadcrumbs = [
            ['name' => 'Welcome'],
        ];

        $timeZoneOffset = Helper::getTimeZoneOffset();
        $start = Carbon::today(Helper::getTimeZone());
        //        dump(Helper::getTimeZone(), $timeZoneOffset, $start);
        if ($timeZoneOffset > 0) {
            $start = $start->addHours($timeZoneOffset)->toDateTimeString();
        } else {
            $start = $start->subHours($timeZoneOffset)->toDateTimeString();
        }
        $registeredCourses = StudentCourseEnrolment::with('progress', 'course', 'enrolmentStats')
            ->where('user_id', auth()->user()->id)
            ->where('is_locked', 0)
            ->whereDate('course_start_at', '<=', $start)
            ->active()
//            ->dump()
            ->get()->map(function ($regCourse) {
                $returnedAssessments = QuizAttempt::where('user_id', '=', $regCourse->user_id)
                    ->where('course_id', '=', $regCourse->course_id)
                    ->latestAttemptSubmittedOnly()
                    ->whereIn('status', ['RETURNED', 'FAIL', 'OVERDUE'])
                    ->get();
                $regCourse['returned_assessments'] = $returnedAssessments;

                return $regCourse;
            });

        $settings = Setting::whereNull('user_id')->get()?->pluck('value', 'key');
        $settings = $settings->map(function ($item, $key) {
            if (is_string($item) && json_decode($item) !== null) {
                return json_decode($item, true);
            }

            return $item;
        });

        // Get LLND status for re-attempt banner
        $studentTrainingPlanService = new \App\Services\StudentTrainingPlanService(auth()->user()->id);
        $llnStatus = $studentTrainingPlanService->getUserLLNDStatus(auth()->user()->id);

        return view()->make('frontend.content.dashboard.index')->with([
            'title' => 'My Courses',
            'registeredCourses' => $registeredCourses,
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
            'settings' => $settings,
            'llnStatus' => $llnStatus,
        ]);
    }

    /*public function onboard($step = 1)
    {
        $pageConfigs = [
            'showMenu' => false,
            'layoutWidth' => 'full'
        ];
        $breadcrumbs = [
            ['name' => 'Onboarding']
        ];
        return view('/frontend/content/onboard/index', [
            'title' => 'Key Institute',
            'step' => $step,
            'steps' => [
                1 => ['title' => 'Personal Info', 'subtitle' => 'Step #1','slug'=>'step-1'],
                2 => ['title' => 'Education Details', 'subtitle' => 'Step #2','slug'=>'step-2'],
                3 => ['title' => 'Employer Details', 'subtitle' => 'Step #3','slug'=>'step-3'],
                4 => ['title' => 'Requirements', 'subtitle' => 'Step #4','slug'=>'step-4'],
                5 => ['title' => 'Agreement', 'subtitle' => 'Step #5','slug'=>'step-5'],
            ],
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs
        ]);
    }*/
}
