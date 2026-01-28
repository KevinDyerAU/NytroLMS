<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentMarked extends Notification
{
    use Queueable;

    public QuizAttempt $attempt;

    /**
     * Create a new notification instance.
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
        return ['database'];
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
