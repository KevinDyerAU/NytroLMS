<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonEndDate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getEndDateAttribute($value)
    {
        if (empty($value)) {
            return false;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }
}
