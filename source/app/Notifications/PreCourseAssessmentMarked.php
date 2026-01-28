<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreCourseAssessmentMarked extends Notification
{
    use Queueable;

    public QuizAttempt $attempt;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(QuizAttempt $attempt)
    {
        $this->attempt = $attempt;
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
            ->subject('Your Pre Course Assessment Result')
            ->view('emails.assessment.pre_course', [
                'attempt' => $this->attempt,
                'notifiable' => $notifiable,
                'assisted' => $this->attempt->assisted,
                'status' => $this->attempt->status,
                'lesson' => $this->attempt->lesson->title,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'student' => $notifiable->id,
            'evaluator' => auth()->user()->id,
            'assessment' => $this->attempt->id,
        ];
    }
}
