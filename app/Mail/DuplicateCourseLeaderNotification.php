<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DuplicateCourseLeaderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $course;
    public $registeredBy;
    public $registrationData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $student,
        Course $course,
        User $registeredBy,
        array $registrationData
    ) {
        $this->student = $student;
        $this->course = $course;
        $this->registeredBy = $registeredBy;
        $this->registrationData = $registrationData;
    }

    /**
     * Build the message.
     */
    public function build() {
        return $this->view('emails.leaders.duplicate-course-receipt')
            ->subject('Student Course Assignment Receipt - ' . $this->student->name . ' (' . $this->student->email . ')')
            ->with([
                'student' => $this->student,
                'course' => $this->course,
                'registeredBy' => $this->registeredBy,
                'registrationData' => $this->registrationData,
            ]);
    }
}
