<?php

namespace App\Console\Commands;

use App\Models\QuizAttempt;
use App\Models\StudentActivity;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;

class UpdateAssessments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:assessments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update assessments on quiz_attempts table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $assessments = [];

        $assessments['in_progress'] = $this->fixInProgressAssessments();
        $assessments['validate_accessor'] = $this->validateAccessor();

        $message = 'Updated assessments: IN PROGRESS ->'.count($assessments['in_progress']);
        $message .= '\n\r Updated assessments: SETUP ACCESSOR ->'.count($assessments['validate_accessor']);
        $this->info($message);
        \Log::info($message);

        return 0;
    }

    protected function fixInProgressAssessments(): array
    {
        $assessments = [];
        $total = QuizAttempt::where('system_result', 'INPROGRESS')->count();
        $i = 0;
        while ($i <= $total) {
            $quizAttempts = QuizAttempt::where('system_result', 'INPROGRESS')->limit(100)->offset($i)->get();
            if (!empty($quizAttempts)) {
                foreach ($quizAttempts as $attempt) {
                    if (count($attempt->questions) === count($attempt->submitted_answers)) {
                        $attempt->status = 'SUBMITTED';
                    } else {
                        $attempt->status = 'ATTEMPTING';
                    }
                    $attempt->save();
                }

                $assessments[] = [$quizAttempts->count() ?? $i => $quizAttempts->pluck('id')];
            }
            $i = $i + 100;
        }

        return $assessments;
    }

    protected function validateAccessor()
    {
        $studentActivities = [];
        $assessments = [];
        $total = QuizAttempt::whereNull('accessor_id')
            ->where('system_result', '!=', 'INPROGRESS')->count();
        $i = 0;
        while ($i <= $total) {
            $quizAttempts = QuizAttempt::selectRaw("quiz_attempts.id as 'id',quiz_attempts.user_id as 'quser_id',student_activities.user_id as 'suser_id',student_activities.activity_details as 'activity_details',student_activities.id as 'sid'")
                ->whereNull('quiz_attempts.accessor_id')
                ->where('quiz_attempts.system_result', '!=', 'INPROGRESS')
                ->leftJoin('student_activities', function (JoinClause $join) {
                    $join->on('student_activities.actionable_id', '=', 'quiz_attempts.id')
                        ->where('student_activities.actionable_type', '=', QuizAttempt::class)
                        ->where('student_activities.activity_event', '=', 'ASSESSMENT MARKED');
                })->limit(100)->offset($i)->get();
            if (!empty($quizAttempts)) {
                foreach ($quizAttempts as $attempt) {
                    if (!empty($attempt->activity_details)) {
                        $activity_details = json_decode($attempt->getRawOriginal('activity_details'), true);
                        $data = [
                            'accessor_id' => $activity_details['accessor_id'] ?? null,
                            'accessed_at' => $activity_details['accessed_at'] ?? null,
                            'is_valid_accessor' => intval($attempt->quser_id) !== intval($activity_details['accessor_id']),
                        ];
                        $quizAttemptID = $attempt->id;
                        $assessments[] = QuizAttempt::where('id', $quizAttemptID)->update($data);
                        if (intval($attempt->suser_id) !== intval($attempt->quser_id)) {
                            $updated = StudentActivity::where('id', $attempt->sid)->update(['user_id' => $attempt->quser_id]);
                            $studentActivities[] = $updated;
                        }
                    }
                }
            }
            $i = $i + 100;
        }

        return $assessments;
    }
}
