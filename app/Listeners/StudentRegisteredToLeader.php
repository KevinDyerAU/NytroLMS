<?php

namespace App\Listeners;

use App\Models\StudentCourseEnrolment;
use App\Notifications\AnacondaCourseNotification;
use App\Notifications\StudentAssignedCourse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentRegisteredToLeader
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
    public function handle(Registered $event)
    {
        $eventUser = $event->user;

        if (!$eventUser->isStudent()) {
            return '';
        }
        $leader = (!empty($eventUser->leaders()) && $eventUser->leaders()->count() > 0) ? $eventUser->leaders()->first()->user : null;
        if ($leader) {
            activity('communication')
                ->event('NOTIFICATION')
                ->causedBy(auth()->user())
                ->performedOn($leader)
                ->withProperties([
                    'for' => 'Student Registered',
                    'action' => 'email sent to leader',
                    'event' => $event,
                    'event_user' => $eventUser,
                    'leader' => $leader,
                ])
                ->log('Email Notification');
            //            Log::info( 'StudentRegisteredToLeader', [
            //                'leader' => $leader,
            //                'event' => $event,
            //                'event_user' => $eventUser,
            //            ] );

            $enrolledRecord = StudentCourseEnrolment::with(['student', 'course'])->where('user_id', intval($eventUser->id))->get();

            foreach ($enrolledRecord as $record) {
                $course = $record->course;
                \Log::info('Student# '.$eventUser->id.' '.$eventUser->name.' Registered to '.$course->name.' '.$course->category);
                if (!(Str::contains(Str::lower($course->title), ['semester 2', 'SEMESTER 2', 'Semester 2']))) {
                    if (\Str::lower($course->category) === 'anaconda') {
                        $leader->notify(new AnacondaCourseNotification($eventUser));
                    } else {
                        $leader->notify(new StudentAssignedCourse($eventUser));
                    }
                }
            }

            //            $leader->notify( new StudentAssignedCourse( $eventUser ) );
        } else {
            \Log::debug('Leader Not found for student', ['student' => $eventUser, 'leader' => $leader]);
        }
    }
}
