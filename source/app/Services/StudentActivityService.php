<?php

namespace App\Services;

use App\Models\StudentActivity;
use App\Repository\Contracts\StudentActivityRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StudentActivityService
{
    public StudentActivityRepositoryInterface $activity;

    /**
     * Flag to prevent recursive activity logging.
     */
    private static bool $isLogging = false;

    public function __construct(StudentActivityRepositoryInterface $activity)
    {
        $this->activity = $activity;
    }

    public function setActivity($data, ?Model $model = null)
    {
        // Guard against recursive activity logging
        if (self::$isLogging) {
            return null;
        }

        self::$isLogging = true;

        try {
            if (!is_array($data)) {
                $input = $data;
                $data = [];
                $data['activity_event'] = $input;
            }
            if (!empty($model)) {
                $data['actionable_type'] = $model::class;
                $data['actionable_id'] = $model->id;
            }
            //        if($model::class === 'App\Models\Topic' || $model::class === 'App\Models\Lesson' || $model::class === 'App\Models\Quiz' || $model::class === 'App\Models\QuizAttempt'){
            //            $data['course'] = $model->course->id;
            //        }
            $data['course_id'] = $model->course?->id;
            if (isset($data['activity_details']) && !is_array($data['activity_details'])) {
                $data['activity_details'] = json_decode($data['activity_details'], true);
            }
            if ($data['activity_event'] === 'TOPIC END' && !empty($data['activity_details']['topic_time'])) {
                $data['time_spent'] = $data['activity_details']['topic_time'];
            }

            if (!isset($data['activity_on']) || empty($data['activity_on'])) {
                $data['activity_on'] = Carbon::now()->toDateTimeString();
            }
            if (!empty($data['activity_details']['activity_on'])) {
                $timeString = \Str::contains($data['activity_details']['activity_on'], [' ', '-', '/']) ? strtotime($data['activity_details']['activity_on']) : $data['activity_details']['activity_on'];
                $activityDetailsOn = Carbon::parse($timeString); // '11 May, 2023 7:18 AM'
                $activityOn = Carbon::parse($data['activity_on']);
                if ($activityDetailsOn->lessThanOrEqualTo($activityOn)) {
                    $data['activity_on'] = $activityDetailsOn->toDateTimeString();
                }
            }

            if (!empty($data['activity_details']['user_id'])) {
                $userId = intval($data['activity_details']['user_id']);
                if ($userId !== intval($data['user_id'])) {
                    $data['user_id'] = $userId;
                }
            }

            if (empty($data['user_id'])) {
                return null;
            }

            $activityTime = $activity_details['activity_by__at'] ?? $activity_details['at'] ?? \Carbon\Carbon::now()->toDateTimeString();
            $data['activity_details'] = array_merge(
                $data['activity_details'],
                [
                    'ip' => request()->ip(),
                    'activity_by__at' => $activityTime,
                    'activity_by_id' => intval($activity_details['activity_by_id'] ?? $activity_details['id'] ?? auth()->check() ? auth()->user()->id : 0),
                    'activity_by_role' => $activity_details['activity_by_role'] ?? $activity_details['by'] ?? auth()->check() ? auth()->user()->roleName() : '',
                    'is_cron_job' => auth()->check() ? 'No' : 'Yes',
                ]
            );

            //        if($data[ 'activity_event' ] === 'TOPIC END') {
            //            dd( $data );
            //        }
            //        \Log::debug('CreateActivity', $data);
            return $this->activity->create($data);
        } finally {
            self::$isLogging = false;
        }
    }

    public function updateActivity($data, StudentActivity $activity, ?Model $model = null)
    {
        // Guard against recursive activity logging
        if (self::$isLogging) {
            return null;
        }

        self::$isLogging = true;

        try {
            if (!is_array($data)) {
                $input = $data;
                $data = [];
                $data['activity_event'] = $input;
            }

            if (!empty($model)) {
                $data['actionable_type'] = $model::class;
                $data['actionable_id'] = $model->id;
            }
            //        if($model::class === 'App\Models\Topic' || $model::class === 'App\Models\Lesson' || $model::class === 'App\Models\Quiz' || $model::class === 'App\Models\QuizAttempt'){
            //            $data['course'] = $model->course->id;
            //        }
            $data['course_id'] = $model->course?->id;

            $dataActivityDetails = $data['activity_details'];
            $data['activity_details'] = $activity->getRawOriginal('activity_details');

            if (!is_array($activity->getRawOriginal('activity_details'))) {
                $data['activity_details'] = json_decode($activity->getRawOriginal('activity_details'), true);
            }
            if ($activity->activity_event === 'TOPIC END' && !empty($data['activity_details']['topic_time'])) {
                $data['time_spent'] = $data['activity_details']['topic_time'];
            }

            if (!isset($dataActivityDetails['activity_on']) || empty($dataActivityDetails['activity_on'])) {
                $data['activity_on'] = !empty($data['activity_details']['activity_on']) ?
                    Carbon::parse($data['activity_details']['activity_on'])->toDateTimeString()
                    : Carbon::now()->toDateTimeString();
            }
            if (!empty($dataActivityDetails['activity_on'])) {
                $timeString = \Str::contains($dataActivityDetails['activity_on'], [' ', '-', '/']) ? strtotime($dataActivityDetails['activity_on']) : $dataActivityDetails['activity_on'];
                $activityDetailsOn = Carbon::parse($timeString);
                $activityOn = Carbon::parse($activity->activity_on);
                if ($activityDetailsOn->lessThanOrEqualTo($activityOn)) {
                    $data['activity_on'] = $activityDetailsOn->toDateTimeString();
                }
            }

            if (!empty($dataActivityDetails['user_id'])) {
                $userId = intval($dataActivityDetails['user_id']);
                $data['user_id'] = $userId;
            }
            if (empty($data['user_id'])) {
                if ($model::class === 'App\Models\User') {
                    $data['user_id'] = $model->id;
                }
            }
            $activityTime = $dataActivityDetails['activity_by__at'] ?? $dataActivityDetails['at'] ?? \Carbon\Carbon::now();
            $data['activity_details'] = array_merge(
                $data['activity_details'],
                $dataActivityDetails,
                [
                    'ip' => request()->ip(),
                    'activity_by__at' => Carbon::parse($activityTime)->toDateTimeString(),
                    'activity_by_id' => intval($dataActivityDetails['activity_by_id']
                        ?? $dataActivityDetails['id']
                        ?? auth()->check()
                        ? auth()->user()?->id
                        : 0),
                    'activity_by_role' => $dataActivityDetails['activity_by_role'] ?? $dataActivityDetails['by'] ?? auth()->check() ? auth()->user()?->roleName() : '',
                    'is_cron_job' => auth()->check() ? 'No' : 'Yes',
                ]
            );

            //        \Log::debug('updateActivity', $data);
            // return StudentActivity::where('id',$activity->id)->update($data);
            return $activity->update($activity->toArray(), $data);
        } finally {
            self::$isLogging = false;
        }
    }

    public function getActivityForUser($user_id)
    {
        return $this->activity->findBy('user_id', $user_id);
    }

    public function getActivityWhere($condition, $relation = [])
    {
        return $this->activity->findWhere($condition, ['*'], $relation);
    }

    public function delete($id)
    {
        return $this->activity->deleteById($id);
    }
}
