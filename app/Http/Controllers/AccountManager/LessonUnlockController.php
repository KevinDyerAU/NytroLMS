<?php

namespace App\Http\Controllers\AccountManager;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonUnlock;
use Illuminate\Http\Request;

class LessonUnlockController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:unlock lessons']);
    }

    public function unlock(Request $request, Lesson $lesson)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $result = LessonUnlock::unlockForUser(
            $lesson->id,
            $lesson->course_id,
            $request->student_id
        );

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Lesson unlocked successfully',
                'release_date' => $lesson->releasePlan(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lesson is already unlocked for this user',
            'release_date' => $lesson->releasePlan(),
        ]);
    }

    public function lock(Request $request, Lesson $lesson)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        LessonUnlock::lockForUser($lesson->id, $request->student_id);

        return response()->json([
            'success' => true,
            'message' => 'Lesson locked successfully',
            'release_date' => $lesson->releasePlan(),
        ]);
    }
}
