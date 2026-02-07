<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentReturned extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $allowed_attempts = $this->attempt->quiz->allowed_attempts;
        $attempts_made = QuizAttempt::where('user_id', $notifiable->id)->where('quiz_id', $this->attempt->quiz->id)->count();
        $remaining_attempts = $allowed_attempts - $attempts_made;

        return (new MailMessage())
            ->subject('Quiz returned after evaluation')
            ->view('emails.assessment.returned', [
                'attempt' => $this->attempt,
                'notifiable' => $notifiable,
                'total_attempts' => $allowed_attempts,
                'remaining_attempts' => ($remaining_attempts < 0) ? 0 : $remaining_attempts,
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
