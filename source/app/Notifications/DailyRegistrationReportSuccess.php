<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyRegistrationReportSuccess extends Notification
{
    use Queueable;

    protected array $reportData;

    public function __construct(array $reportData) {
        $this->reportData = $reportData;
    }

    public function via($notifiable): array {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage {
        $count = $this->reportData['count'];
        $reportDate = $this->reportData['report_date'];
        $filename = $this->reportData['filename'];
        $fileUrl = $this->reportData['file_url'] ?? '#';

        return (new MailMessage)
            ->subject("Daily Registration Report - {$reportDate} ({$count} registrations)")
            ->greeting("Daily Registration Report Generated")
            ->line("The daily registration report for **{$reportDate}** has been successfully generated.")
            ->line("**Total Registrations:** {$count}")
            ->line("**Report Date:** {$reportDate}")
            ->line("**File Name:** {$filename}")
            ->action('View Report', $fileUrl)
            ->line('The report is available in the SharePoint folder and includes all new student registrations and additional course assignments for the reporting period.')
            ->line('Thank you for using Key Institute LMS!');
    }
}
