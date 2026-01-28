<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ActivityController extends Controller
{
    /**
     * Update the last activity timestamp for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActivity(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'User not authenticated',
                ],
                419
            );
        }

        $now = Carbon::now();
        Session::put('last_activity', $now->timestamp);

        // Keep Laravel session alive
        $request->session()->put('_last_activity', time());

        return response()->json([
            'success' => true,
            'message' => 'Activity updated',
            'timestamp' => $now->timestamp,
            'time' => $now->toDateTimeString(),
        ]);
    }

    /**
     * Get the current session status and remaining time.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionStatus(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'User not authenticated',
                ],
                401
            );
        }

        $idleTimeoutMinutes = config('session.idle_timeout', 120);
        $lastActivity = Session::get('last_activity');
        $now = Carbon::now();

        if (!$lastActivity) {
            return response()->json([
                'success' => true,
                'session_active' => true,
                'last_activity' => null,
                'remaining_minutes' => $idleTimeoutMinutes,
            ]);
        }

        $lastActivityTime = Carbon::createFromTimestamp($lastActivity);
        $idleTime = $now->diffInMinutes($lastActivityTime);
        $remainingMinutes = max(0, $idleTimeoutMinutes - $idleTime);

        return response()->json([
            'success' => true,
            'session_active' => $remainingMinutes > 0,
            'last_activity' => $lastActivityTime->toDateTimeString(),
            'idle_minutes' => $idleTime,
            'remaining_minutes' => $remainingMinutes,
            'timeout_minutes' => $idleTimeoutMinutes,
        ]);
    }
}
