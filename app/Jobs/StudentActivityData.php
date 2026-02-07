<?php

namespace App\Jobs;

use App\Models\StudentActivity;
use App\Services\CourseProgressService;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StudentActivityData implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $students;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($studentsIds)
    {
        $this->students = $studentsIds;
        //        \Log::info('StudentActivityData for student ids', $studentsIds);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //        \Log::info('handling StudentActivityData', $this->students);

        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }
        foreach ($this->students as $student_id) {
            $this->updateStudentActivity($student_id);
        }
    }

    protected function updateStudentActivity($student_id)
    {
        $activities = StudentActivity::with('actionable', 'course')
            ->whereIn('activity_event', ['LESSON MARKED', 'LESSON END', 'LESSON START', 'TOPIC MARKED', 'TOPIC END', 'TOPIC START'])
            ->where(function ($query) use ($student_id) {
                $query->where('activity_details', 'LIKE', "%student\":{$student_id},%")
                    ->orWhere('user_id', $student_id);
            })->get();
        $userId = $student_id;
        foreach ($activities as $activity) {
            $activity_details = json_decode($activity->getRawOriginal('activity_details'), true);
            if (!empty($activity_details['student']) || !empty($activity_details['student_id'])) {
                $userId = $activity_details['student'] ?? $activity_details['student_id'] ?? $activity_details['user_id'];
                $activity->user_id = $userId;
            }

            if ($activity->activity_event === 'TOPIC END') {
                $activity->time_spent = $activity_details['topic_time'];
            }

            if (in_array($activity->activity_event, ['LESSON MARKED', 'LESSON END', 'LESSON START', 'TOPIC MARKED', 'TOPIC END', 'TOPIC START'])) {
                $activityTime = $activity_details['activity_on'] ?? $activity_details['activity_by__at'] ?? $activity_details['at'] ?? null;
                $activity->activity_details = array_merge($activity_details, [
                    'student' => intval($userId),
                    'user_id' => intval($userId),
                    'activity_by__at' => $activityTime,
                    'activity_by_id' => intval($activity_details['activity_by_id'] ?? $activity_details['id'] ?? auth()->check() ? auth()->user()?->id : 0),
                    'activity_by_role' => $activity_details['activity_by_role'] ?? $activity_details['by'] ?? auth()->check() ? auth()->user()?->roleName() : '',
                ]);
                if ($activity->activity_event === 'TOPIC END') {
                    $lesson_id = $activity->actionable->lesson_id;
                    //                    dump( [ $activity->user_id, $activity->course_id, 'lessons.list.' . $lesson_id . '.topics.list.' . $activity->actionable_id ] );
                    if (!empty($lesson_id)) {
                        $activityTime = CourseProgressService::getActivityTime(
                            $activity->user_id,
                            $activity->course_id,
                            'lessons.list.'.$lesson_id.'.topics.list.'.$activity->actionable_id
                        );
                    }
                }
                if (empty($activityTime)) {
                    $activityTime = \Carbon\Carbon::now()->toDateTimeString();
                }
                if (Carbon::parse($activity->activity_on)->greaterThan(Carbon::parse($activityTime))) {
                    $activity->activity_on = Carbon::parse($activityTime)->toDateTimeString();
                }
            }

            if (!empty($activity->actionable)) {
                $actionable = $activity->actionable;
                if (!empty($actionable->course_id)) {
                    $activity->course_id = $actionable->course_id;
                } elseif (method_exists($actionable, 'course') && count($actionable->course) > 0) {
                    $activity->course_id = $actionable->load('course')->course->id;
                }
            }
            $activity->save();
        }
    }
}
