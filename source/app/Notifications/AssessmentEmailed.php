<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentEmailed extends Notification
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
        //
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
        // Get all questions including deleted ones
        $allQuestions = $this->attempt->quiz->questions()->withDeleted()->get();
        
        // Filter questions: include non-deleted OR deleted questions that were answered
        $submittedAnswers = $this->attempt->submitted_answers ? $this->attempt->submitted_answers->toArray() : [];
        $questions = $allQuestions->filter(function ($question) use ($submittedAnswers) {
            // Include if not deleted
            if (!$question->is_deleted) {
                return true;
            }
            // Include deleted questions only if they were answered
            return isset($submittedAnswers[$question->id]);
        });
        
        $questionIds = $questions->pluck('id')->toArray();

        return (new MailMessage())
            ->subject('Your Quiz Assessment Result')
            ->view('emails.assessment.result', [
                'attempt' => $this->attempt,
                'notifiable' => $notifiable,
                'evaluation' => $this->attempt->evaluation,
                'questions' => $questions,
                'options' => $this->attempt->quiz->questions()->withDeleted()->whereIn('id', $questionIds)->whereNotNull('options')->pluck('options', 'id')->toArray(),
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
