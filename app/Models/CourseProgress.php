<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CourseProgress extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['course_enrolment_status'];

    protected $with = ['user.detail', 'course'];

    protected $casts = [
        'percentage' => AsCollection::class,
        'details' => AsCollection::class,
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCourseEnrolmentStatusAttribute()
    {
        return StudentCourseEnrolment::select('status')->where('user_id', $this->user_id)->where('course_id', $this->course_id)->first()?->status;
    }

    public function getPercentageAttribute($value): float
    {
        // dump('=== CourseProgress getPercentageAttribute START ===');
        // dump('Value passed: ' . $value);
        // dump('Course ID: ' . ($this->course_id ?? 'null'));
        // dump('User ID: ' . ($this->user_id ?? 'null'));

        if (empty($this->course)) {
            // dump('Course is empty, returning 0.00');
            return 0.00;
        }

        $stats = DB::table('courses')
            ->leftJoin('lessons', 'courses.id', '=', 'lessons.course_id')
            ->leftJoin('topics', 'courses.id', '=', 'topics.course_id')
            ->leftJoin('quizzes', 'courses.id', '=', 'quizzes.course_id')
            ->select(
                'courses.id',
                DB::raw('COUNT(DISTINCT lessons.id) as lesson_count'),
                DB::raw('COUNT(DISTINCT topics.id) as topic_count'),
                DB::raw('COUNT(DISTINCT quizzes.id) as quiz_count'),
                DB::raw('(COUNT(DISTINCT lessons.id) + COUNT(DISTINCT topics.id) + COUNT(DISTINCT quizzes.id)) as total_count')
            )
            ->where('courses.id', $this->course->id)
            ->groupBy('courses.id')
            ->first();

        if (!$stats) {
            return 0.00;
        }

        // Check if value is numeric (float/int) - if so, get totals from details
        // In local environment, always call getTotalCounts for debugging
        if (is_numeric($value) || app()->environment('local')) {
            // dump('Value is numeric or local environment - calling getTotalCounts');
            $detailsArray = $this->details ? $this->details->toArray() : [];
            $percentage = CourseProgressService::getTotalCounts($this->user->id, $detailsArray);
            // dump('getTotalCounts result: ' . json_encode($percentage));
            // Fix the database by saving the correct data
            $this->percentage = $percentage;
            $this->save();
            // dump('Saved updated percentage to database');
        } else {
            // dump('Value is not numeric - using json_decode');
            $percentage = json_decode($value, true);
            // dump('json_decode result: ' . json_encode($percentage));
        }
        // Don't override the total from getTotalCounts - it already has the correct count with LLND logic
        // $percentage['total'] = $stats->total_count;
        $percentage['total_breakdown'] = [
            'lesson_count' => $stats->lesson_count,
            'topic_count' => $stats->topic_count,
            'quiz_count' => $stats->quiz_count,
        ];
        $result = CourseProgressService::calculatePercentage($percentage, $this->user->id, $this->course->id);

        //        Helper::debug([
        //            'course_id' => $this->course->id,
        //            'user_id' => $this->user->id,
        //            'is_main_course' => $isMainCourse,
        //            'onboarded' => $onboarded,
        //            'stats' => $stats,
        //            'raw_value' => $value,
        //            'percentage_array' => $percentage,
        //            'final_percentage' => $finalPercentage,
        //            'result' => $result,
        //        ],'dd');
        // dump('Final result: ' . $result);
        return $result;
    }

    public function getCompletedAttribute()
    {
        $details = $this->details;

        return $details ? ($details['completed'] ?? false) : false;
    }
}
