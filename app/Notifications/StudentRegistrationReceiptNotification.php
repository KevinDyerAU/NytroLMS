<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentRegistrationReceiptNotification extends Notification
{
    use Queueable;

    public $student;
    public $registrationData;
    public $course;
    public $registeredBy;
    public $isExistingStudent;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, $registrationData, $course, $registeredBy, $isExistingStudent = false)
    {
        $this->student = $student;
        $this->registrationData = $registrationData;
        $this->course = $course;
        $this->registeredBy = $registeredBy;
        $this->isExistingStudent = $isExistingStudent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Student Registration Receipt - ' . config('settings.site.institute_name', 'Key Institute'))
            ->view('emails.leaders.student-registration-receipt', [
                'student' => $this->student,
                'registrationData' => $this->registrationData,
                'course' => $this->course,
                'registeredBy' => $this->registeredBy,
                'isExistingStudent' => $this->isExistingStudent,
                'notifiable' => $notifiable
            ]);
    }
}
