<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnacondaCourseNotification extends Notification
{
    use Queueable;

    public $student;
    public $selectedCourseIds;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, array $selectedCourseIds = [])
    {
        $this->student = $student->load(['courseEnrolments', 'courseEnrolments.course']);
        $this->selectedCourseIds = $selectedCourseIds;
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
            ->subject('Confirmation of Student Registration')
            ->view('emails.anaconda.student-course', [
                'student' => $this->student,
                'notifiable' => $notifiable,
                'selectedCourseIds' => $this->selectedCourseIds,
            ]);
    }
}
