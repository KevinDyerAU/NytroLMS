<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DuplicateEmailLeaderNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $originalEmail;
    public $studentName;
    public $course;
    public $registeredBy;
    public $registrationData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $originalEmail,
        string $studentName,
        Course $course,
        User $registeredBy,
        array $registrationData
    ) {
        $this->originalEmail = $originalEmail;
        $this->studentName = $studentName;
        $this->course = $course;
        $this->registeredBy = $registeredBy;
        $this->registrationData = $registrationData;
    }

    /**
     * Build the message.
     */
    public function build() {
        return $this->view('emails.leaders.duplicate-email-receipt')
            ->subject('Student Registration Receipt - ' . $this->studentName . ' (' . $this->originalEmail . ')')
            ->with([
                'originalEmail' => $this->originalEmail,
                'studentName' => $this->studentName,
                'course' => $this->course,
                'registeredBy' => $this->registeredBy,
                'registrationData' => $this->registrationData,
            ]);
    }
}
