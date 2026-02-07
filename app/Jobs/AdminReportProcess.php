<?php

namespace App\Jobs;

use App\Models\AdminReport;
use App\Services\AdminReportService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdminReportProcess implements ShouldQueue
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
        //        \Log::info('AdminReportProcess for student ids', $studentsIds);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //        \Log::info('handling AdminReportProcess', $this->students);

        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }
        foreach ($this->students as $student_id) {
            $adminReports = AdminReport::where('student_id', $student_id)->get();
            foreach ($adminReports as $adminReport) {
                $adminReportService = new AdminReportService($adminReport->student_id, $adminReport->course_id);
                $adminReportService->updateStudentDetails($adminReport->student_id);
                $adminReportService->updateCourseProgress();
            }
        }
    }
}
