<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     *
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // cd /home/rdyeiay7wk0z/v2.keyinstitute.com.au && /usr/local/bin/php artisan schedule:run >> /home/rdyeiay7wk0z/cron-schedule.log 2>&1
        // cd /home/rdyeiay7wk0z/v2.keyinstitute.com.au && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
        //        $schedule->command('email:send-test')
        //                 ->everyThreeMinutes()
        //                 ->appendOutputTo(base_path('logs/cron-jobs.log'));

        $schedule->command('email:send-inactivity 9')->weeklyOn(6, '00:15')->timezone('Australia/Sydney');
        $schedule->command('email:send-inactivity 10')->weeklyOn(6, '00:45')->timezone('Australia/Sydney');

        $schedule->command('telescope:prune')->daily();

        $schedule->command('cronjob:reset-cursor student-sync')
            ->dailyAt('22:05')
            ->timezone('Australia/Sydney');

        //        $scheduleTimes = ['22:10', '23:10', '00:10', '01:10', '02:10', '03:10', '04:10', '05:10', '06:10', '07:10'];
        $scheduleTimes = ['22:10'];

        // Schedule the active command
        foreach ($scheduleTimes as $time) {
            $schedule->command('student-sync:process active')
                ->withoutOverlapping()
                ->days([
                        Schedule::SUNDAY,
                        Schedule::MONDAY,
                        Schedule::TUESDAY,
                        Schedule::WEDNESDAY,
                        Schedule::THURSDAY,
                    ])
                ->at($time) // Use `at` instead of `dailyAt` for specific times
                ->timezone('Australia/Sydney');
        }

        // Schedule the inactive student sync command
        foreach ($scheduleTimes as $time) {
            $schedule->command('student-sync:process all')
                ->withoutOverlapping()
                ->days([
                        Schedule::FRIDAY,
                        Schedule::SATURDAY,
                    ])
                ->at($time)
                ->timezone('Australia/Sydney');
        }

        // Schedule the retry command (optional, e.g., run at 08:00 on weekdays)
        $schedule->command('student-sync:retry-failed')
            ->withoutOverlapping()
            ->dailyAt('08:00')
            ->timezone('Australia/Sydney');

        // Generate daily registration report at 02:00 Australia/Sydney
        $schedule->command('report:daily-registrations')
            ->withoutOverlapping()
            ->dailyAt('02:00')
            ->timezone('Australia/Sydney');

        //        $schedule->command('queue:work',[
        //            '--queue' => 'high,default',
        //            '--tries' => 3
        //            ])
        //            ->cron('* * * * *')
        //            ->withoutOverlapping(5)
        //            ->appendOutputTo(base_path( 'logs/queue-jobs.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
