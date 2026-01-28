<?php

namespace App\Jobs;

use App\Models\CourseProgress;
use App\Services\CourseProgressService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CourseProgressProcess implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $students;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($studentsIds)
    {
        $this->students = $studentsIds;
        //        \Log::info('CourseProgressProcess for student ids', $studentsIds);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //        \Log::info('handling CourseProgressProcess', $this->students);

        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        foreach ($this->students as $student_id) {
            $courseProgresses = CourseProgress::where('user_id', intval($student_id))->where('course_id', '!=', config('constants.precourse_quiz_id', 0))->get();
            CourseProgressService::updateStudentProgress($student_id, $courseProgresses);
            //            CourseProgressService::updateStudentProgressWithModel($student_id, $courseProgresses);
        }
    }
}
