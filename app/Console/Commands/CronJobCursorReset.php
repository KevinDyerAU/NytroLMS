<?php

namespace App\Console\Commands;

use App\Notifiables\CronJobNotifier;
use App\Notifications\CronJobReportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CronJobCursorReset extends Command
{
    protected $signature = 'cronjob:reset-cursor {cronjob}';

    protected $description = 'Reset the cursor for a specific cronjob';

    public function handle()
    {
        $cronjob = $this->argument('cronjob');
        DB::table('cron_job_cursors')->where('cronjob', $cronjob)->update([
            'last_processed_id' => 0,
            'total_duration_seconds' => 0,
            'created_at' => null,
            'updated_at' => now(),
        ]);
        (new CronJobNotifier())->notifyNow(new CronJobReportNotification([
            'cronjob' => 'cronjob:reset-cursor '.$cronjob,
            'runAt' => now()->toDateTimeString(),
        ]), ['mail']);
        $this->info("Cursor for [$cronjob] has been reset.");
    }
}
