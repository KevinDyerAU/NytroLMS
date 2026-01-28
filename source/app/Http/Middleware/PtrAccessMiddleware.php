<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use App\Models\Quiz;
use App\Models\StudentCourseEnrolment;
use App\Services\PtrCompletionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PtrAccessMiddleware
{
    protected string $ptrCondition = 'NOT_SATISFACTORY'; // SATISFACTORY

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $ptrQuizId = intval(config('ptr.quiz_id'));
        $enforcement = config('ptr.enforcement', true);
        $excludedCategories = config('ptr.excluded_categories', ["non_accredited", "accelerator"]);

        // Helper::debug(['PTR Middleware: Starting PTR check' => [
        //     'user_id' => $user ? $user->id : 'no_user',
        //     'ptr_quiz_id' => $ptrQuizId,
        //     'enforcement' => $enforcement,
        //     'excluded_categories' => $excludedCategories,
        //     'current_path' => $request->path(),
        //     'request_url' => $request->url()
        // ]], 'dump', 'testn2');

        if (!$user || config('ptr.skip_ptr', false)) {
            // Helper::debug(['PTR Middleware: Skipping PTR check'=> [
            //     'reason' => !$user ? 'no_user' : 'skip_ptr_enabled',
            //     'skip_ptr' => config('ptr.skip_ptr', false)
            // ]], 'dd', 'testn2');
            return $next($request);
        }

        // Skip PTR enforcement
        if (!$enforcement) {
            // Helper::debug(['PTR Middleware: Skipping PTR enforcement' => [
            //     'user_id' => $user->id,
            //     'enforcement' => $enforcement
            // ]], 'dd', 'testn2');
            return $next($request);
        }

        // Check if the PTR quiz is active
        $quizParam = $request->route('quiz');
        if ($quizParam instanceof \App\Models\Quiz && $quizParam->id == $ptrQuizId) {
            // Check if user should be grandfathered BEFORE allowing PTR access
            $ptrImplementationDate = config('ptr.implementation_date');

            // Only fetch courses that actually need PTR (non-grandfathered, non-excluded)
            $enrolments = StudentCourseEnrolment::where('user_id', $user->id)
                ->where('status', '!=', 'DELIST')
                ->where('created_at', '>', $ptrImplementationDate . ' 00:00:00') // Check ENROLMENT date, not course date
                ->whereHas('course', function ($q) use ($excludedCategories) {
                    $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'")
                      ->where(function ($subQ) use ($excludedCategories) {
                          $subQ->whereNull('category')  // Include NULL categories
                               ->orWhereNotIn('category', $excludedCategories); // Include non-excluded categories
                      });
                })
                ->get();

            // If no courses need PTR, block access
            if ($enrolments->isEmpty()) {
                $actionUrl = route('frontend.dashboard');

                return response()->view('errors.423', [
                    'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(423, 'You are not required to complete this assessment.'),
                    'actionUrl' => $actionUrl,
                ], 423);
            }

            // Check if any of these courses actually require PTR completion
            $requiresPtr = false;
            $ptrService = app(PtrCompletionService::class);

            foreach ($enrolments as $enrolment) {
                $ptrCompleted = $ptrService->hasCompletedPtrForCourse($user->id, $enrolment->course_id);

                if (!$ptrCompleted) {
                    $requiresPtr = true;

                    break;
                }
            }

            // Remove the incorrect logic that checks for any PTR attempt globally
            // The PtrCompletionService already handles all the completion logic properly

            if (!$requiresPtr) {
                // Student is not required to do PTR, block direct access with dashboard link

                $actionUrl = route('frontend.dashboard');

                return redirect($actionUrl)->with('info', 'You are not required to complete this assessment.');
            }

            return $next($request);
        }

        // Get all active course enrolments (not DELIST, not semester 2)
        // Include both main courses (is_main_course = 1) and regular courses (is_main_course = 0)
        // as PTR should apply to all course types
        $enrolments = StudentCourseEnrolment::where('user_id', $user->id)
            ->where('status', '!=', 'DELIST')
            ->whereHas('course', function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->with('course')
            ->get();

        // Helper::debug(['PTR Middleware: Found enrolments'=> [
        //     'user_id' => $user->id,
        //     'total_enrolments' => $enrolments->count(),
        //     'enrolments_details' => $enrolments->map(function($e) {
        //         return [
        //             'course_id' => $e->course_id,
        //             'course_title' => $e->course->title ?? 'Unknown',
        //             'category' => $e->course->category ?? 'NULL',
        //             'is_main_course' => $e->course->is_main_course ?? 'Unknown',
        //             'created_at' => $e->created_at,
        //             'status' => $e->status
        //         ];
        //     })->toArray()
        // ]], 'dump', 'testn2');

        // Check PTR implementation date for grandfathering
        $ptrImplementationDate = config('ptr.implementation_date');

        $ptrService = app(PtrCompletionService::class);
        $coursesRequiringPtr = collect();

        foreach ($enrolments as $enrolment) {
            if (in_array($enrolment->course?->category, $excludedCategories)) {
                // Skip PTR checks for excluded categories, but don't redirect to dashboard
                // to avoid infinite redirect loops
                continue;
            }

            // Check if enrolment is grandfathered FIRST (before calling the service)
            $enrolmentDate = \Carbon\Carbon::parse($enrolment->created_at);
            $implementationDate = \Carbon\Carbon::parse($ptrImplementationDate);
            // Compare dates only (not times) - if enrolled on or before implementation date, consider grandfathered
            $isGrandfathered = $enrolmentDate->startOfDay()->lte($implementationDate->startOfDay());

            // Helper::debug(['PTR Middleware: Grandfathering check for enrolment'=> [
            //     'user_id' => $user->id,
            //     'course_id' => $enrolment->course_id,
            //     'course_title' => $enrolment->course->title ?? 'Unknown',
            //     'enrolment_created_at' => $enrolment->created_at,
            //     'enrolment_parsed_date' => $enrolmentDate->format('Y-m-d H:i:s'),
            //     'ptr_implementation_date' => $ptrImplementationDate,
            //     'ptr_parsed_date' => $implementationDate->format('Y-m-d H:i:s'),
            //     'is_grandfathered' => $isGrandfathered,
            //     'comparison_result' => $enrolmentDate->startOfDay()->lte($implementationDate->startOfDay())
            // ]], 'dump', 'testn2');

            if ($isGrandfathered) {
                // Helper::debug(['PTR Middleware: Skipping grandfathered enrolment'=> [
                //     'user_id' => $user->id,
                //     'course_id' => $enrolment->course_id,
                //     'course_title' => $enrolment->course->title ?? 'Unknown'
                // ]], 'dump', 'testn2');
                continue; // Skip PTR requirement for grandfathered enrolments
            }

            // Only check PTR completion for non-grandfathered enrolments
            $ptrCompleted = $ptrService->hasCompletedPtrForCourse($user->id, $enrolment->course_id);

            // Helper::debug(['PTR Middleware: PTR completion check'=> [
            //     'user_id' => $user->id,
            //     'course_id' => $enrolment->course_id,
            //     'course_title' => $enrolment->course->title ?? 'Unknown',
            //     'ptr_completed' => $ptrCompleted
            // ]], 'dump', 'testn2');

            if (!$ptrCompleted) {
                $coursesRequiringPtr->push([
                    'course_id' => $enrolment->course_id,
                    'course_title' => $enrolment->course->title ?? 'Unknown Course',
                    'category' => $enrolment->course->category ?? 'unknown',
                ]);
            }
        }

        // If no enrolments, show 403 with leader name
        if ($enrolments->isEmpty()) {
            $leader = method_exists($user, 'leaders') ? $user->leaders()->first() : null;
            $leaderName = $leader ? $leader->name . ', ' : '';
            $message = "No course assigned yet! Please contact your assigned leader, <strong>{$leaderName}</strong> to get you enrolled. Thanks.";
            $actionUrl = url('logout');
            $actionTitle = 'Logout';

            return response()->view('errors.403', [
                'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(403, $message),
                'actionUrl' => $actionUrl,
                'actionTitle' => $actionTitle,
            ], 403);
        }

        // Check if the current route is for a specific course that requires PTR
        $currentCourseId = null;
        if (preg_match('/\/courses\/(\d+)/', $request->path(), $matches)) {
            $currentCourseId = intval($matches[1]);
        } elseif (preg_match('/\/lessons\/(\d+)/', $request->path(), $matches)) {
            // For lesson routes, get the course ID from the lesson
            $lessonId = intval($matches[1]);
            $lesson = \App\Models\Lesson::find($lessonId);
            if ($lesson) {
                $currentCourseId = $lesson->course_id;
            }
        } elseif (preg_match('/\/topics\/(\d+)/', $request->path(), $matches)) {
            // For topic routes, get the course ID from the topic's lesson
            $topicId = intval($matches[1]);
            $topic = \App\Models\Topic::find($topicId);
            if ($topic && $topic->lesson) {
                $currentCourseId = $topic->lesson->course_id;
            }
        } elseif (preg_match('/\/course\/(\d+)/', $request->path(), $matches)) {
            // Alternative course URL pattern
            $currentCourseId = intval($matches[1]);
        } elseif ($request->has('course_id')) {
            // Check if course_id is passed as query parameter
            $currentCourseId = intval($request->query('course_id'));
        }

        // Helper::debug(['PTR Middleware: Current route analysis'=> [
        //     'current_path' => $request->path(),
        //     'current_course_id' => $currentCourseId,
        //     'courses_requiring_ptr' => $coursesRequiringPtr->toArray()
        // ]], 'dump', 'testn2');

        // If accessing a specific course, check if that course needs PTR
        if ($currentCourseId) {
            $currentCourseNeedsPtr = $coursesRequiringPtr->where('course_id', $currentCourseId)->isNotEmpty();

            // Helper::debug(['PTR Middleware: Course-specific PTR check'=> [
            //     'current_course_id' => $currentCourseId,
            //     'current_course_needs_ptr' => $currentCourseNeedsPtr
            // ]], 'dump', 'testn2');

            if ($currentCourseNeedsPtr) {
                // The current course needs PTR, redirect to PTR quiz
                $courseList = $coursesRequiringPtr->pluck('course_title')->implode(', ');

                // Helper::debug(['PTR Middleware: Redirecting to PTR quiz for current course' => [
                //     'current_course_id' => $currentCourseId,
                //     'coursesRequiringPtr' => $coursesRequiringPtr->toArray(),
                //     'ptrQuizId' => $ptrQuizId,
                //     'currentPath' => $request->path(),
                //     'intendedRoute' => $request->url(),
                //     'user_id' => $user->id
                // ]], 'dump', 'testn2');

                return redirect()->intended(route('frontend.lms.quizzes.show', $ptrQuizId) . '?course_id=' . $currentCourseId)
                    ->with('warning', "You must complete the Pre-Training Review (PTR) assessment for the following course(s): {$courseList}");
            } else {
                // Current course doesn't need PTR, allow access
                // Helper::debug(['PTR Middleware: Current course does not need PTR, allowing access' => [
                //     'current_course_id' => $currentCourseId,
                //     'user_id' => $user->id
                // ]], 'dd', 'testn2');
                return $next($request);
            }
        }

        // If not accessing a specific course, check if any course needs PTR
        if ($coursesRequiringPtr->isNotEmpty()) {
            $courseList = $coursesRequiringPtr->pluck('course_title')->implode(', ');

            // Helper::debug(['PTR Middleware: Redirecting to PTR quiz (general access)'=> [
            //     'coursesRequiringPtr' => $coursesRequiringPtr->toArray(),
            //     'ptrQuizId' => $ptrQuizId,
            //     'currentPath' => $request->path(),
            //     'intendedRoute' => $request->url(),
            //     'user_id' => $user->id
            // ]], 'dump', 'testn2');

            // Use intended() to store the current route for post-completion redirect
            // For general PTR requirement, use the first course that needs PTR
            $firstCourseId = $coursesRequiringPtr->first()['course_id'];

            return redirect()->intended(route('frontend.lms.quizzes.show', $ptrQuizId) . '?course_id=' . $firstCourseId)
                ->with('warning', "You must complete the Pre-Training Review (PTR) assessment for the following course(s): {$courseList}");
        } else {
            // Helper::debug(['PTR Middleware: No courses require PTR completion'=> [
            //     'user_id' => $user->id,
            //     'total_enrolments' => $enrolments->count(),
            //     'coursesRequiringPtr_count' => $coursesRequiringPtr->count(),
            //     'enrolments_details' => $enrolments->map(function($e) {
            //         return [
            //             'course_id' => $e->course_id,
            //             'course_title' => $e->course->title ?? 'Unknown',
            //             'category' => $e->course->category ?? 'NULL',
            //             'created_at' => $e->created_at,
            //             'status' => $e->status
            //         ];
            //     })->toArray()
            // ]], 'dd', 'testn2');
        }

        // If all checks pass, allow access
        return $next($request);
    }
}
