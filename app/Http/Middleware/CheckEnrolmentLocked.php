<?php

namespace App\Http\Middleware;

use App\Models\StudentCourseEnrolment;
use Closure;
use Illuminate\Http\Request;

class CheckEnrolmentLocked
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $courseId = $request->route('course')?->id;
        if (!empty($enrolment)) {
            $enrolment = StudentCourseEnrolment::where('user_id', auth()->user()->id)
                ->where('course_id', $courseId)
                ->first();

            if (empty($enrolment) || $enrolment->is_locked === 1) {
                return redirect()->route('frontend.dashboard');
            }
        }

        return $next($request);
    }
}
