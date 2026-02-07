<?php

namespace App\Notifiables;

use Illuminate\Notifications\Notifiable;

class CronJobNotifier
{
    use Notifiable;

    public function routeNotificationForSlack(): string
    {
        return config('cron.notifications.slack_webhook');
    }

    public function routeNotificationForMail(): string
    {
        return config('cron.notifications.email');
    }
}
