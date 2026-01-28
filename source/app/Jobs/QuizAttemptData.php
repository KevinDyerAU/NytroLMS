<?php

namespace App\Jobs;

use App\Models\QuizAttempt;
use App\Models\StudentActivity;
use App\Models\User;
use App\Services\CourseProgressService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QuizAttemptData implements ShouldQueue
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
        //        \Log::info('Quiz Attempts for student ids', $studentsIds);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()?->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }
        foreach ($this->students as $student_id) {
            $this->updateQuizAttemptsWithActivity(intval($student_id));
        }
    }

    protected function updateQuizAttemptsWithActivity(int $student_id)
    {
        $quizAttempts = QuizAttempt::with('evaluation')->selectRaw('quiz_attempts.*')// , quiz_attempts.user_id as 'quser_id',student_activities.user_id as 'suser_id',student_activities.activity_details as 'activity_details',student_activities.id as 'sid'" )
//                                   ->whereNull( 'quiz_attempts.accessor_id' )
            ->where('quiz_attempts.user_id', $student_id)
            ->where('quiz_attempts.system_result', '!=', 'INPROGRESS')
//                                   ->leftJoin( 'student_activities', function ( JoinClause $join ) {
//                                       $join->on( 'student_activities.actionable_id', '=', 'quiz_attempts.id' )
//                                            ->where( 'student_activities.actionable_type', '=', QuizAttempt::class )
//                                            ->where( 'student_activities.activity_event', '=', 'ASSESSMENT MARKED' );
//                                   } )
            ->get();
        $count = count($quizAttempts);
        if (!empty($quizAttempts)) {
            foreach ($quizAttempts as $attempt) {
                if (in_array($attempt->status, ['ATTEMPTING', 'SUBMITTED', 'REVIEWING'])) {
                    StudentActivity::where('activity_event', 'ASSESSMENT MARKED')
                        ->where('user_id', $attempt->user_id)
                        ->where('course_id', $attempt->course_id)
                        ->where('actionable_type', QuizAttempt::class)
                        ->where('actionable_id', $attempt->id)
                        ->delete();
                } else {
                    $quizAttempt = QuizAttempt::where('id', $attempt->id)->first();
                    $evaluation = $quizAttempt->evaluation;
                    if (!empty($evaluation)) {
                        $evaluatorID = !empty($evaluation) ? intval($evaluation->evaluator_id) : null;
                        $accessor = !empty($evaluatorID) ? User::find($evaluatorID) : null;
                        $accessed_on = !empty($evaluation) ? $evaluation->created_at : null;
                        $activityData = [
                            'activity_on' => $quizAttempt->getRawOriginal('updated_at'),
                            'student' => $quizAttempt->user_id,
                            'status' => $quizAttempt->status,
                            'user_id' => !empty($accessor) ? $accessor->id : null,
                            'accessor_id' => !empty($accessor) ? $accessor->id : null,
                            'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                            'accessed_at' => !empty($accessed_on) ? $accessed_on : null,
                            'activity_by__at' => !empty($accessed_on) ? $accessed_on : null,
                            'activity_by_id' => !empty($accessor) ? $accessor->id : null,
                            'activity_by_role' => !empty($accessor) ? $accessor->roleName() : null,
                        ];
                        CourseProgressService::updateOrCreateStudentActivity(
                            $quizAttempt,
                            'ASSESSMENT MARKED',
                            $student_id,
                            $activityData
                        );
                        $data = [
                            'accessor_id' => !empty($accessor) ? $accessor->id : null,
                            'accessed_at' => !empty($accessed_on) ? $accessed_on : null,
                            'is_valid_accessor' => true,
                        ];
                        if (!empty($data['accessor_id'])) {
                            QuizAttempt::where('id', $attempt->id)->update($data);
                        }
                    }
                }
            }
            //            \Log::info( "Updated assessments for STUDENT {$student_id}->{$count}" );
        }
    }
}
