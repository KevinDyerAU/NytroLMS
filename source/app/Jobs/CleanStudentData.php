<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\AssessmentReturned;
use App\Notifications\NewAccountNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanStudentData implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $students;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($studentsIds)
    {
        $this->students = $studentsIds;
        \Log::info('CleanStudentData for student ids', $studentsIds);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('handling CleanStudentData', $this->students);

        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }
        foreach ($this->students as $student_id) {
            $this->processQuizAttempts($student_id);
            $this->updateRegisteredBy($student_id);
        }
    }

    protected function processQuizAttempts(mixed $student_id)
    {
        $attempts = QuizAttempt::where('user_id', $student_id)->orderBy('attempt', 'DESC')->get();

        $cleanedAttempts = [];
        foreach ($attempts as $attempt) {
            $cleanedAttempts = $this->clearNotifications($attempt, $student_id);
            $quiz = Quiz::where('id', $attempt->quiz_id)->first();
            if (!empty($quiz)) {
                $quiz->cleanAttemptResult($student_id);
            }
        }

        return $cleanedAttempts;
    }

    protected function clearNotifications(QuizAttempt $attempt, mixed $student_id)
    {
        $notifications = [];
        $hasQuiz = Quiz::where('id', $attempt->quiz_id)->exists();

        if ($attempt->system_result === 'MARKED'
            || $attempt->status === 'SATISFACTORY'
            || !$hasQuiz) {
            $query = User::find($student_id)
                ->notifications()
                ->where('type', AssessmentReturned::class)
                ->whereRaw("notifications.data LIKE '%assessment__{$attempt->id}%'");

            if ($query->count() > 0) {
                $notifications['AssessmentReturned'][] = $query->delete();
            }
        }

        return $notifications;
    }

    protected function updateRegisteredBy(int $student_id)
    {
        $query = User::find($student_id)
            ->notifications()
            ->where('type', NewAccountNotification::class);

        if ($query->count() > 0) {
            $details = $query->first()->data;
            $student = User::find($student_id);
            $student->detail()->update([
                'registered_by' => $details['created_by'] ?? 0,
            ]);
        }
    }
}
