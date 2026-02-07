<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentActivity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'activity_details' => AsCollection::class,
        'activity_on' => 'datetime',
    ];

    public function actionable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function getActivityDetailsAttribute($value)
    {
        $output = '';
        $details = $this->fromJson($value);
        if (!empty($details) && !empty($this->actionable)) {
            $related = $this->actionable;
            $event = $this->activity_event;
            switch ($event) {
                case $event === 'LESSON MARKED':
                case $event === 'QUIZ AUTO MARKED':
                    $output = 'Quiz: '.$related->title.'<br/>'.$details['status'];

                    break;
                case $event === 'TOPIC MARKED':
                case $event === 'TOPIC END':
                    $output = 'Topic: '.$related->title.'<br/>'.($details['status'] ?? '').(empty($details['time_spent']) ? '' : '<br/> Time Spent '.$details['time_spent']);

                    break;
                case $event === 'QUIZ ATTEMPT':
                    $output = 'Quiz: '.$related->title.'<br/>'.$details['status'].' out of '.$details['total_quizzes'].(empty($details['time_spent']) ? '' : ',<br/> Time Spent '.$details['time_spent']).(empty($details['topic_time']) ? '' : ',<br/> Total topic time is '.$details['topic_time']);

                    break;
                case $event === 'ENROLMENT':
                    $output = 'Successfully '.Str::title($details['status']);

                    break;
                case $event === 'LESSON START':
                case $event === 'LESSON END':
                    $output = 'Lesson: '.$related->title.'<br/>'.' at '.Carbon::parse($this->created_at)->timezone(Helper::getTimeZone())->isoFormat('llll');

                    break;
                default:
                    break;
            }
        }
        if (empty($output)) {
            $output = Carbon::parse($this->created_at)->timezone(Helper::getTimeZone())->isoFormat('llll');
        }

        return $output;
    }
}
