<?php

namespace App\Listeners;

use Carbon\Carbon;
use Illuminate\Notifications\Events\NotificationSent;

class LogNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(NotificationSent $event)
    {
        if (!$event->notifiable instanceof \Illuminate\Database\Eloquent\Model) {
            return; // Skip logging for non-model notifiables
        }

        if ($event->channel === 'mail') {
            //            dd($event->notification);
            activity('communication')
                ->event('NOTIFICATION')
                ->causedBy(auth()->user())
                ->performedOn($event->notifiable)
                ->withProperties($this->getLogProperties($event->notification))
                ->log('Email Notification');
        }
    }

    private function getLogProperties($notification)
    {
        $properties = [];
        if (isset($notification->attempt) && !empty($notification->attempt)) {
            $notification->attempt->evaluation()->update(['email_sent_on' => Carbon::now()]);
            $properties = [
                'on' => 'Quiz Attempt',
                'quiz_attempt' => $notification->attempt->id,
            ];
        }

        return $properties;
    }
}
