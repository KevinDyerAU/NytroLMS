<?php

namespace App\Jobs;

use App\Models\CourseProgress;
use App\Models\Topic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class UpdateCourseProgress implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Topic $topic;

    /**
     * Create a new job instance.
     */
    public function __construct(Topic $topic)
    {
        $this->topic = $topic;
        Log::info('Call UpdateCourseProgress Job');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger('Initialized UpdateCourseProgress Job', $this->topic->toArray());

        $course = $this->topic->course;

        $courseProgresses = CourseProgress::where('course_id', $course->id)->get();

        logger('Start Updating Course Progress upon Topic Time Update', ['ids' => $courseProgresses->pluck('id')]);

        foreach ($courseProgresses as $courseProgress) {
            $progress = $courseProgress->details->toArray();

            $topic_id = $this->topic->id;
            $lesson_id = $this->topic->lesson->id;

            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['data'] = Topic::where('id', $topic_id)->first()->toArray();

            $courseProgress->details = $progress;
            $courseProgress->save();

            activity('UpdateCourseProgress')
                ->event('TopicTimeUpdated')
                ->causedBy(auth()->user())
                ->performedOn($courseProgress)
                ->withProperties([
                    'user_id' => $courseProgress->user_id,
                    'course_id' => $courseProgress->course_id,
                ])
                ->log('Updated Course Progress upon Topic Time Update');
            logger('Updated Course Progress upon Topic Time Update', ['id' => $courseProgress->id]);
        }
    }
}
