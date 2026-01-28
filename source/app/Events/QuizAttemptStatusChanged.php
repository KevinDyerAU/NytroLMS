<?php

namespace App\Events;

use App\Models\QuizAttempt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $quizAttempt;

    public function __construct(QuizAttempt $quizAttempt)
    {
        $this->quizAttempt = $quizAttempt;
    }
}
