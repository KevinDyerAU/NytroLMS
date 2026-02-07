<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    protected $guarded = [];

    protected $casts = [
        'notes' => AsCollection::class,
        'param' => AsCollection::class,
        'competent_on' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userDetails(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserDetail::class, 'user_id', 'user_id');
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function evidence(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StudentLMSAttachables::class);
    }

    public function studentCourseEnrolment()
    {
        //        return $this->belongsTo(StudentCourseEnrolment::class, 'user_id', 'user_id')
        //                    ->where('course_id', $this->course_id);
        return $this->belongsTo(StudentCourseEnrolment::class, 'course_id', 'course_id');
    }
}
