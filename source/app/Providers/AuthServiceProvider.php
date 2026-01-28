<?php

namespace App\Providers;

use App\Models\Role;
use App\Policies\RolePolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //        VerifyEmail::toMailUsing(function ($notifiable, $url) {
        //            return (new MailMessage)
        //                ->subject('Verify Email Address')
        //                ->view('emails.users.verify',['url' => $url,'notifiable' => $notifiable]);
        // //                ->line('Click the button below to verify your email address.')
        // //                ->action('Verify Email Address', $url);
        //        });

        Gate::before(function ($user, $ability) {
            if ($user->isRoot()) {
                return true;
            }
        });
    }
}
