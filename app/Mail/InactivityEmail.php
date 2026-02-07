<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InactivityEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $student;

    public $weeks;

    public $course;

    public $subject;

    public function __construct($student, $course, $weeks)
    {
        $this->course = $course;
        $this->student = $student;
        $this->weeks = $weeks;
        $this->subject = config('settings.site.institute_name', 'Key Institute').' – Inactivity Notification';
    }

    public function build()
    {
        return $this->view('emails.cron.inactivity_email')
            ->with([
                'subject' => $this->subject,
                'student' => $this->student,
                'course' => $this->course,
                'weeks' => $this->weeks,
            ]);
    }
}
