<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Services\LlnCompletionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LlnAccessMiddleware
{
    protected string $llnCondition = 'NOT_SATISFACTORY'; // SATISFACTORY

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $llnQuizId = intval(config('lln.quiz_id'));
        $llnCutoffDate = '2025-07-01';
        $enforcement = config('lln.enforcement', true);
        $excludedCategories = config('lln.excluded_categories', ["non_accredited", "accelerator"]);

        if (!$user || config('lln.skip_lln', false)) {
            return $next($request);
        }
        // Skip LLN enforcement
        if (!$enforcement) {
            return $next($request);
        }

        // Note: Removed dashboard route skip to allow LLN enforcement on dashboard

        // Skip LLN checks for account-manager routes (account managers don't need LLND)
        if ($request->is('account-manager*') || $request->routeIs('account_manager.*')) {
            return $next($request);
        }

        // Check if the LLN quiz is active
        $quizParam = $request->route('quiz');
        if ($quizParam instanceof \App\Models\Quiz && $quizParam->id == $llnQuizId) {
            // Only allow access if at least one enrolment actually requires LLN
            $enrolments = StudentCourseEnrolment::where('user_id', $user->id)
                ->where('status', '!=', 'DELIST')
                ->where('is_main_course', 1)
                ->whereHas('course', function ($q) use ($excludedCategories) {
                    $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
                })
                ->get();
            //            Helper::debug($enrolments,'dump','testa3');
            $requiresLln = false;
            foreach ($enrolments as $enrolment) {
                if (in_array($enrolment->course?->category, $excludedCategories)) {
                    // Skip excluded categories instead of redirecting
                    continue;
                }

                if (!$enrolment->has_lln_completed) {
                    app(LlnCompletionService::class)
                        ->updateLlnStatus($user->id, $enrolment->course_id);

                    $hasCompletedQuery = QuizAttempt::where('user_id', $user->id)
                        ->whereHas('quiz', function ($q) use ($enrolment) {
                            $q->where(function ($subQuery) use ($enrolment) {
                                $subQuery->where('course_id', $enrolment->course_id)
                                    ->orWhere('course_id', intval(config('lln.course_id')));
                            })->where('is_lln', true);
                        });
                    if ($this->llnCondition === 'SATISFACTORY') {
                        $hasCompletedQuery->where('status', 'SATISFACTORY');
                    } else {
                        $hasCompletedQuery->where('system_result', '!=', 'INPROGRESS')
                            ->where('status', '!=', 'ATTEMPTING');
                    }
                    //                    Helper::debug($hasCompletedQuery->get(),'dump','testa3');
                    $hasCompletedLln = $hasCompletedQuery->exists();
                    //                    Helper::debug($hasCompletedLln,'dump','testa3');
                    if (!$hasCompletedLln) {
                        $requiresLln = true;

                        break;
                    }
                }
            }
            $latestLlnAttempt =
                \App\Models\QuizAttempt::where('user_id', auth()->user()->id)
                    ->where('quiz_id', $llnQuizId)
                    ->latest()
                    ->first();
            if ($latestLlnAttempt && in_array($latestLlnAttempt->status, ['FAIL', 'RETURNED', 'ATTEMPTING'])) {
                $requiresLln = true;
            }
            // dd($requiresLln, $latestLlnAttempt);
            //            Helper::debug([$latestLlnAttempt,$requiresLln],'dd','darrenk');
            //            Helper::debug(['requiredLLn' => $requiresLln],'dd','testa3');
            if (!$requiresLln) {
                // Student is not required to do LLN, block direct access with dashboard link
                // Use custom 423 error view with message
                $actionUrl = route('frontend.dashboard');

                return response()->view('errors.423', [
                    'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(423, 'You are not required to complete this assessment.'),
                    'actionUrl' => $actionUrl,
                ], 423);
            }

            return $next($request);
        }

        // Get all active, main course enrolments (not DELIST, not excluded category, not semester 2)
        $enrolments = StudentCourseEnrolment::where('user_id', $user->id)
            ->where('status', '!=', 'DELIST')
            ->where('is_main_course', 1)
            ->whereHas('course', function ($q) use ($excludedCategories) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->with('course')
            ->get();
        // If no enrolments, show 403 with leader name
        if ($enrolments->isEmpty()) {
            $leader = method_exists($user, 'leaders') ? $user->leaders()->first() : null;
            $leaderName = $leader ? $leader->name.', ' : '';
            $message = "No course assigned yet! Please contact your assigned leader, <strong>{$leaderName}</strong> to get you enrolled. Thanks.";
            $actionUrl = url('logout');
            $actionTitle = 'Logout';

            return response()->view('errors.403', [
                'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(403, $message),
                'actionUrl' => $actionUrl,
                'actionTitle' => $actionTitle,
            ], 403);
        }

        foreach ($enrolments as $enrolment) {
            app(LlnCompletionService::class)
                ->updateLlnStatus($user->id, $enrolment->course_id);

            // Debug: Log the enrolment details
            // dd([
            //     'user_id' => $user->id,
            //     'course_id' => $enrolment->course_id,
            //     'course_category' => $enrolment->course?->category,
            //     'is_excluded' => in_array($enrolment->course?->category, $excludedCategories),
            //     'has_lln_completed' => $enrolment->has_lln_completed
            // ]);

            if (in_array($enrolment->course?->category, $excludedCategories)) {
                // Skip LLND checks for excluded categories, but don't redirect to dashboard
                // to avoid infinite redirect loops
                // \Log::info('LLND Debug - Skipping excluded category:', $enrolment->course?->category);
                continue;
            }

            // Only check if LLN is not already marked as completed
            if (!$enrolment->has_lln_completed) {
                // dd('LLND Debug - Checking LLND completion for enrolment:', [
                //     'enrolment_id' => $enrolment->id,
                //     'course_id' => $enrolment->course_id
                // ]);

                // Fallback: If not marked as completed, check attempts directly
                $hasCompletedQuery = QuizAttempt::where('user_id', $user->id)
                    ->whereHas('quiz', function ($q) use ($enrolment) {
                        $q->where(function ($subQuery) use ($enrolment) {
                            $subQuery->where('course_id', $enrolment->course_id)
                                ->orWhere('course_id', intval(config('lln.course_id')));
                        })->where('is_lln', true);
                    });
                if ($this->llnCondition === 'SATISFACTORY') {
                    $hasCompletedQuery->where('status', 'SATISFACTORY');
                } else {
                    $hasCompletedQuery->where('system_result', '!=', 'INPROGRESS')
                        ->where('status', '!=', 'ATTEMPTING');
                }

                $hasCompletedLln = $hasCompletedQuery->exists();
                // dd('LLND Debug - LLND completion check result:', [
                //     'has_completed_lln' => $hasCompletedLln,
                //     'lln_quiz_id' => $llnQuizId,
                //     'lln_condition' => $this->llnCondition
                // ]);

                if (!$hasCompletedLln) {
                    //    dd('LLND Debug - Redirecting to LLND quiz');
                    return redirect()->route('frontend.lms.quizzes.show', $llnQuizId)
                        ->with('warning', 'You must complete the LLN assessment before accessing your course.');
                } else {
                    // \Log::info('LLND Debug - LLND already completed, allowing access');
                }
            } else {
                // \Log::info('LLND Debug - Enrolment marked as LLND completed', [
                //     'user_id' => $user->id,
                //     'enrolment_id' => $enrolment->id,
                //     'course_id' => $enrolment->course_id,
                //     'course_category' => $enrolment->course?->category,
                //     'trigger_url' => $request->fullUrl(),
                //     'request_method' => $request->method(),
                //     'user_agent' => $request->userAgent(),
                //     'ip_address' => $request->ip()
                // ]);
            }
        }

        // If all checks pass, allow access
        return $next($request);
    }
}
