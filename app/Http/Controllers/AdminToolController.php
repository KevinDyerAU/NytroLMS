<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\StudentTrainingPlanService;
use Illuminate\Http\Request;

class AdminToolController extends Controller
{
    public function index()
    {
        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Admin Tools'],
        ];

        return view('content.admin-tools.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Show the sync student profiles form page
     * URL: /admin-tools/sync-stats.
     */
    public function showSyncStudentProfilesForm()
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['link' => 'admin-tools', 'name' => 'Admin Tools'],
            ['name' => 'Sync Stats'],
        ];

        return view('content.admin-tools.sync-student-profiles', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Show the test service consistency form page
     * URL: /admin-tools/compare-stats.
     */
    public function showTestServiceConsistencyForm()
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['link' => 'admin-tools', 'name' => 'Admin Tools'],
            ['name' => 'Compare Stats'],
        ];

        return view('content.admin-tools.test-service-consistency', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Sync student profiles for given user ID(s) - API endpoint
     * URL: /admin-tools/sync-student-profiles/api?user_ids=123,456,789.
     */
    public function syncStudentProfiles(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|string',
        ]);

        $userIds = $request->input('user_ids');

        // Parse comma-separated user IDs
        if (str_contains($userIds, ',')) {
            $userIds = array_map('trim', explode(',', $userIds));
        } else {
            $userIds = [trim($userIds)];
        }

        // Note: Batch processing is now handled on the frontend
        // The controller can handle any number of user IDs as long as they're valid

        // Validate that all user IDs are numeric
        $invalidIds = [];
        foreach ($userIds as $userId) {
            if (!is_numeric($userId) || $userId <= 0) {
                $invalidIds[] = $userId;
            }
        }

        if (!empty($invalidIds)) {
            return response()->json([
                'error' => 'Invalid user IDs provided: ' . implode(', ', $invalidIds) . '. User IDs must be positive numbers.',
                'invalid_ids' => $invalidIds,
            ], 400);
        }

        $results = [];
        $errors = [];
        $totalProcessed = 0;
        $totalEnrolments = 0;

