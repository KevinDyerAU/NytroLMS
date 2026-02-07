<?php

namespace App\Listeners;

use App\Services\StudentActivityService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

class LogoutListener
{
    public StudentActivityService $activityService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Logout $event)
    {
        if (Auth::check() && auth()->user()->isStudent()) {
            $this->activityService->setActivity('SIGN OUT', auth()->user());
        }
    }
}
