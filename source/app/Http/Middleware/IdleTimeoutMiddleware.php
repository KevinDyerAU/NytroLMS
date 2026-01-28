<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class IdleTimeoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next) {
        $user = Auth::user();

        // Only apply idle timeout to authenticated users
        if ($user) {
            $idleTimeoutMinutes = config('idle-timeout.timeout_minutes', 120); // Default 2 hours
            $lastActivity = Session::get('last_activity');
            $now = Carbon::now();

            // If this is the first request or last activity is not set, set it to now
            // Note: last_activity should already be set in LoginController::authenticated()
            // but we set it here as a fallback for edge cases
            if (!$lastActivity) {
                Session::put('last_activity', $now->timestamp);
                // Don't regenerate session here - Laravel already did it during login
                // Regenerating would invalidate the CSRF token and cause 419 errors

                return $next($request);
            }

            $lastActivityTime = Carbon::createFromTimestamp($lastActivity);
            $idleTime = $now->diffInMinutes($lastActivityTime);

            // Check if user has been idle for more than the timeout period
            // This check must happen BEFORE the stale data check to prevent
            // users who have exceeded the idle timeout from staying logged in
            if ($idleTime >= $idleTimeoutMinutes) {
                // Log the user out
                Auth::logout();
                Session::flush();

                // Handle AJAX requests
                if ($request->expectsJson()) {
                    return response()->json(
                        [
                            'message' =>
                                'Your session has expired due to inactivity. Please log in again.',
                            'error' => 'session_expired',
                            'redirect' => route('login'),
                        ],
                        419 // Use 419 for consistency with Laravel's CSRF error
                    );
                }

                // Redirect to login with a clear message
                return redirect()
                    ->route('login')
                    ->with(
                        'error',
                        'Your session has expired due to inactivity. Please log in again.'
                    );
            }

            // Safety check: if last_activity is from a previous session (more than session lifetime ago),
            // reset it. This handles cases where session data persists unexpectedly.
            // This check happens AFTER idle timeout check to ensure users past timeout are logged out first.
            $sessionLifetime = config('session.lifetime', 180); // minutes
            if ($now->diffInMinutes($lastActivityTime) > $sessionLifetime) {
                Session::put('last_activity', $now->timestamp);
                return $next($request);
            }

            // Update last activity timestamp and keep Laravel session alive
            Session::put('last_activity', $now->timestamp);

            // Touch the session to prevent Laravel's native timeout
            $request->session()->put('_last_activity', time());
        }

        return $next($request);
    }
}
