<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LessonUnlock extends Model
{
    protected $fillable = [
        'lesson_id',
        'course_id',
        'user_id',
        'unlocked_by',
        'unlocked_at',
    ];

    protected $dates = [
        'unlocked_at',
        'created_at',
        'updated_at',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unlockedBy()
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public static function isUnlockedForUser($lessonId, $userId, $courseId)
    {
        return static::where('lesson_id', $lessonId)
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }

    public static function unlockForUser($lessonId, $courseId, $userId, $unlockedBy = null)
    {
        // Start the day at midnight
        $unlockDate = Carbon::now()->startOfDay();

        return static::updateOrCreate(
            [
                'lesson_id' => $lessonId,
                'course_id' => $courseId,
                'user_id' => $userId,
            ],
            [
                'unlocked_by' => $unlockedBy ?? auth()->id(),
                'unlocked_at' => $unlockDate,
            ]
        );
    }

    public static function lockForUser($lessonId, $userId)
    {
        return static::where('lesson_id', $lessonId)
            ->where('user_id', $userId)
            ->delete();
    }
}
