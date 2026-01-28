<?php

namespace App\Console\Commands;

use App\Services\CronJobManagerService;
use App\Services\StudentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StudentSyncRetryFailed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student-sync:retry-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed student sync jobs';

    protected string $cronjob = 'student-sync';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Retrying failed jobs for {$this->cronjob}...");
        $syncService = new StudentSyncService();
        $manager = new CronJobManagerService($this->cronjob, 'retry-failed');

        $failures = DB::table('cron_job_failures')
            ->where('cronjob', $this->cronjob)
            ->where('retry_attempts', '<', 4)
            ->orderBy('id')
            ->get();

        $retried = 0;
        $stillFailed = 0;

        foreach ($failures as $failure) {
            try {
                $enrolment = \App\Models\StudentCourseEnrolment::findOrFail($failure->job_identifier);
                $syncService->syncProfile($enrolment);

                // ✅ Success: remove from failures
                DB::table('cron_job_failures')->where('id', $failure->id)->delete();
                $retried++;
            } catch (\Throwable $e) {
                // ❌ Still failing: update attempt and log again
                DB::table('cron_job_failures')
                    ->where('id', $failure->id)
                    ->update([
                        'retry_attempts' => DB::raw('retry_attempts + 1'),
                        'updated_at' => now(),
                    ]);

                $manager->logFailure($failure->job_identifier, $e, false); // 🔔 Notify via Slack

                $stillFailed++;
            }
        }

        $manager->sendSummary($retried, $stillFailed);

        $this->info("Retried: {$retried}");
        $this->info("Still Failed: {$stillFailed}");
    }
}
