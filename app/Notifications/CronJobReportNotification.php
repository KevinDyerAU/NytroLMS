<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class CronJobReportNotification extends Notification
{
    use Queueable;

    protected array $report;

    public function __construct(array $report)
    {
        $this->report = $report;
    }

    public function via($notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail($notifiable): MailMessage
    {
        $updatedCount = $this->report['updatedCount'] ?? 0;
        $failedCount = $this->report['failedCount'] ?? 0;
        $runTime = $this->report['run_time'] ?? 0;
        $memoryUsage = $this->report['memory_usage'] ?? 0;
        $status = $this->report['status'] ?? '';
        $env = config('app.env');
        $name = config('app.name');
        $url = config('app.url');

        return (new MailMessage())
            ->subject("[Cron Report] {$this->report['cronjob']}")
            ->line("Status: {$status}")
            ->line("Updated Records: {$updatedCount}")
            ->line("Failed Records: {$failedCount}")
            ->line("Run Time: {$runTime}")
            ->line("Memory Usage: {$memoryUsage}")
            ->line("Environment: {$env}")
            ->line("Application: {$name}")
            ->line("Web URL: {$url}")
            ->line("Completed At: {$this->report['runAt']}");
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->success()
            ->content("[Cron Report] {$this->report['cronjob']} completed.")
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Status' => $this->report['status'] ?? '',
                    'Updated' => $this->report['updatedCount'] ?? 0,
                    'Failed' => $this->report['failedCount'] ?? 0,
                    'Run Time' => $this->report['run_time'] ?? 0,
                    'Memory Usage' => $this->report['memory_usage'] ?? 0,
                    'App ENV' => config('app.env'),
                    'App Name' => config('app.name'),
                    'App URL' => config('app.url'),
                    'Completed At' => $this->report['runAt'],
                ]);
            });
    }
}
