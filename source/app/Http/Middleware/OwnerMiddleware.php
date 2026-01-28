<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user->id !== auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
