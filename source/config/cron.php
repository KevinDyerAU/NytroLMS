<?php

return [
    'notifications' => [
        // Email address to receive summary reports
        'email' => env('CRON_NOTIFICATION_EMAIL'),

        // Incoming Slack webhook URL for posting notifications
        'slack_webhook' => env('CRON_SLACK_WEBHOOK_URL'),
    ],
];
