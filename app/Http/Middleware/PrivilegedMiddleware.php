<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivilegedMiddleware
{
    /**
     * Handle an incoming request.
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
                return redirect(route('frontend.dashboard'));
            }
        }

        return $next($request);
    }
}
