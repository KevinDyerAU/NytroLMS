<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLLNDMarked extends Notification
{
    use Queueable;

    public QuizAttempt $attempt;

    public function __construct(QuizAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Your LLND Activity Result')
            ->view('emails.assessment.lln_marked', [
                'attempt' => $this->attempt,
                'notifiable' => $notifiable,
                'assisted' => $this->attempt->assisted,
                'status' => $this->attempt->status,
                'lesson' => $this->attempt->lesson->title,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'student' => $notifiable->id,
            'evaluator' => auth()->user()->id,
            'assessment' => $this->attempt->id,
        ];
    }
}
