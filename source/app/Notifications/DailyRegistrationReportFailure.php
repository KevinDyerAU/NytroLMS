<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyRegistrationReportFailure extends Notification
{
    use Queueable;

    protected array $errorData;

    public function __construct(array $errorData) {
        $this->errorData = $errorData;
    }

    public function via($notifiable): array {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage {
        $reportDate = $this->errorData['report_date'] ?? 'Unknown';
        $error = $this->errorData['error'] ?? 'Unknown error';
        $message = $this->errorData['message'] ?? 'Failed to generate report';

        return (new MailMessage)
            ->error()
            ->subject("Daily Registration Report Failed - {$reportDate}")
            ->greeting("Daily Registration Report Generation Failed")
            ->line("The daily registration report for **{$reportDate}** failed to generate.")
            ->line("**Error:** {$error}")
            ->line("**Details:** {$message}")
            ->line('Please check the application logs for more details or contact the system administrator.')
            ->line('Report generation can be manually triggered using the admin panel.');
    }
}
