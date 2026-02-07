<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnBoardMiddleware
{
    /**
     * Handle an incoming request.
     *
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $dateTime = Carbon::now();
            if (!empty($user->password_change_at)) {
                if (empty($user->detail->first_login)) {
                    $user->detail->first_login = $dateTime;
                }
                if (empty($user->detail->last_logged_in)) {
                    $user->detail->last_logged_in = $dateTime;
                }
                $user->detail->save();
            }

            if ($user->hasRole('Student')) {
                $routeName = $request->route()->getName();

                // If accessing the onboard page and already onboarded, redirect to dashboard
                if ($routeName === 'frontend.onboard' || $routeName === 'frontend.onboard.create') {
                    if (!empty($user->detail->onboard_at)) {
                        return redirect(route('frontend.dashboard'));
                    }
                }
                // If accessing any other page and not onboarded, redirect to onboard
                if ($routeName !== 'frontend.onboard' && $routeName !== 'frontend.onboard.create') {
                    if (empty($user->detail->onboard_at)) {
                        return redirect(route('frontend.onboard.create'));
                    }
                }
            } else {
                abort(403, 'Only for Students.');
            }
        }

        return $next($request);
    }
}
