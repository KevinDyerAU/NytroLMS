<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceUserSessionPolicies
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if the user is authenticated
        if ($user) {
            // Check if user is active
            if (intval($user->is_active) !== 1) {
                Auth::logout();

                return redirect()->route('login')
                    ->with('error', 'Your account is inactive. Please contact support if you believe this is a mistake.');
            }
            // Check if user session has exceeded maximum allowed time
            if (Carbon::now()->diffInHours($user->detail->getRawOriginal('last_logged_in')) >= 11) {
                Auth::logout();

                return redirect()->route('login')
                    ->with('error', 'Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
