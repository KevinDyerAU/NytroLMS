<?php

namespace App\Jobs;

use App\Models\AdminReport;
use App\Models\Topic;
use App\Services\AdminReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAdminReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Topic $topic;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Topic $topic)
    {
        $this->topic = $topic;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        logger([$this->topic->toArray()]);

        $course = $this->topic->course;

        $adminReports = AdminReport::where('course_id', $course->id)->get();
        logger('Start Updating Admin Report upon Topic Time Update', ['ids' => $adminReports->pluck('id')]);

        foreach ($adminReports as $adminReport) {
            $adminReportService = new AdminReportService($adminReport->student_id, $adminReport->course_id);
            $adminReportService->updateCourseProgress();

            activity('UpdateAdminReport')
                ->event('TopicTimeUpdated')
                ->causedBy(auth()->user())
                ->performedOn($adminReport)
                ->withProperties([
                    'student_id' => $adminReport->student_id,
                    'course_id' => $adminReport->course_id,
                ])
                ->log('Updated Admin Report upon Topic Time Update');
            logger('Updated Admin Report upon Topic Time Update', ['id' => $adminReport->id]);
        }
    }
}
