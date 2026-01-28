<?php

namespace App\Listeners;

use App\Events\Authenticated;
use App\Helpers\Helper;
use App\Services\AdminReportService;
use App\Services\StudentActivityService;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class LogSuccessfulLogin
{
    public StudentActivityService $studentActivityService;

    /**
     * Create the event listener.
     */
    public function __construct(StudentActivityService $studentActivityService)
    {
        //
        $this->studentActivityService = $studentActivityService;
    }

    /**
     * Handle the event.
     *
     *
     * @return void
     */
    public function handle(Authenticated $event)
    {
        // last_logged_in
        $datetime = Carbon::now();
        $event->user->detail()->update(['last_logged_in' => $datetime]);
        //        app()[StudentActivityService::class]->userLoggedIn(auth()->user()->id);
        $timezone = Helper::getTimeZone();
        if (auth()->user()->isStudent()) {
            AdminReportService::updateStudent(auth()->user(), ['student_last_active' => $datetime]);
            $this->studentActivityService->setActivity(['user_id' => auth()->user()->id, 'activity_event' => 'SIGN IN', 'activity_details' => ['set_timezone' => $timezone, 'at' => $datetime, 'time_converted' => false]], auth()->user());
        }
        activity('audit')
            ->event('AUTH')
            ->causedBy(auth()->user())
            ->performedOn(auth()->user())
            ->withProperties(['ip' => request()->ip(), 'user_role' => auth()->user()->roleName(), 'time' => ['set_timezone' => $timezone, 'at' => $datetime, 'converted' => false]])
            ->log('SIGN IN');

        //        dd(auth()->user()->detail, auth()->user());

        if (!empty(auth()->user()->detail->last_logged_in)) {
            $first_login = auth()->user()->detail->first_login ?? '';

            //            if ( empty( $first_login )
            //                || $first_login === '0000-00-00 00:00:00'
            //                || Carbon::parse( $first_login )->equalTo( Carbon::parse( auth()->user()->created_at ) ) ) {
            $activity = Activity::where('causer_id', auth()->user()->id)
                ->where('event', 'AUTH')
                ->where('log_name', 'audit')
                ->where('description', 'SIGN IN')->first();
            $first_logged_in = !empty($activity) ? $activity->getRawOriginal('created_at') : '';
            if (!empty($first_logged_in)) {
                // IF NO ACTIVITY is present e.g. before 8/15/2023
                //                if ( empty( $activity )
                //                    || (Carbon::parse( $first_logged_in )->equalTo( Carbon::parse( auth()->user()->detail->getRawOriginal( 'last_logged_in' ) ) )
                //                        && auth()->user()->detail->status !== "CREATED") ) {
                //                    $first_logged_in = auth()->user()->getRawOriginal( 'created_at' );
                //                }
                $userDetails = auth()->user()->detail;
                $userDetails->first_login = $first_logged_in;
                $userDetails->save();
                //                }
            }
        }
    }
}
