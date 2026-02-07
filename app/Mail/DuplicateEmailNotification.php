<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DuplicateEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $originalEmail;
    public $studentName;
    public $course;
    public $registeredBy;
    public $existingUser;
    public $registrationData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $originalEmail,
        string $studentName,
        Course $course,
        User $registeredBy,
        User $existingUser,
        array $registrationData
    ) {
        $this->originalEmail = $originalEmail;
        $this->studentName = $studentName;
        $this->course = $course;
        $this->registeredBy = $registeredBy;
        $this->existingUser = $existingUser;
        $this->registrationData = $registrationData;
    }

    /**
     * Build the message.
     */
    public function build() {
        return $this->view('emails.admin.duplicate-email-notification')
            ->subject('Duplicate Email Registration Alert' . ' - (' . $this->originalEmail . ')')
            ->with([
                'originalEmail' => $this->originalEmail,
                'studentName' => $this->studentName,
                'course' => $this->course,
                'registeredBy' => $this->registeredBy,
                'existingUser' => $this->existingUser,
                'registrationData' => $this->registrationData,
            ]);
    }
}
