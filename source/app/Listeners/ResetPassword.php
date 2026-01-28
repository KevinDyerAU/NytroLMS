<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class ResetPassword
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
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if (empty($user->password_change_at)
                && empty($user->detail->last_logged_in)) {
            return redirect(route('profile.password', $user));
        }
        dd(['Login Event Reset Password' => $event]);
    }
}
