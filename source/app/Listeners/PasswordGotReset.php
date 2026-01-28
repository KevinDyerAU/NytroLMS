<?php

namespace App\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;

class PasswordGotReset
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
    public function handle(PasswordReset $event)
    {
        $event->user->password_change_at = Carbon::now();
        $event->user->save();

        activity('audit')
            ->event('AUTH')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties([
                'ip' => request()->ip(),
                'event' => $event,
            ])
            ->log('Password Reset');
    }
}
