<?php

namespace App\Providers;

use App\Events\Authenticated;
use App\Listeners\PasswordGotReset;
use App\Listeners\StudentRegisteredToLeader;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            StudentRegisteredToLeader::class,
        ],
        PasswordReset::class => [
            PasswordGotReset::class,
        ],
        Authenticated::class => [
            'App\Listeners\LogSuccessfulLogin',
        ],
        'Illuminate\Notifications\Events\NotificationSent' => [
            'App\Listeners\LogNotification',
        ],
        Logout::class => [
            'App\Listeners\LogoutListener',
        ],
        \App\Events\QuizAttemptStatusChanged::class => [
            \App\Listeners\UpdateLlnStatusListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
