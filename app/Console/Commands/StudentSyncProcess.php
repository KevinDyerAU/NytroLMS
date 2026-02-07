<?php

namespace App\Console\Commands;

use App\Models\StudentCourseEnrolment;
use App\Services\CronJobManagerService;
use App\Services\StudentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StudentSyncProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student-sync:process {status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync student profiles';

    protected string $cronjob = 'student-sync';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $status = $this->argument('status') ?? 'all';

        $this->info("Starting student sync process for {$status} students...");

        $manager = new CronJobManagerService($this->cronjob, $status);
        $syncService = new StudentSyncService();
        $cursor = $manager->getCursor();
        $lastId = $cursor['lastId'];
        $lastRun = $cursor['lastRunAt'];
        $updated = $failed = 0;
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $query = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats', 'adminReport'])
            ->active()
            ->where('id', '>', $lastId);

        if ($status === 'active') {
            $query->whereHas('student', function ($query) {
                $query->where('is_active', 1);
            });
        } elseif ($status === 'inactive') {
            $query->whereHas('student', function ($query) {
                $query->where('is_active', 0);
            });
        }

        $query->orderBy('id')
            ->chunk(5000, function ($chunk) use ($syncService, $manager, &$updated, &$failed) {
                foreach ($chunk as $enrolment) {
                    try {
                        $startTime1 = microtime(true);
                        $syncService->syncProfile($enrolment); // Your main logic
                        $updated++;
                        $duration = microtime(true) - $startTime1;
                        $manager->updateCursor($enrolment->id, $duration);
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error("Failed enrolment ID {$enrolment->id}: ".$e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $manager->logFailure($enrolment->id, $e, false); // This logs to cron_job_failures + notifies Slack
                        // Cursor is not updated here to retry this job in future
                    }
                }
                //                                  return FALSE; // Stop after first chunk
            });
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $totalRun = $manager->getTotalRunTimeForToday() ?? ($endTime - $startTime);
        $run_time = round($totalRun, 4).' seconds ('.round(($totalRun) / 60, 2).' minutes)';
        $memory_usage = round(($endMemory - $startMemory) / (1024 * 1024), 2).' MB';

        $manager->sendSummary($updated, $failed, ['run_time' => $run_time, 'memory_usage' => $memory_usage]);
        $this->info('Student sync process completed.');
        $this->info("Updated Records: {$updated}");
        $this->info("Failed Records: {$failed}");
        $this->info('Last Run At: '.now()->toDateTimeString());
        $this->info('Last Run Time: '.$run_time);
        $this->info('Last Memory Usage: '.$memory_usage);
    }
}
