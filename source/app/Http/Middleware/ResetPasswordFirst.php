<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResetPasswordFirst
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (empty($user->password_change_at)
                && empty($user->detail->last_logged_in)) {
                return redirect(route('profile.password', $user));
            }
        }

        return $next($request);
    }
}
