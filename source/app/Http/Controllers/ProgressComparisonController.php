<?php

namespace App\Http\Controllers;

use App\Models\AdminReport;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Services\StudentTrainingPlanService;

class ProgressComparisonController extends Controller
{
    public function index($user_id = null, $course_id = null)
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Compare Progress'],
        ];

        return view('content.playground.compare-progress', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'user_id' => $user_id,
            'course_id' => $course_id,
        ]);
    }

    public function getDetails($user_id, $course_id)
    {
        try {
            // Get student and course
            $student = User::findOrFail($user_id);
            $course = Course::findOrFail($course_id);

            // Get enrollment
            $enrolment = StudentCourseEnrolment::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->with(['progress', 'enrolmentStats'])
                ->first();

            if (!$enrolment) {
                return response()->json([
                    'error' => 'Student is not enrolled in this course',
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                ]);
            }

            // Get training plan
            $trainingPlanService = new StudentTrainingPlanService($user_id);
            $trainingPlan = $trainingPlanService->getTrainingPlan(true);

            // Get admin report
            $adminReport = AdminReport::where('student_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            // Get course progress
            $courseProgress = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            return response()->json([
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'email' => $student->email,
                ],
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                ],
                'enrollment' => [
                    'id' => $enrolment->id,
                    'status' => $enrolment->status,
                    'course_start_at' => $enrolment->course_start_at,
                    'course_ends_at' => $enrolment->course_ends_at,
                    'course_expiry' => $enrolment->course_expiry,
                    'course_completed_at' => $enrolment->course_completed_at,
                    'enrolment_stats' => $enrolment->enrolmentStats?->course_stats,
                ],
                'course_progress' => $courseProgress ? [
                    'id' => $courseProgress->id,
                    'percentage' => $courseProgress->percentage,
                    'details' => $courseProgress->details,
                ] : null,
                'training_plan' => [
                    'raw' => $trainingPlan,
                    'html' => $trainingPlanService->renderTrainingPlan($trainingPlan, $student),
                ],
                'admin_report' => $adminReport ? [
                    'id' => $adminReport->id,
                    'course_status' => $adminReport->course_status,
                    'course_expiry' => $adminReport->course_expiry,
                    'course_completed_at' => $adminReport->course_completed_at,
                    'student_course_progress' => $adminReport->student_course_progress,
                    'updated_at' => $adminReport->updated_at,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'course_id' => $course_id,
            ], 500);
        }
    }
}
