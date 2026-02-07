<?php

namespace App\Services;

use App\Notifiables\CronJobNotifier;
use App\Notifications\CronJobReportNotification;
use Illuminate\Support\Facades\DB;

class CronJobManagerService
{
    protected string $cronjob;

    protected string $email;

    protected string $slackWebhook;

    protected string $status;

    public function __construct(string $cronjob, string $status)
    {
        $this->cronjob = $cronjob;
        $this->email = config('cron.notifications.email');
        $this->slackWebhook = config('cron.notifications.slack_webhook');
        $this->status = $status;
    }

    public function getCursor(): array
    {
        $cursor = DB::table('cron_job_cursors')
            ->where('cronjob', $this->cronjob)
            ->first();

        return [
            'lastId' => $cursor->last_processed_id ?? 0,
            'lastRunAt' => $cursor->last_run_at ?? null,
        ];
    }

    public function updateCursor(int $lastProcessedId, ?float $durationSeconds = null): void
    {
        $updates = [
            'last_processed_id' => $lastProcessedId,
            'last_run_at' => now(),
            'created_at' => DB::raw('IFNULL(created_at, "'.now()->setTimezone('GMT')->toDateTimeString().'")'),
            'updated_at' => now(),
        ];

        if ($durationSeconds !== null) {
            // add to existing total_duration_seconds
            $durationSeconds = round($durationSeconds, 4);
            $updates['total_duration_seconds'] = DB::raw("total_duration_seconds + {$durationSeconds}");
        }

        DB::table('cron_job_cursors')->updateOrInsert(
            ['cronjob' => $this->cronjob],
            $updates
        );
    }

    public function logFailure(int $jobId, \Throwable $e, $notify = true): void
    {
        DB::table('cron_job_failures')->insert([
            'cronjob' => $this->cronjob,
            'job_identifier' => $jobId,
            'error_message' => substr($e->getMessage(), 0, 1000),
            'retry_attempts' => 0,
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($notify) {
            (new CronJobNotifier())->notify(new CronJobReportNotification([
                'cronjob' => $this->cronjob,
                'status' => $this->status,
                'updatedCount' => 0,
                'failedCount' => 1,
                'runAt' => now()->toDateTimeString(),
            ]));
        }
    }

    public function sendSummary(int $updatedCount, int $failedCount, $options = ['run_time' => '', 'memory_usage' => '']): void
    {
        (new CronJobNotifier())->notify(new CronJobReportNotification([
            'cronjob' => $this->cronjob,
            'status' => $this->status,
            'updatedCount' => $updatedCount,
            'failedCount' => $failedCount,
            'run_time' => $options['run_time'] ?? '',
            'memory_usage' => $options['memory_usage'] ?? '',
            'runAt' => now()->toDateTimeString(),
        ]));
    }

    /**
     * Get total run time for today (accumulated).
     */
    public function getTotalRunTimeForToday(): float
    {
        $record = DB::table('cron_job_cursors')
            ->where('cronjob', $this->cronjob)
            ->first();

        return $record->total_duration_seconds ?? 0;
    }
}
