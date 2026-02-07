<?php

namespace App\Http\Controllers;

use App\DataTables\AssessmentDatatable;
use App\Helpers\Helper;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Notifications\AssessmentEmailed;
use App\Notifications\AssessmentMarked;
use App\Notifications\AssessmentReturned;
use App\Notifications\NewLLNDMarked;
use App\Notifications\PreCourseAssessmentMarked;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AssessmentsController extends Controller
{
    public CourseProgressService $courseProgress;

    public StudentActivityService $activityService;

    public function __construct(CourseProgressService $courseProgress, StudentActivityService $activityService)
    {
        $this->courseProgress = $courseProgress;
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(AssessmentDatatable $dataTable, Request $request)
    {
        $this->authorize('view assessments');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'List'],
        ];

        return $dataTable->with(['status' => $request->status, 'start_date' => $request->start_date, 'end_date' => $request->end_date])->render('content.assessments.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(QuizAttempt $assessment)
    {
        $this->isAuthorized($assessment);

        if ($assessment->status === 'SUBMITTED') {
            $assessment->status = 'REVIEWING';
            $assessment->save();
        }

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Assessments'],
            ['name' => 'Under Review'],
        ];
        $canEvaluate = (in_array($assessment->status, ['SUBMITTED', 'REVIEWING']) && auth()->user()->can('mark assessments'));

        $markedBySystem = false;
        if ($assessment->system_result === 'EVALUATED'
            || ($assessment->system_result === 'MARKED'
                && empty($assessment->accessor_id)
                && auth()->user()->can('mark assessments'))) {
            $canEvaluate = true;
            $markedBySystem = true;
        }

        // Special handling for LLND quiz - allow evaluation even if already marked
        if (intval($assessment->quiz_id) === intval(config('lln.quiz_id')) && auth()->user()->can('mark assessments')) {
            $canEvaluate = true;
            // \Log::info('LLND Quiz Evaluation Allowed in AssessmentsController', [
            //     'quiz_id' => $assessment->quiz_id,
            //     'attempt_id' => $assessment->id,
            //     'status' => $assessment->status,
            //     'system_result' => $assessment->system_result,
            //     'accessor_id' => $assessment->accessor_id,
            //     'can_evaluate' => $canEvaluate,
            //     'user_can_mark' => auth()->user()->can('mark assessments')
            // ]);
        }
        //        if(auth()->user()->id === 1){
        //            dd($canEvaluate, $assessment->system_result, $assessment->accessor_id, auth()->user()->can('mark assessments'));
        //        }
        if (!$canEvaluate) {
            $breadcrumbs[1] = ['link' => route('assessments.index'), 'name' => 'Back'];
        }

        $quiz_feedback = Feedback::where('attachable_id', $assessment->quiz_id)->where('user_id', $assessment->user_id)->get();

        $isPreCourseAssessment = Lesson::where('id', $assessment->lesson_id)
            ->where('course_id', $assessment->course_id)
            ->where('order', 0)
            ->whereHas('course', function ($query) {
                return $query->where('courses.title', 'NOT LIKE', '%Semester 2%');
            })
            ->first();
        //        dd( $isPreCourseAssessment );

        $previousEvaluation = StudentCourseService::getPreviousEvaluation($assessment);
        
        // Get all questions including deleted ones
        $allQuestions = $assessment->quiz->questions()->withDeleted()->get();
        
        // Filter questions: include non-deleted OR deleted questions that were answered
        $submittedAnswers = $assessment->submitted_answers ? $assessment->submitted_answers->toArray() : [];
        $questions = $allQuestions->filter(function ($question) use ($submittedAnswers) {
            // Include if not deleted
            if (!$question->is_deleted) {
                return true;
            }
            // Include deleted questions only if they were answered
            return isset($submittedAnswers[$question->id]);
        });
        
        $questionIds = $questions->pluck('id')->toArray();
        $correctAnswer = $assessment->quiz->questions()->withDeleted()->whereIn('id', $questionIds)->whereNotNull('correct_answer')->pluck('correct_answer', 'id')->toArray();

        //        Helper::debug([$correctAnswer, $assessment->quiz->questions->pluck('answer_type','id')->toArray(), $assessment->submitted_answers->toArray()],'dd');

        return view()->make('content.assessments.review')
            ->with([
                'markedBySystem' => $markedBySystem,
                'canEvaluate' => $canEvaluate,
                'attempt' => $assessment,
                'evaluation' => $assessment->evaluation,
                'feedbacks' => $quiz_feedback,
                'questions' => $questions,
                'options' => $assessment->quiz->questions()->withDeleted()->whereIn('id', $questionIds)->whereNotNull('options')->pluck('options', 'id')->toArray(),
                'correct_answers' => $correctAnswer,
                'is_pre_course_assessment' => (bool) $isPreCourseAssessment,
                'prevEvaluationResults' => $previousEvaluation?->results?->toArray(),
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    public function answerPost(Request $request, QuizAttempt $assessment): \Illuminate\Http\JsonResponse
    {
        $evaluation = $assessment->evaluation()?->latest()->first();

        // If no evaluation exists or the last one is completed, create a new evaluation
        if (empty($evaluation) || $evaluation->isComplete()) {
            $evaluation = new Evaluation([
                'results' => [
                    $request->question => [
                        'status' => $request->status ?? '',
                        'comment' => $request->comment ?? '',
                    ],
                ],
                'student_id' => $assessment->user_id,
                'evaluator_id' => auth()->user()->id,
            ]);
        } else {
            // If not complete, update the existing evaluation
            $existingEvaluation = !empty($evaluation->results) ? $evaluation->results->toArray() : [];
            $evaluation->results = array_replace($existingEvaluation, [
                $request->question => [
                    'status' => $request->status ?? $existingEvaluation[$request->question]['status'] ?? '',
                    'comment' => $request->comment ?? $existingEvaluation[$request->question]['comment'] ?? '',
                ],
            ]);
        }
        // Save the evaluation for the quiz attempt
        $assessment->evaluation()->save($evaluation);

        return response()->json([
            'data' => $assessment->evaluation,
            'success' => true, 'status' => 'success',
            'message' => 'Your evaluation for question submitted successfully.',
        ]);
    }

    public function feedbackPost(Request $request, QuizAttempt $assessment): \Illuminate\Http\JsonResponse
    {
        $isPreCourseAssessment = Lesson::where('id', $assessment->lesson_id)
            ->where('course_id', $assessment->course_id)
            ->whereHas('course', function ($query) {
                return $query->where('courses.title', 'NOT LIKE', '%Semester 2%');
            })
            ->where('order', 0)->first();

        //        dd($isPreCourseAssessment, $request->all(), $assessment->toArray());

        // Fetch the latest evaluation entry for the assessment
        $evaluation = $assessment->evaluation()?->latest()->first();

        //        Helper::debug([$evaluation->results, $assessment->quiz->questions->toArray()],'dd');

        // Get all questions including deleted ones
        $allQuestions = $assessment->quiz->questions()->withDeleted()->get();
        
        // Filter questions: include non-deleted OR deleted questions that were answered
        $submittedAnswers = $assessment->submitted_answers ? $assessment->submitted_answers->toArray() : [];
        $questionsToMark = $allQuestions->filter(function ($question) use ($submittedAnswers) {
            // Include if not deleted
            if (!$question->is_deleted) {
                return true;
            }
            // Include deleted questions only if they were answered
            return isset($submittedAnswers[$question->id]);
        });

        // Check if no evaluation exists or if the last one is incomplete (unmarked)
        if (empty($evaluation) || count($evaluation->results) < count($questionsToMark)) {
            return Helper::errorResponse('Kindly mark all questions first.', 403);
        }
        // If the evaluation exists and is already marked (status set), create a new evaluation with carried forward results
        if ($evaluation->isComplete()) {
            $newEvaluation = new Evaluation([
                'results' => $evaluation->results, // Carry forward results from the last marked evaluation
                'student_id' => $assessment->user_id,
                'evaluator_id' => auth()->user()->id,
                'status' => \Str::upper($request->status), // Set the new status
            ]);
            $assessment->evaluation()->save($newEvaluation);

            // Store feedback for the new evaluation
            $feedback = new Feedback([
                'body' => ['message' => $request->feedback, 'evaluation_id' => $newEvaluation->id, 'attempt_id' => $assessment->id],
                'user_id' => $assessment->user_id,
                'owner_id' => auth()->user()->id,
            ]);
            $assessment->quiz->feedbacks()->save($feedback);

            $newEvaluation->updated_at = Carbon::now();
            $newEvaluation->save();
        } else {// If the evaluation exists and is not marked, update it with feedback and status
            $feedback = new Feedback([
                'body' => ['message' => $request->feedback, 'evaluation_id' => $evaluation->id, 'attempt_id' => $assessment->id],
                'user_id' => $assessment->user_id,
                'owner_id' => auth()->user()->id,
            ]);
            $assessment->quiz->feedbacks()->save($feedback);

            // Update the existing evaluation with new status
            $evaluation->status = \Str::upper($request->status);
            $evaluation->updated_at = Carbon::now();
            $evaluation->save();
        }

        $assessment->assisted = $request->assisted ?? false;

        $assessment->accessed_at = Carbon::now();
        $assessment->accessor_id = auth()->user()->id;
        $assessment->is_valid_accessor = true;
        $assessment->user_ip = $request->ip();

        if (strtolower($request->status) === 'satisfactory') {
            $assessment->status = 'SATISFACTORY';
        } else {
            $assessment->status = 'FAIL';
        }
        $assessment->save();
        event(new \App\Events\QuizAttemptStatusChanged($assessment));

        // save progress
        $progress = $this->courseProgress->markProgress($assessment->user_id, $assessment->course_id, $assessment);
        if (!empty($progress)) {
            CourseProgressService::updateProgressSession($progress);
        }
        $this->updateAdminReport($assessment);

        if ($evaluation->isComplete()) {
            // Update Student Course Stats
            $enrolment = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])
                ->where('user_id', $assessment->user_id)
                ->where('course_id', $assessment->course_id)
                ->first();
            if ($enrolment) {
                $isMainCourse = $enrolment->course->is_main_course || !\Str::contains(\Str::lower($enrolment->course->title), 'emester 2');
                CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
            }
        }

        //        dd($isPreCourseAssessment, $assessment->user);
        //        \Log::debug('Assessment Marked',[
        //            $assessment->status,
        //            'assessment_id' => $assessment->id,
        //            'user_id' => $assessment->user_id,
        //            'course_id' => $assessment->course_id,
        //            'lesson_id' => $assessment->lesson_id,
        //            'lln_quiz' => config('lln.quiz_id'),
        //            'quiz_id' => $assessment->quiz_id,
        //            'lln_quiz_true' => intval($assessment->quiz_id) === config('lln.quiz_id'),
        //        ]);
        if (intval($assessment->quiz_id) === config('lln.quiz_id')) {
            $assessment->user->notify(new NewLLNDMarked($assessment));
            if ($assessment->status === 'SATISFACTORY' && $assessment->assisted) {
                $note_body = '<p>The trainer has marked the LLND activity as satisfactory, with <strong>assistance required</strong>.</p>';
                $note_body .= '<p>An email notification has been sent to the student advising them to <strong>reach out for help</strong> with the course when needed.</p>';
                Note::create([
                    'user_id' => 0,
                    'subject_type' => User::class,
                    'subject_id' => $assessment->user_id,
                    'note_body' => $note_body,
                ]);
            }
        } elseif (!empty($isPreCourseAssessment)) {
            $assessment->user->notify(new PreCourseAssessmentMarked($assessment));
        } else {
            if (strtolower($assessment->status) !== 'satisfactory') {
                return $this->returnPost($request, $assessment);
            }

            // mark quiz to student notification
            $assessment->user->notify(new AssessmentMarked($assessment));

            $lesson = $assessment->lesson;
            //            \Log::info( 'checking competency', [ 'user_id' => $assessment->user_id, 'lesson' => $lesson->id ] );
            StudentCourseService::addCompetency($assessment->user_id, $lesson);
        }

        return response()->json([
            'data' => ['feedback' => $assessment->feedbacks, 'progress' => $progress, 'user_id' => $assessment->user_id],
            'success' => true, 'status' => 'success',
            'message' => 'Your evaluation for quiz submitted successfully',
        ]);
    }

    public function emailPost(Request $request, QuizAttempt $assessment)
    {
        // notification sent via email
        $assessment->user->notify(new AssessmentEmailed($assessment));

        return response()->json([
            'data' => $assessment,
            'success' => true, 'status' => 'success',
            'message' => 'Email notification successfully sent.',
        ]);
    }

    public function returnPost(Request $request, QuizAttempt $assessment)
    {
        $assessment->status = 'RETURNED';
        $assessment->save();

        // quiz return to student notification
        $assessment->user->notify(new AssessmentReturned($assessment));

        return response()->json([
            'data' => $assessment,
            'success' => true, 'status' => 'success',
            'message' => 'Quiz returned to student',
        ]);
    }

    private function isAuthorized(QuizAttempt $assessment): void
    {
        $this->authorize('view assessments');

        //        if (auth()->user()->isTrainer()) {
        //            $trainer = $assessment->relatedTrainer()->count();
        //            if ($trainer < 1) {
        //                abort(403, "You are not authorized to view this assessment.");
        //            }
        //        }
        if (auth()->user()->isLeader()) {
            $leader = $assessment->relatedLeader()->count();
            if ($leader < 1) {
                abort(403, 'You are not authorized to view this assessment.');
            }
        }
    }

    protected function updateAdminReport(QuizAttempt $attempt)
    {
        $adminReportService = new AdminReportService($attempt->user_id, $attempt->course_id);

        return $adminReportService->updateProgress(false);
    }
}
