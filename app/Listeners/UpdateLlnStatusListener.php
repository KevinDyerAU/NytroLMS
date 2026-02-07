<?php

namespace App\Listeners;

use App\Events\QuizAttemptStatusChanged;
use App\Services\LlnCompletionService;

class UpdateLlnStatusListener
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(QuizAttemptStatusChanged $event)
    {
        $quizAttempt = $event->quizAttempt;
        $quiz = $quizAttempt->quiz;
        if (!$quiz || !$quiz->is_lln) {
            return;
        }
        // Update LLN status for this user and course
        app(LlnCompletionService::class)->updateLlnStatus($quizAttempt->user_id, $quiz->course_id);
    }
}