        foreach ($userIds as $userId) {
            try {
                // Validate user exists
                $user = User::find($userId);
                if (!$user) {
                    $errors[] = "User ID {$userId} not found";

                    continue;
                }

                // Get all active enrolments for this user
                $enrolments = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'DELIST')
                    ->get();

                if ($enrolments->isEmpty()) {
                    $results[] = [
                        'user_id' => $userId,
                        'user_name' => $user->name,
                        'message' => 'No active enrolments found',
                        'enrolments_processed' => 0,
                    ];

                    continue;
                }

                $enrolmentsProcessed = 0;
                $userErrors = [];

                foreach ($enrolments as $enrolment) {
                    try {
                        // Determine if it's a main course
                        $isMainCourse = $enrolment->course?->is_main_course
                            || !\Str::contains(\Str::lower($enrolment->course?->title), 'emester 2');

                        $progress = CourseProgressService::initProgressSession($user->id, $enrolment->course_id, $enrolment);
                        // Recalculate progress to ensure details are properly populated
                        $courseProgress = CourseProgressService::reCalculateProgress($user->id, $enrolment->course_id);
                        // Ensure the course progress record is updated with fresh percentage
                        if ($courseProgress) {
                            $courseProgress->refresh();
                        }

                        // Update student course stats
                        $processEnrolment = CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
                        // Force refresh the enrolment to get updated course_stats
                        $enrolment->refresh();
                        $enrolment->load('enrolmentStats');

                        // Setup expiry date
                        CourseProgressService::setupExpiryDate($enrolment);

                        // Update admin reports
                        $adminReportService = new AdminReportService($user->id, $enrolment->course_id);
                        $adminReportService->update($adminReportService->prepareData($user, $enrolment->course), $enrolment);

                        // Touch the enrolment to update timestamp
                        $enrolment->touch();

                        $enrolmentsProcessed++;
                        $totalEnrolments++;
                    } catch (\Exception $e) {
                        $userErrors[] = "Course {$enrolment->course_id}: " . $e->getMessage();
                    }
                }

                $results[] = [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'enrolments_processed' => $enrolmentsProcessed,
                    'total_enrolments' => $enrolments->count(),
                    'errors' => $userErrors,
                ];

                $totalProcessed++;
            } catch (\Exception $e) {
                $errors[] = "User ID {$userId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'summary' => [
                'total_users_processed' => $totalProcessed,
                'total_enrolments_processed' => $totalEnrolments,
                'total_errors' => count($errors),
            ],
            'results' => $results,
            'errors' => $errors,
            'database_tables_updated' => [
                'course_progress' => 'Course progress percentage and details',
                'student_course_enrolments' => 'Enrolment status, expiry dates, completion dates',
                'student_course_stats' => 'Course statistics and completion status',
                'admin_reports' => 'Student and course details, progress data',
            ],
        ]);
    }

    /**
     * Check if a course is excluded from service consistency testing.
     */
    private function isCourseExcluded($courseId)
    {
        $llnCourseId = config('lln.course_id');
        $ptrCourseId = config('ptr.course_id');

        return $courseId == $llnCourseId || $courseId == $ptrCourseId;
    }

    /**
     * Test to verify that both services (LMS and Admin Report) provide the same course progress and assessment counts
     * URL: /admin-tools/test-service-consistency?user_id=123&course_id=456.
     *
     * Fixed issues:
     * - Handle StudentCourseEnrolment global scope that excludes LLN/PTR courses
     * - Allow testing even without explicit enrolment if course progress exists
     * - Provide better error messages with debugging information
     * - Handle cases where StudentTrainingPlanService fails to recalculate progress
     * - Exclude LLN/PTR courses from course selection and testing
     */
    public function testServiceConsistency(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            $courseId = $request->get('course_id');

            if (!$userId) {
                return response()->json(['error' => 'user_id parameter is required'], 400);
            }

            // Get a sample user and course for testing
            $user = \App\Models\User::with('detail')->find($userId);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            if ($courseId) {
                $course = \App\Models\Course::find($courseId);
                if (!$course) {
                    return response()->json(['error' => 'Course not found'], 404);
                }

                // Get course progress for the specific course
                $courseProgress = \App\Models\CourseProgress::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
                if (!$courseProgress) {
                    return response()->json(['error' => 'No course progress found for user ' . $user->id . ' in course ' . $course->id], 404);
                }
            } else {
                // Find a course where the user has progress, excluding LLN/PTR courses
                $llnCourseId = config('lln.course_id');
                $ptrCourseId = config('ptr.course_id');

                $courseProgress = \App\Models\CourseProgress::where('user_id', $user->id)
                    ->where('course_id', '!=', $llnCourseId)
                    ->where('course_id', '!=', $ptrCourseId)
                    ->first();

                if (!$courseProgress) {
                    // Get all courses the user is enrolled in, excluding LLN/PTR courses
                    $enrolledCourses = \App\Models\StudentCourseEnrolment::where('user_id', $user->id)
                        ->where('status', '!=', 'DELIST')
                        ->where('course_id', '!=', $llnCourseId)
                        ->where('course_id', '!=', $ptrCourseId)
                        ->with('course')
                        ->get();

                    if ($enrolledCourses->isEmpty()) {
                        return response()->json([
                            'error' => 'User ' . $user->id . ' is not enrolled in any testable courses (LLN/PTR courses are excluded from service consistency testing)',
                            'excluded_courses' => [
                                'lln_course_id' => $llnCourseId,
                                'ptr_course_id' => $ptrCourseId,
                            ],
                            'note' => 'LLN and PTR courses are excluded from StudentTrainingPlanService and cannot be tested for service consistency',
                        ], 404);
                    }

                    $courseList = $enrolledCourses->map(function ($enrolment) {
                        return [
                            'id' => $enrolment->course_id,
                            'title' => $enrolment->course->title ?? 'Unknown',
                            'category' => $enrolment->course->category ?? 'Unknown',
                        ];
                    })->toArray();

                    return response()->json([
                        'error' => 'User ' . $user->id . ' has no course progress records for testable courses',
                        'enrolled_courses' => $courseList,
                        'excluded_courses' => [
                            'lln_course_id' => $llnCourseId,
                            'ptr_course_id' => $ptrCourseId,
                        ],
                        'message' => 'Please specify a course_id from the enrolled courses list (LLN/PTR courses are excluded), or the user may need to start their courses first.',
                    ], 404);
                }

                $course = $courseProgress->course;

                // Double-check that the selected course is not excluded
                if ($this->isCourseExcluded($course->id)) {
                    return response()->json([
                        'error' => 'The automatically selected course ' . $course->id . ' (' . $course->title . ') is an LLN/PTR course and is excluded from testing',
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'note' => 'Please specify a different course_id that is not an LLN/PTR course',
                    ], 400);
                }
            }

            // Test using the existing progress data (don't recalculate to avoid changing the database)
            // Note: We're not calling reCalculateProgress() to avoid modifying the course_progress table

            // Test StudentTrainingPlanService (LMS) - use direct service calls with proper data
            $lmsService = new \App\Services\StudentTrainingPlanService($user->id);

            // Check if user has valid enrolment before calling getProgressDetails
            // Note: StudentCourseEnrolment has a global scope that excludes LLN/PTR courses
            // So we need to check without the global scope for those courses
            $enrolment = null;

            if ($course->id === config('lln.course_id') || $course->id === config('ptr.course_id')) {
                // For LLN/PTR courses, check without global scope
                $enrolment = \App\Models\StudentCourseEnrolment::withoutGlobalScope('excludeLlnAndPtrCourses')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', '!=', 'DELIST')
                    ->first();
            } else {
                // For regular courses, use normal query (global scope will apply)
                $enrolment = \App\Models\StudentCourseEnrolment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', '!=', 'DELIST')
                    ->first();
            }

            // If no enrolment found, check if course progress exists (some courses might not require explicit enrolment)
            if (!$enrolment) {
                // Check if there's any course progress for this user/course combination
                $hasProgress = \App\Models\CourseProgress::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->exists();

                if (!$hasProgress) {
                    // Let's also check what enrolments exist for this user to help with debugging
                    $allUserEnrolments = \App\Models\StudentCourseEnrolment::where('user_id', $user->id)
                        ->select('course_id', 'status')
                        ->get()
                        ->toArray();

                    return response()->json([
                        'error' => 'User ' . $user->id . ' has no enrolment or progress in course ' . $course->id,
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'note' => 'No enrolment record found and no course progress exists',
                        'debug_info' => [
                            'user_enrolments' => $allUserEnrolments,
                            'lln_course_id' => config('lln.course_id'),
                            'ptr_course_id' => config('ptr.course_id'),
                            'is_lln_course' => $course->id === config('lln.course_id'),
                            'is_ptr_course' => $course->id === config('ptr.course_id'),
                        ],
                    ], 400);
                }
            }

            // Check if course is LLN or PTR (these are excluded from StudentTrainingPlanService)
            if ($this->isCourseExcluded($course->id)) {
                return response()->json([
                    'error' => 'Course ' . $course->id . ' (' . $course->title . ') is an LLN/PTR course and is excluded from StudentTrainingPlanService',
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'course_type' => $course->id === config('lln.course_id') ? 'LLN' : 'PTR',
                    'note' => 'LLN and PTR courses are automatically excluded from service consistency testing',
                ], 400);
            }

            try {
                $lmsProgressDetails = $lmsService->getProgressDetails($courseProgress, $course->id);
                $lmsResults = $lmsService->getTotalCounts($lmsProgressDetails, $course->id);
                $lmsPercentage = $lmsService->calculatePercentage($lmsResults, $user->id, $course->id);
            } catch (\Exception $e) {
                // If StudentTrainingPlanService fails, try to use CourseProgressService to populate progress

                try {
                    // Try to use CourseProgressService to get progress details
                    $adminService = new \App\Services\CourseProgressService();
                    $lmsProgressDetails = $adminService->getProgressDetails($courseProgress, [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ]);

                    if (empty($lmsProgressDetails)) {
                        throw new \RuntimeException('CourseProgressService also failed to get progress details');
                    }

                    $lmsResults = $lmsService->getTotalCounts($lmsProgressDetails, $course->id);
                    $lmsPercentage = $lmsService->calculatePercentage($lmsResults, $user->id, $course->id);
                } catch (\Exception $fallbackError) {
                    // If both services fail, try to create minimal progress data

                    // Create minimal progress structure
                    $lmsProgressDetails = [
                        'course' => $course->id,
                        'completed' => false,
                        'at' => null,
                        'lessons' => [
                            'passed' => 0,
                            'count' => 0,
                            'submitted' => 0,
                            'list' => [],
                        ],
                    ];

                    $lmsResults = $lmsService->getTotalCounts($lmsProgressDetails, $course->id);
                    $lmsPercentage = $lmsService->calculatePercentage($lmsResults, $user->id, $course->id);
                }
            }

            // Test CourseProgressService (Admin Report) - use the normal flow
            $adminService = new \App\Services\CourseProgressService();

            // Ensure course progress details are properly formatted
            $progressDetails = $courseProgress->details;
            if (!$progressDetails) {
                // Try to populate progress details using CourseProgressService

                try {
                    $adminService = new \App\Services\CourseProgressService();
                    $populatedDetails = $adminService->getProgressDetails($courseProgress, [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ]);

                    if ($populatedDetails) {
                        $progressDetails = $populatedDetails;
                    } else {
                        throw new \RuntimeException('Failed to populate progress details');
                    }
                } catch (\Exception $populateError) {
                    return response()->json([
                        'error' => 'No progress details found and failed to populate: ' . $populateError->getMessage(),
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'debug_info' => [
                            'course_progress_id' => $courseProgress->id,
                            'course_progress_percentage' => $courseProgress->percentage,
                            'course_progress_created_at' => $courseProgress->created_at,
                            'course_progress_updated_at' => $courseProgress->updated_at,
                            'populate_error' => $populateError->getMessage(),
                        ],
                    ], 400);
                }
            }

            $adminResults = $adminService->getTotalCounts($user->id, $progressDetails);
            $adminPercentage = $adminService->calculatePercentage($adminResults, $user->id, $course->id);

            // Get quiz counts from CourseProgressService
            $adminQuizResults = $adminService->getTotalQuizzes($progressDetails, $user->id);

            // Compare results
            $comparison = [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_title' => $course->title,
                'course_category' => $course->category,
                'is_main_course' => $course->is_main_course,
                'is_llnd_excluded' => \App\Helpers\Helper::isLLNDExcluded($course->category),
                'user_onboarded' => $user->detail && $user->detail->onboard_at ? true : false,
                'lms_service' => [
                    'total_counts' => $lmsResults,
                    'percentage' => $lmsPercentage,
                ],
                'admin_service' => [
                    'total_counts' => $adminResults,
                    'percentage' => $adminPercentage,
                    'quiz_counts' => $adminQuizResults,
                ],
                'comparison' => [
                    'counts_match' => $lmsResults['total'] === $adminResults['total'] &&
                                     $lmsResults['passed'] === $adminResults['passed'] &&
                                     $lmsResults['failed'] === $adminResults['failed'] &&
                                     $lmsResults['submitted'] === $adminResults['submitted'] &&
                                     $lmsResults['processed'] === $adminResults['processed'],
                    'percentage_match' => abs($lmsPercentage - $adminPercentage) < 0.01, // Allow for small floating point differences
                    'percentage_difference' => abs($lmsPercentage - $adminPercentage),
                ],
            ];

            // // Check if results are consistent
            $isConsistent = $comparison['comparison']['counts_match'] && $comparison['comparison']['percentage_match'];

            return response()->json([
                'success' => true,
                'consistent' => $isConsistent,
                'data' => $comparison,
                'message' => $isConsistent ?
                    'Both services provide consistent results!' :
                    'Services provide different results - investigation needed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
