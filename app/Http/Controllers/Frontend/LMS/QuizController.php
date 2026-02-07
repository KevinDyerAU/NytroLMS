<?php

namespace App\Http\Controllers\Frontend\LMS;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class QuizController extends Controller
{
    public CourseProgressService $courseProgress;

    public StudentActivityService $activityService;

    // Debug flag - set to true to enable debug output
    protected bool $allowDebug = false;

    public function __construct(CourseProgressService $courseProgress, StudentActivityService $activityService)
    {
        $this->courseProgress = $courseProgress;
        $this->activityService = $activityService;
    }

    /**
     * Display the specified resource.
     *
     * @param Quiz $quiz
     * @param string $slug
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(Quiz $quiz, $slug = '')
    {
        $course = $quiz->course;
        // Check LLND exclusion early to avoid unnecessary processing
        $excludeLLND = \App\Helpers\Helper::isLLNDExcluded($course->category);

        $pageConfigs = [
            'showMenu' => false,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
        ];
        $topic = $quiz->topic;
        $breadcrumbs = [
            ['name' => 'Quiz'],
            ['name' => 'Back', 'link' => route('frontend.lms.topics.show', [$topic->id, $topic->slug])],
        ];

        if (!$excludeLLND && ($quiz->id === intval(config('lln.quiz_id')) || $quiz->id === intval(config('ptr.quiz_id')))) {
            $breadcrumbs = [
                ['name' => 'LLN Quiz'],
                ['name' => 'Back', 'link' => route('frontend.dashboard')],
            ];
        }

        // PTR Logic: Check if this is a PTR quiz and handle the three scenarios
        $ptrQuizId = config('ptr.quiz_id');
        $isPtrQuiz = ($quiz->id == $ptrQuizId);
        $ptrRequiresAccess = false;
        $ptrCourseTitle = '';

        if ($isPtrQuiz) {
            $user = auth()->user();
            $ptrService = app(\App\Services\PtrCompletionService::class);

            // Get all user's enrolments that might need PTR
            $enrolments = \App\Models\StudentCourseEnrolment::where('user_id', $user->id)
                ->where('status', '!=', 'DELIST')
                ->whereHas('course', function ($q) {
                    $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
                })
                ->with('course')
                ->get();

            if ($this->allowDebug) {
                dump('PTR Quiz Controller: Checking enrolments for PTR access', [
                    'user_id' => $user->id,
                    'total_enrolments' => $enrolments->count(),
                    'enrolments' => $enrolments->map(function ($e) {
                        return [
                            'course_id' => $e->course_id,
                            'course_title' => $e->course->title ?? 'Unknown',
                            'category' => $e->course->category ?? 'NULL',
                            'created_at' => $e->created_at,
                        ];
                    })->toArray(),
                ]);
            }

            // Check if user has any courses that require PTR completion
            $coursesRequiringPtr = [];
            foreach ($enrolments as $enrolment) {
                // Skip excluded categories
                if (in_array($enrolment->course?->category, config('ptr.excluded_categories', []))) {
                    continue;
                }

                // Check if PTR is completed for this course
                $ptrCompleted = $ptrService->hasCompletedPtrForCourse($user->id, $enrolment->course_id);
                $ptrCourseTitle = $enrolment->course->title;
                if (!$ptrCompleted) {
                    $coursesRequiringPtr[] = [
                        'course_id' => $enrolment->course_id,
                        'course_title' => $enrolment->course->title ?? 'Unknown Course',
                    ];
                }
            }

            if ($this->allowDebug) {
                dump('PTR Quiz Controller: PTR requirement analysis', [
                    'user_id' => $user->id,
                    'courses_requiring_ptr' => $coursesRequiringPtr,
                    'total_courses_requiring_ptr' => count($coursesRequiringPtr),
                ]);
            }

            if (!empty($coursesRequiringPtr)) {
                // User needs PTR for some courses - allow access
                $ptrRequiresAccess = true;
                goto skipIsAllowedCheck;
            }
        }

        if (!$quiz->isAllowed()) {
            abort(403, "You already attempted this quiz");
        }

        skipIsAllowedCheck:

        $quizzes = collect();
        $userId = auth()->user()->id;

        // Only prepend LLND quiz for non-excluded categories and main courses
        if (!$excludeLLND && $course->is_main_course) {
            $llnQuiz = Quiz::with(['questions', 'attempts' => function ($q) use ($userId) {
                $q->where('user_id', $userId)->orderBy('id', 'desc');
            }])->find(config('lln.quiz_id'));

            if ($llnQuiz) {
                $quizzes->push($llnQuiz);
            }
        }

        // Get regular quizzes for the topic
        $topicQuizzes = $topic->quizzes()
            ->with(['questions', 'attempts' => function ($q) use ($userId) {
                $q->where('user_id', $userId)->orderBy('id', 'desc');
            }])
            ->orderBy('order')
            ->get();

        $quizzes = $quizzes->merge($topicQuizzes);

        $lastSubmission = QuizAttempt::where('quiz_id', $quiz->id)
                                   ->where('user_id', $userId)
                                   ->orderBy('id', 'DESC')
                                   ->first();

        // Only apply attempt restrictions for non-excluded categories
        // Skip submission check for PTR quiz if user needs PTR access
        if (!$excludeLLND && !($isPtrQuiz && $ptrRequiresAccess)) {
            if (!empty($lastSubmission)) {
                if (in_array($lastSubmission->system_result, ['COMPLETED', 'EVALUATED', 'MARKED']) && !in_array($lastSubmission->status, ['FAIL', 'RETURNED', 'ATTEMPTING'])) {
                    return view()->make('frontend.content.errors.403')->with([
                        'message' => 'Already Submitted. Please wait for result.',
                        'link' => [
                            'href' => route('frontend.lms.topics.show', [$topic->id, $topic->slug]),
                            'title' => 'Go back',
                        ],
                        'breadcrumbs' => $breadcrumbs,
                        'pageConfigs' => $pageConfigs,
                    ]);
                }

                if ($lastSubmission->attempt >= ($quiz->allowed_attempts ?? 999)) {
                    abort(403, 'Max attempts reached');
                }
            }
        }

        $questions = $quiz->questions()->orderBy('order', 'ASC')->get();
        $countQuestions = !(empty($questions)) ? count($questions) : 0;
        $attempted_answers = [];

        if (!empty($lastSubmission->system_result) && $lastSubmission->system_result === 'INPROGRESS') {
            $attempted_answers = $lastSubmission;
        }
        $last_failed_attempt = QuizAttempt::where('quiz_id', $quiz->id)
                                          ->where('user_id', auth()->user()->id)
                                          ->where(function ($query) {
                                              $query->where('quiz_attempts.status', 'RETURNED')
                                                    ->orWhere('quiz_attempts.status', 'FAIL');
                                          })
                                          ->orderBy('id', 'DESC')
                                          ->with(['evaluation' => function ($query) {
                                              $query->select('id', 'results', 'evaluable_id');
                                          }])
                                          ->first();

        $submittedAnswers = !empty($last_failed_attempt) ? json_decode($last_failed_attempt->submitted_answers, true) : "";
        $evaluationResults = !empty($last_failed_attempt) ? json_decode($last_failed_attempt->evaluation->results ?? '{}', true) : "";

        //        dd($lastSubmission->system_result, $attempted_answers);

        $questions_order_adjust = in_array(0, $questions->pluck('order')->toArray()) ? 0 : 1;
        $answer_types = $questions->pluck('answer_type', 'id')->toArray();
        $attempted_answersO = $attempted_answers;

        $attempted_answers = !empty($attempted_answersO) ? $attempted_answersO->submitted_answers->toArray() : [];

        if (!empty($attempted_answers)) {
            foreach ($attempted_answers as $key => $answer) {
                if (!empty($answer_types[$key]) && $answer_types[$key] === 'FILE') {
                    if (!\Str::contains($answer, 'public/user/')) {
                        unset($attempted_answers[$key]);
                    }
                }
            }
            $attempted_answersO->submitted_answers = $attempted_answers;
        }
        if (!empty($submittedAnswers)) {
            foreach ($submittedAnswers as $key => $answer) {
                if (!empty($answer_types[$key]) && $answer_types[$key] === 'FILE') {
                    if (!\Str::contains($answer, 'public/user/')) {
                        unset($submittedAnswers[$key]);
                    }
                }
            }
            //            Helper::debug([$submittedAnswers, $answer_types], 'dd',16056);
        }

        //        Helper::debug([$attempted_answersO, json_encode($attempted_answers)], 'dd',15808);
        //        if(auth()->user()->username === 'tests'){
        //            dd($attempted_answers, auth()->user()->id,  $quiz->id,  $last_attempt);
        //        }
        //        $digestCollection = Lesson::where( 'id', $quiz->lesson_id )->with( 'topics' )->first();
        return view()->make('frontend.content.lms.quiz')->with([
            'title' => $quiz->title,
            'activeUser' => auth()->user(),
            'post' => $quiz,
            'hasRelated' => ($countQuestions > 0),
            'related' => [
                'title' => \Str::plural('Question', $countQuestions),
                'route' => 'frontend.lms.questions.show',
                'data' => $questions,
                'questions_order_adjust' => $questions_order_adjust,
            ],
//            'digest' => $digestCollection,
            'correct_answers' => $quiz->questions()->whereNotNull('correct_answer')->pluck('correct_answer', 'id')->toArray(),
            'attempted_answers' => $attempted_answers,
            'questions' => $questions?->pluck('id')->toArray(),
            'submitted_answers' => !empty($attempted_answers) ? array_keys($attempted_answers) : [],
            'last_attempt' => $submittedAnswers,
            'last_attempt_evaluation' => $evaluationResults,
            'next_step' => $this->nextStep($quiz, $attempted_answersO),
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
            'lastAttempt' => ($isPtrQuiz && $ptrRequiresAccess) ? null : $quiz->lastAttempt(),
            'is_ptr_quiz' => $isPtrQuiz,
            'ptr_course_title' => $ptrCourseTitle,
        ]);
    }

    private function nextStep(Quiz $quiz, $attempted_answers)
    {
        $questions = $quiz->questions()->orderBy('order')->get()->toArray();
        $result = 1;
        $qid = 0;
        if (empty($questions)) {
            return ['last' => 0, 'step' => 1, 'qid' => 0, 'last_question_id' => 0];
        }

        $last_question = end($questions);
        reset($questions);
        if (empty($attempted_answers)) {
            return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }
        $submittedAnswers = $attempted_answers->submitted_answers->toArray();
        if (empty($submittedAnswers)) {
            return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }

        $submittedAnswersKeys = array_keys($submittedAnswers);
        $questionIds = \Arr::pluck($questions, 'id');
        $remaining_questions = array_diff($questionIds, $submittedAnswersKeys);
        //        dd([
        //            $submittedAnswersKeys,$questionIds, $remaining_questions,
        //            count( $remaining_questions ), count( $questions ),  count( $submittedAnswers ) ,
        //            ( ( count( $questions ) - 1 ) === count( $submittedAnswers ) )
        //
        //        ]);
        if (count($remaining_questions) > 1) {
            $keys_remaining_questions = array_keys($remaining_questions);
            $result = $keys_remaining_questions[1];
        } else {
            $result = $remaining_questions[end($questionIds)] ?? 0;
        }
        if (count($questions) === count($submittedAnswers)) {
            $question = end($questions);

            return ['last' => 1, 'step' => count($questions), 'qid' => $question['id'], 'last_question_id' => $last_question['id']];
        }

        if (count($questions) > count($submittedAnswers)) {
            return ['last' => 0, 'step' => (count($submittedAnswers) + 1), 'qid' => $remaining_questions[$result] ?? 0, 'last_question_id' => end($questionIds)];
        }

        //        dd($submittedAnswersKeys,\Arr::pluck($questions,'id'), $remaining_questions, $result);

        //        if(auth()->user()->id === 2594) {
        //            dd( $result );
        //        }
        return ['last' => 0, 'step' => count($questions), 'qid' => $remaining_questions[$result] ?? 0, 'last_question_id' => end($questionIds)];
    }

    public function viewResult(Quiz $quiz, QuizAttempt $attempt)
    {
        $pageConfigs = [
            'showMenu' => false,
            'layoutWidth' => 'full',
            'mainLayoutType' => 'horizontal',
        ];

        $topic = $quiz->topic;
        $breadcrumbs = [
            ['name' => 'Last Attempt'],
            ['name' => 'Back to Topic', 'link' => route('frontend.lms.topics.show', [$topic->id, $topic->slug])],
        ];

        $quiz_feedback = Feedback::where('attachable_id', $attempt->quiz_id)->where('user_id', auth()->user()->id)->get();

        $markedBySystem = false;
        if ($quiz->passing_percentage > 0) {
            $lastAttempt = $quiz->lastAttempt();
            if (!empty($lastAttempt) && ($lastAttempt->system_result === 'EVALUATED' || $lastAttempt->system_result === 'MARKED')) {
                $markedBySystem = true;
            }
        }

        // Get all questions including deleted ones
        $allQuestions = $attempt->quiz->questions()->withDeleted()->get();
        
        // Filter questions: include non-deleted OR deleted questions that were answered
        $submittedAnswers = $attempt->submitted_answers ? $attempt->submitted_answers->toArray() : [];
        $questions = $allQuestions->filter(function ($question) use ($submittedAnswers) {
            // Include if not deleted
            if (!$question->is_deleted) {
                return true;
            }
            // Include deleted questions only if they were answered
            return isset($submittedAnswers[$question->id]);
        });
        
        $questionIds = $questions->pluck('id')->toArray();

        return view()->make('frontend.content.assessments.review')
                     ->with([
                         'markedBySystem' => $markedBySystem,
                         'canEvaluate' => false,
                         'attempt' => $attempt,
                         'evaluation' => $attempt->evaluation,
                         'feedbacks' => $quiz_feedback,
                         'questions' => $questions,
                         'options' => $attempt->quiz->questions()->withDeleted()->whereIn('id', $questionIds)->whereNotNull('options')->pluck('options', 'id')->toArray(),
                         'correct_answers' => $attempt->quiz->questions()->withDeleted()->whereIn('id', $questionIds)->whereNotNull('correct_answer')->pluck('correct_answer', 'id')->toArray(),
                         'pageConfigs' => $pageConfigs,
                         'breadcrumbs' => $breadcrumbs,
                     ]);
    }

    public function attempt(Request $request, Quiz $quiz): \Illuminate\Http\JsonResponse
    {
        $token = $request->session()->token();
        //        dump($request->_token, $token, intval($request->user), auth()->user()->id);

        // PTR Logic: Check if this is a PTR quiz and get the course ID that requires PTR
        $ptrQuizId = config('ptr.quiz_id');
        $ptrCourseId = null;

        // Debug logging for PTR course ID determination
        // Helper::debug(['PTR Quiz Attempt Debug:'=> [
        //     'quiz_id' => $quiz->id,
        //     'ptr_quiz_id' => $ptrQuizId,
        //     'is_ptr_quiz' => $quiz->id == $ptrQuizId,
        //     'requested_course_id' => $request->query('course_id'),
        //     'user_id' => auth()->user()->id
        // ]], 'dump', 'sharonw6');

        if ($quiz->id == $ptrQuizId) {
            $user = auth()->user();

            // Check if course_id is provided in query parameters or request data (from ptr.access middleware or AJAX)
            $requestedCourseId = $request->query('course_id') ?? $request->input('course_id');

            // Helper::debug(['PTR Course ID Determination - Step 1' => [
            //     'requested_course_id' => $requestedCourseId,
            //     'query_course_id' => $request->query('course_id'),
            //     'input_course_id' => $request->input('course_id'),
            //     'ptr_course_id_before' => $ptrCourseId ?? 'null',
            //     'request_url' => $request->url(),
            //     'request_path' => $request->path(),
            //     'request_query' => $request->query(),
            //     'request_all' => $request->all(),
            //     'request_method' => $request->method()
            // ]], 'dump', 'testn2');

            if ($requestedCourseId) {
                // Validate the requested course ID - check if it exists and is not excluded
                $requestedCourse = \App\Models\Course::find(intval($requestedCourseId));

                // Helper::debug(['PTR Course ID Determination - Step 2' => [
                //     'requested_course_found' => $requestedCourse ? 'yes' : 'no',
                //     'course_category' => $requestedCourse->category ?? 'null',
                //     'is_excluded' => $requestedCourse ? \App\Helpers\Helper::isPTRExcluded($requestedCourse->category) : 'unknown'
                // ]], 'dump', 'testn2');

                if ($requestedCourse) {
                    // Check if PTR is excluded for this course category
                    $isExcluded = \App\Helpers\Helper::isPTRExcluded($requestedCourse->category);

                    if (!$isExcluded) {
                        // Use the specific course ID provided by the middleware
                        $ptrCourseId = intval($requestedCourseId);
                        // Helper::debug(['PTR Course ID Determination - Step 3' => [
                        //     'ptr_course_id_set' => $ptrCourseId,
                        //     'reason' => 'query_parameter_valid'
                        // ]], 'dump', 'testn2');
                    } else {
                        // Helper::debug(['PTR Course ID Determination - Step 3' => [
                        //     'reason' => 'course_excluded',
                        //     'category' => $requestedCourse->category
                        // ]], 'dump', 'testn2');
                    }
                } else {
                    // Helper::debug(['PTR Course ID Determination - Step 3' => [
                    //     'reason' => 'course_not_found',
                    //     'requested_course_id' => $requestedCourseId
                    // ]], 'dump', 'testn2');
                }
            } else {
                // Helper::debug(['PTR Course ID Determination - Step 2' => [
                //     'reason' => 'no_query_parameter'
                // ]], 'dump', 'testn2');
            }

            // If no valid course ID from query parameter, fall back to main course logic
            if (!$ptrCourseId) {
                // Helper::debug(['PTR Course ID Determination - Fallback Logic' => [
                //     'reason' => 'no_valid_query_parameter',
                //     'ptr_course_id_before_fallback' => $ptrCourseId ?? 'null'
                // ]], 'dump', 'testn2');

                // Fallback to the same logic as EnrolmentController for consistency
                // Get the student's main course for PTR association
                $studentCourseEnrolment = \App\Models\StudentCourseEnrolment::with('course')
                    ->where('user_id', $user->id)
                    ->where('is_main_course', true)
                    ->where('status', '!=', 'DELIST')
                    ->whereHas('course', function ($q) {
                        $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
                    })
                    ->first();

                // Helper::debug(['PTR Course ID Determination - Fallback Enrolment' => [
                //     'enrolment_found' => $studentCourseEnrolment ? 'yes' : 'no',
                //     'enrolment_course_id' => $studentCourseEnrolment->course_id ?? 'null',
                //     'enrolment_course_category' => $studentCourseEnrolment->course->category ?? 'null'
                // ]], 'dump', 'testn2');

                if ($studentCourseEnrolment && $studentCourseEnrolment->course) {
                    // Check if PTR is excluded for this course category
                    $isExcluded = \App\Helpers\Helper::isPTRExcluded($studentCourseEnrolment->course->category);

                    if (!$isExcluded) {
                        // Use the student's enrolled course ID for PTR association
                        $ptrCourseId = $studentCourseEnrolment->course_id;
                        // Helper::debug(['PTR Course ID Determination - Fallback Set' => [
                        //     'ptr_course_id_set' => $ptrCourseId,
                        //     'reason' => 'main_course_not_excluded'
                        // ]], 'dump', 'testn2');
                    } else {
                        // Helper::debug(['PTR Course ID Determination - Fallback Excluded' => [
                        //     'reason' => 'main_course_excluded',
                        //     'category' => $studentCourseEnrolment->course->category
                        // ]], 'dump', 'testn2');
                    }
                } else {
                    // Helper::debug(['PTR Course ID Determination - Fallback No Enrolment' => [
                    //     'reason' => 'no_main_course_enrolment'
                    // ]], 'dump', 'testn2');
                }
            }
        }

        // Debug logging for final PTR course ID
        // Helper::debug(['PTR Quiz Attempt - Final Course ID:'=> [
        //     'ptr_course_id' => $ptrCourseId,
        //     'quiz_course_id' => $quiz->course->id ?? 'null',
        //     'fallback_course_id' => $quiz->course->id ?? 'null'
        // ]], 'dump', 'sharonw6');

        // Skip isAllowed check for PTR quiz if user needs PTR access
        if ($quiz->id == $ptrQuizId && $ptrCourseId) {
            goto skipIsAllowedCheck;
        }

        // For PTR quizzes, skip the isAllowed check entirely since it doesn't understand course-specific PTR
        if ($quiz->id == $ptrQuizId) {
            goto skipIsAllowedCheck;
        }

        if (!$quiz->isAllowed()) {
            abort(403, "You already attempted this quiz");
        }

        skipIsAllowedCheck:

        $theUserId = auth()->user()->id;
        if ((intval($request->user) !== $theUserId) || $token !== $request->_token) {
            return Helper::errorResponse('Something went wrong. Invalid request!', 403);
        }
        $question = Question::where('id', $request->question)->first();
        $validate = [];
        $result = [];
        if ($question->answer_type === 'FILE') {
            if ($request->hasFile('file') && !empty($request->file)) {
                $validate['file'] = (!empty($question->required) ? "sometimes|file|mimes:" . ($question->options['file']['types_allowed'] ? $question->options['file']['types_allowed'] : "pdf,doc,docx,zip") : "nullable");
            } elseif (!empty($request->file)) {
                $validate['file'] = "nullable";
            }
        } elseif ($question->answer_type === 'TABLE') {
            // Get the raw submitted answers - they come as a flat array
            $rawAnswers = $request->input('answer');

            // Convert table structure to array if it's not already
            $tableStructure = is_string($question->table_structure)
                ? json_decode($question->table_structure, true)
                : $question->table_structure;

            // Validate answers array
            if (!is_array($rawAnswers)) {
                $rawAnswers = [];
            }

            // Check if all rows have answers
            $expectedRowCount = isset($tableStructure['rows']) ? count($tableStructure['rows']) : 0;
            $actualRowCount = count($rawAnswers);

            if ($actualRowCount !== $expectedRowCount) {
                return response()->json([
                    'code' => 704,
                    'status' => 'error',
                    'success' => false,
                    'message' => ['answer' => ["Please select an answer for each row in question {$question->id}. Expected {$expectedRowCount} answers, got {$actualRowCount}."]],
                ], 404);
            }

            // Get the table structure
            $tableStructure = is_string($question->table_structure)
                ? json_decode($question->table_structure, true)
                : $question->table_structure;

            // Initialize the restructured answers array
            $submittedAnswers = [];

            // Restructure the answers
            $submittedAnswers = [];
            if (!empty($tableStructure['rows'])) {
                foreach ($tableStructure['rows'] as $rowIndex => $row) {
                    if (!array_key_exists($rowIndex, $rawAnswers)) {
                        continue;
                    }

                    $response = $rawAnswers[$rowIndex];

                    if (is_array($response)) {
                        // Handle checkbox/text/textarea inputs (nested array structure)
                        $submittedAnswers[$rowIndex] = [];
                        if (isset($tableStructure['input_type']) && $tableStructure['input_type'] === 'checkbox') {
                            // For checkboxes, handle array of selected columns
                            foreach ($response as $selectedCol) {
                                if (isset($tableStructure['columns'][$selectedCol])) {
                                    $submittedAnswers[$rowIndex][] = [
                                        'question' => $row['heading'],
                                        'column' => $tableStructure['columns'][$selectedCol]['heading'],
                                        'user_response' => strval($selectedCol),
                                    ];
                                }
                            }
                            // If no checkboxes were selected for this row, keep empty array
                            if (empty($submittedAnswers[$rowIndex])) {
                                $submittedAnswers[$rowIndex] = [];
                            }
                        } else {
                            // Handle text/textarea inputs
                            foreach ($tableStructure['columns'] as $colIndex => $column) {
                                if (!empty($response[$colIndex])) {
                                    $submittedAnswers[$rowIndex][$colIndex] = [
                                        'question' => $row['heading'],
                                        'column' => $column['heading'],
                                        'user_response' => strval($response[$colIndex]),
                                    ];
                                }
                            }
                        }
                    } else {
                        // Handle radio inputs (single value)
                        if (!empty($response) || $response === '0') {
                            $columnIndex = is_numeric($response) ? intval($response) : $response;
                            $submittedAnswers[$rowIndex] = [
                                'question' => $row['heading'],
                                'answer' => array_key_exists($columnIndex, $tableStructure['columns'])
                                    ? $tableStructure['columns'][$columnIndex]['heading']
                                    : "Column {$columnIndex}",
                                'user_response' => $columnIndex,
                            ];
                        }
                    }
                }
            }
            // dd($submittedAnswers);
            // Update the request with restructured answers
            $request->merge(['answer' => $submittedAnswers]);

            // Validate if question is required
            if ($question->required) {
                foreach ($submittedAnswers as $rowIndex => $rowAnswers) {
                    // For checkbox type, check if the row has any selected answers
                    if (isset($tableStructure['input_type']) && $tableStructure['input_type'] === 'checkbox') {
                        if (empty($rowAnswers)) {
                            return response()->json([
                                'code' => 704,
                                'status' => 'error',
                                'success' => false,
                                'message' => ['answer' => ["Please select at least one option for row " . ($rowIndex + 1) . " in question {$question->id}."]],
                            ], 404);
                        }
                    } else {
                        // For other input types
                        if (empty($rowAnswers) ||
                            (isset($rowAnswers['user_response']) && empty($rowAnswers['user_response']))) {
                            return response()->json([
                                'code' => 704,
                                'status' => 'error',
                                'success' => false,
                                'message' => ['answer' => ["Please provide an answer for row " . ($rowIndex + 1) . " in question {$question->id}."]],
                            ], 404);
                        }
                    }
                }
            }

            // The answers are already in the correct format, no need for additional processing
            $request->merge(['answer' => $submittedAnswers]);
        } else {
            $validate['answer.*'] = ($question->required === 1 ? "required" : "nullable");
        }

        $validator = \Validator::make($request->all(), $validate);
        if ($validator->fails()) {
            return response()->json([
                'code' => (404 + 300),
                'status' => 'error',
                'success' => false,
                'message' => $validator->messages(),
            ], 404);
        }

        // For PTR quizzes, include course_id in the query to get the correct attempt for the specific course
        $query = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $theUserId)
                                      ->where('quiz_attempts.system_result', 'INPROGRESS');

        if ($quiz->id == $ptrQuizId && $ptrCourseId) {
            $query->where('course_id', $ptrCourseId);
        }

        $lastQuizAttempt = $query->orderBy('attempt', 'DESC')->first();

        // For PTR quizzes, include course_id in the failed attempt query as well
        $failedQuery = QuizAttempt::where('quiz_id', $quiz->id)->where('user_id', $theUserId)
                                            ->where(function ($query) {
                                                $query->where('quiz_attempts.status', 'RETURNED')
                                                      ->orWhere('quiz_attempts.status', 'FAIL');
                                            });

        if ($quiz->id == $ptrQuizId && $ptrCourseId) {
            $failedQuery->where('course_id', $ptrCourseId);
        }

        $lastFailedQuizAttempt = $failedQuery->orderBy('attempt', 'DESC')
                                            ->with(['evaluation' => function ($query) {
                                                $query->select('id', 'results', 'evaluable_id');
                                            }])
                                            ->first();
        $evaluationResults = !empty($lastFailedQuizAttempt) ? json_decode($lastFailedQuizAttempt->evaluation->results ?? '{}', true) : "";

        if (!empty($request->file)) {
            $filePath = 'public/user/' . $theUserId . '/quiz/' . $quiz->id;
            //            Helper::debug( [ $request->file, $request->question, $filePath ], 'dump', 15808 );
            if (($lastFailedQuizAttempt && $evaluationResults[$question->id]['status'] !== 'correct')) {
                $qAns = $lastFailedQuizAttempt->submitted_answers[$request->question] ?? null;
                if ($qAns && \Str::contains($qAns, 'public/user/') && Storage::exists($qAns)) {
                    Storage::delete($qAns);
                }
            }
            //            Helper::debug( [ 'step' => 1 ], 'dump', 15808 );
            if (empty($lastFailedQuizAttempt) && !empty($lastQuizAttempt)) {
                $tAns = $lastQuizAttempt->submitted_answers[$request->question] ?? null;

                //                Helper::debug( [ '$tAns' => $tAns ], 'dump', 15808 );
                if ($tAns && \Str::contains($tAns, 'public/user/')) {
                    if (Storage::exists($tAns)) {
                        Storage::delete($tAns);
                    }
                }
            }
            //            Helper::debug( [ 'step' => 2 ], 'dump', 15808 );
            if (!File::isDirectory($filePath)) {
                File::makeDirectory($filePath, 0755, true);
            }
            $stored_file = \Str::replace('/storage', 'public', $request->file);
            //            Helper::debug( [ 'step' => 2, $stored_file ], 'dump', 15808 );
            if (!$request->hasFile('file') && Storage::exists($stored_file)) {
                $newFilePath = $stored_file;
            } elseif ($request->hasFile('file')) {
                Helper::ensureDirectoryWithPermissions($filePath);
                $newFilePath = $request->file('file')->store($filePath);
            }

            $request->merge(['answer' => $newFilePath ?? null]);

            //            Helper::debug( [ $request->answer, $newFilePath ], 'dd', 15808 );
        }
        // Helper::debug(['PTR Quiz Attempt - Last Quiz Attempt' => [
        //     'last_quiz_attempt' => $lastQuizAttempt,
        //     'ptr_course_id' => $ptrCourseId,
        //     'user_id' => auth()->user()->id,
        //     'quiz_id' => $quiz->id
        // ]], 'dd', 'testn2');

        if (empty($lastQuizAttempt)) {
            $result = $this->createQuizAttempt(1, $quiz, $request, $ptrCourseId);

            // For PTR quizzes, include the course_id in the response
            if ($quiz->id == $ptrQuizId && $ptrCourseId) {
                $result['course_id'] = $ptrCourseId;
            }

            return response()->json([
                'data' => $result,
                'success' => true, 'status' => 'success',
                'message' => 'Question Saved.',
            ]);
        }
        // For PTR quizzes, handle course-specific completion logic
        if ($quiz->id == config('ptr.quiz_id')) {
            // Helper::debug(['PTR Quiz Attempt - Course ID Check' => [
            //     'quiz_id' => $quiz->id,
            //     'ptr_quiz_id' => config('ptr.quiz_id'),
            //     'ptr_course_id' => $ptrCourseId,
            //     'requested_course_id' => $request->query('course_id'),
            //     'user_id' => auth()->user()->id
            // ]], 'dump', 'testn2');

            // Check if there's already a quiz attempt for this specific course
            $existingAttempt = QuizAttempt::where('user_id', auth()->user()->id)
                ->where('quiz_id', $quiz->id)
                ->where('course_id', $ptrCourseId)
                ->orderBy('id', 'DESC')
                ->first();

            if ($existingAttempt) {
                // Helper::debug(['PTR Quiz Attempt - Existing Attempt Found' => [
                //     'existing_attempt_id' => $existingAttempt->id,
                //     'existing_course_id' => $existingAttempt->course_id,
                //     'existing_system_result' => $existingAttempt->system_result,
                //     'existing_status' => $existingAttempt->status,
                //     'ptr_course_id' => $ptrCourseId
                // ]], 'dump', 'testn2');

                // If there's an existing attempt for this course, use it instead of creating new one
                $lastQuizAttempt = $existingAttempt;

                // Check if this attempt is already completed for this course
                if (in_array($existingAttempt->system_result, ['COMPLETED', 'EVALUATED', 'MARKED']) &&
                    !in_array($existingAttempt->status, ['FAIL', 'RETURNED', 'ATTEMPTING'])) {
                    // Check if user still needs PTR for this specific course
                    $ptrService = app(\App\Services\PtrCompletionService::class);
                    $ptrCompleted = $ptrService->hasCompletedPtrForCourse(auth()->user()->id, $ptrCourseId);

                    if ($ptrCompleted) {
                        // PTR already completed for this course, return existing status
                        $data = [$existingAttempt, 'next_step' => $this->nextStep($quiz, $existingAttempt), 'submitted_answers' => array_keys($existingAttempt->submitted_answers->toArray()), 'questions' => $existingAttempt->questions->pluck('id')->toArray()];

                        // For PTR quizzes, include the course_id in the response
                        if ($quiz->id == $ptrQuizId && $ptrCourseId) {
                            $data['course_id'] = $ptrCourseId;
                        }

                        return response()->json([
                            'data' => $data,
                            'success' => true,
                            'status' => 'success',
                            'message' => 'PTR already completed for this course.',
                        ]);
                    }
                }

                // Use existing attempt for updates
                goto skipAlreadyAttemptedCheck;
            } else {
                // Helper::debug(['PTR Quiz Attempt - No Existing Attempt Found' => [
                //     'ptr_course_id' => $ptrCourseId,
                //     'user_id' => auth()->user()->id,
                //     'quiz_id' => $quiz->id
                // ]], 'dump', 'testn2');
            }
        }

        if (in_array($lastQuizAttempt->system_result, ['COMPLETED', 'EVALUATED', 'MARKED']) && !in_array($lastQuizAttempt->status, ['FAIL', 'RETURNED', 'ATTEMPTING'])) {
            abort(403, "You already attempted this quiz");
        }

        skipAlreadyAttemptedCheck:
        // Helper::debug(['PTR Course' => [
        //     'ptr_course_id' => $ptrCourseId,
        //     'last_quiz_attempt_status' => $lastQuizAttempt->system_result,
        //     'user_id' => auth()->user()->id,
        //     'quiz_id' => $quiz->id
        // ]], 'dd', 'testn2');

        if ($lastQuizAttempt->attempt >= ($quiz->allowed_attempts ?? 999)) {
            return Helper::errorResponse('Max attempts reached', 403);
        }

        if ($lastQuizAttempt->system_result === 'INPROGRESS') {
            $result = $this->updateQuizAttempt($lastQuizAttempt->attempt, $quiz, $lastQuizAttempt, $request);
        } elseif ($lastQuizAttempt->status === 'RETURNED' || $lastQuizAttempt->status === 'FAIL') {
            $notifications = auth()->user()->unreadNotifications()->where('type', \App\Notifications\AssessmentReturned::class)->get();
            foreach ($notifications as $notification) {
                if ($notification->data['assessment'] === $lastQuizAttempt->id) {
                    $notification->markAsRead();
                }
            }

            $result = $this->createQuizAttempt(($lastQuizAttempt->attempt + 1), $quiz, $request, $ptrCourseId);
        }
        // For PTR quizzes, include the course_id in the response
        if ($quiz->id == $ptrQuizId && $ptrCourseId) {
            $result['course_id'] = $ptrCourseId;
        }

        return response()->json([
            'data' => $result,
            'success' => true, 'status' => 'success',
            'message' => 'Question Saved.',
        ]);
    }

    private function createQuizAttempt(int $attempt, Quiz $quiz, Request $request, $ptrCourseId = null)
    {
        $topic = $quiz->topic;
        $lesson = $topic->lesson;
        $course = $lesson->course;
        $questions = $quiz->questions()->orderBy('order', 'ASC')->get()->toArray();

        $submitted_answers = [$request->question => $request->answer];

        $attempting = new QuizAttempt();
        $attempting->user_id = auth()->user()->id;
        // For PTR quizzes, use the course ID from the student's PTR requirement
        // Otherwise, use the quiz's associated course
        $attempting->course_id = $ptrCourseId ?? $course->id;

        // Debug logging for quiz attempt creation
        // Helper::debug(['Quiz Attempt Creation Debug:' => [
        //     'ptr_course_id' => $ptrCourseId,
        //     'quiz_course_id' => $course->id,
        //     'final_course_id' => $attempting->course_id,
        //     'quiz_id' => $quiz->id
        // ]], 'dump', 'sharonw6');
        $attempting->lesson_id = $lesson->id;
        $attempting->topic_id = $topic->id;
        $attempting->quiz_id = $quiz->id;
        $attempting->questions = $questions;
        $attempting->submitted_answers = $submitted_answers;
        $attempting->attempt = $attempt;
        $attempting->system_result = 'INPROGRESS';
        $attempting->status = 'ATTEMPTING';
        $attempting->user_ip = $request->ip();
        $attempting->save();

        if ($this->allowDebug) {
            dump('Quiz completion check:', [
                'quiz_id' => $quiz->id,
                'questions_count' => count($questions),
                'submitted_answers_count' => count($attempting->submitted_answers),
                'is_completed' => $this->isCompleted($attempting),
            ]);
        }

        if ($this->isCompleted($attempting)) {
            if ($this->allowDebug) {
                dump('Quiz is completed! Processing completion...');
            }

            $lastQuizAttempt = $this->systemEvaluation($request, $quiz, $attempting);
            if (count($questions) === 1) {
                $lastQuizAttempt->system_result = 'COMPLETED';
                $lastQuizAttempt->status = 'SUBMITTED';
                $lastQuizAttempt->submitted_at = Carbon::now();
            }
            $lastQuizAttempt->save();

            // For PTR quizzes, create completion records for all qualifying courses
            if ($quiz->id == config('ptr.quiz_id')) {
                if ($this->allowDebug) {
                    dump('Creating PTR completion records...');
                }
                $this->createPtrCompletionForAllQualifyingCourses($lastQuizAttempt);
            }

            event(new \App\Events\QuizAttemptStatusChanged($lastQuizAttempt));
        }

        // Determine if this is the last question
        $isLastQuestion = $this->isCompleted($attempting);
        $nextStep = $isLastQuestion ?
            ['last' => 1, 'step' => 2, 'qid' => 1, 'last_question_id' => 1] :
            ['last' => 0, 'step' => 2, 'qid' => 1, 'last_question_id' => 1];

        if ($this->allowDebug) {
            dump('Quiz return data:', [
                'is_last_question' => $isLastQuestion,
                'next_step' => $nextStep,
                'intended_url' => $quiz->id == config('ptr.quiz_id') ? 'will be set below' : 'not PTR quiz',
            ]);
        }

        $return = [$attempting, 'next_step' => $nextStep, 'submitted_answers' => array_keys($submitted_answers), 'questions' => $attempting->questions->pluck('id')->toArray()];

        // Add intended URL for PTR quiz completion
        if ($quiz->id == config('ptr.quiz_id')) {
            // For PTR quizzes, check if we're in onboarding context
            $referer = request()->header('referer');
            $isOnboarding = $referer && strpos($referer, 'onboard') !== false;

            if ($isOnboarding) {
                // If coming from onboarding, redirect to step 6
                $intendedUrl = route('frontend.onboard.create', ['step' => 6, 'resumed' => 1]);
            } elseif ($ptrCourseId) {
                // Otherwise, redirect to the course that required PTR completion
                $course = \App\Models\Course::find($ptrCourseId);
                if ($course) {
                    $intendedUrl = route('frontend.lms.courses.show', [$course->id, $course->slug]);
                } else {
                    $intendedUrl = session('url.intended', route('frontend.dashboard'));
                }
            } else {
                $intendedUrl = session('url.intended', route('frontend.dashboard'));
            }
            $return['intended_url'] = $intendedUrl;
        }

        return $return;
    }

    /**
     * @param QuizAttempt $attempt
     *
     * @return bool
     */
    private function isCompleted(QuizAttempt $attempt): bool
    {
        $questions = is_array($attempt->questions) ? count($attempt->questions) : $attempt->questions->count();
        $answers = is_array($attempt->submitted_answers) ? count($attempt->submitted_answers) : $attempt->submitted_answers->count();

        if ($this->allowDebug) {
            dump('isCompleted check:', [
                'questions_count' => $questions,
                'answers_count' => $answers,
                'questions' => $attempt->questions,
                'submitted_answers' => $attempt->submitted_answers,
                'result' => $questions <= $answers,
            ]);
        }

        return $questions <= $answers;
    }

    private function systemEvaluation(Request $request, Quiz $quiz, QuizAttempt $lastQuizAttempt): QuizAttempt
    {
        if ($quiz->passing_percentage > 0) {
            //need to evaluate questions by system
            $correct_answers = [];
            $results = [];
            $total_questions = count($lastQuizAttempt->questions);
            foreach ($lastQuizAttempt->questions as $question) {
                $correctAnswers = $question['correct_answer'];
                if (!empty($correctAnswers)) {
                    $isCorrect = false;
                    if ($question['answer_type'] === 'MCQ') {
                        $correctAnswers = json_decode($correctAnswers, true);
                        $isCorrect = $correctAnswers === $lastQuizAttempt->submitted_answers[$question['id']];
                    } else {
                        $isCorrect = intval($correctAnswers) === intval($lastQuizAttempt->submitted_answers[$question['id']]);
                    }

                    if ($isCorrect) {
                        $correct_answers[] = $question['id'];
                        $results[$question['id']] = ['status' => 'correct', 'comment' => 'Marked by System'];
                    } elseif (empty($results[$question['id']])) {
                        $results[$question['id']] = ['status' => 'incorrect', 'comment' => 'Marked by System'];
                    }
                }
            }
            //            dd( $results, $correct_answers );
            $lastQuizAttempt->system_result = 'EVALUATED';
            $lastQuizAttempt->accessor_id = 0;
            $lastQuizAttempt->accessed_at = Carbon::now();

            $countCorrectAnswers = count($correct_answers);
            $obtainedPercentage = ($countCorrectAnswers / $total_questions) * 100;
            if ($countCorrectAnswers > 0 && $obtainedPercentage >= $quiz->passing_percentage) {
                //PASS
                $lastQuizAttempt->status = 'SATISFACTORY';
                CourseProgressService::updateQuizPassedCourseProgress($lastQuizAttempt->user_id, [
                    'course_id' => $lastQuizAttempt->course_id,
                    'lesson_id' => $lastQuizAttempt->lesson_id,
                    'topic_id' => $lastQuizAttempt->topic_id,
                    'quiz_id' => $lastQuizAttempt->quiz_id,
                    'attempt' => $lastQuizAttempt,
                ], Carbon::now()->toDateTimeString());
            } else {
                //FAIL
                $lastQuizAttempt->status = 'FAIL';
            }
            //            $lastQuizAttempt->save();
            //            Helper::debug('Now adding evaluation and feedback','dump','patrickb2');

            $evaluation = new Evaluation([
                'results' => $results,
                'student_id' => $lastQuizAttempt->user_id,
                'evaluator_id' => 0,
                'status' => (($lastQuizAttempt->status === 'FAIL') ? "UNSATISFACTORY" : "SATISFACTORY"),
            ]);
            $feedback = new Feedback([
                'body' => ['obtained' => $obtainedPercentage, 'passing' => $quiz->passing_percentage, 'message' => "You have obtained {$obtainedPercentage}%. Passing marks: {$quiz->passing_percentage}%."],
                'user_id' => $lastQuizAttempt->user_id,
                'owner_id' => 0,
            ]);
            //            dd($lastQuizAttempt, $evaluation, $correct_answers, $feedback);
            $lastQuizAttempt->quiz->feedbacks()->save($feedback);
            $lastQuizAttempt->evaluation()->save($evaluation);

            $this->activityService->setActivity(['user_id' => auth()->user()->id, 'activity_event' => 'QUIZ AUTO MARKED', 'activity_details' => ['user_id' => auth()->user()->id, 'status' => $lastQuizAttempt->status, 'by' => 'system']], $quiz);
        }
        //        dump('is COMPLETED?');
        if ($lastQuizAttempt->system_result === 'COMPLETED') {
            //            dump('yes its COMPLETED');
            $lesson = $lastQuizAttempt->lesson;
            //            \Log::info( 'checking competency', [ 'user_id' => auth()->user()->id, 'lesson' => $lesson->id ] );
            StudentCourseService::addCompetency(auth()->user()->id, $lesson);
        }

        //        dd($lastQuizAttempt);
        if ($quiz->id !== config('constants.precourse_quiz_id', 99999)) {
            //save progress
            //            dump($lastQuizAttempt);
            $progress = $this->courseProgress->markProgress($lastQuizAttempt->user_id, $lastQuizAttempt->course_id, $lastQuizAttempt);
            $progress1 = CourseProgressService::reEvaluateProgress($lastQuizAttempt->user_id, $progress);
            //            dd($progress, $progress1);
            if (is_bool($progress1)) {
                CourseProgressService::updateProgressSession($progress);
            } else {
                CourseProgressService::updateProgressSession($progress1);
            }
        }

        return $lastQuizAttempt;
    }

    private function updateQuizAttempt(int $attempt, Quiz $quiz, QuizAttempt $lastQuizAttempt, Request $request)
    {
        $submitted_answers = array_replace($lastQuizAttempt->submitted_answers->toArray(), [$request->question => $request->answer]);
        $lastQuizAttempt->submitted_answers = $submitted_answers;
        $lastQuizAttempt->attempt = $attempt;
        $lastQuizAttempt->save();

        if ($this->isCompleted($lastQuizAttempt)) {
            $lastQuizAttempt->system_result = 'COMPLETED';
            $lastQuizAttempt->status = 'SUBMITTED';
            $lastQuizAttempt->submitted_at = Carbon::now();

            $topic = $quiz->topic;
            $quizCount = $topic->quizzes()->count();
            $this->activityService->setActivity([
                'user_id' => auth()->user()->id,
                'activity_event' => 'QUIZ ATTEMPT',
                'activity_details' => [
                    'user_id' => auth()->user()->id,
                    'status' => 'SUBMITTED',
                    'by' => 'user: ' . auth()->user()->toJson(),
                    'time_spent' => number_format(($topic->estimated_time / $quizCount), 2),
                    'topic_time' => $topic->estimated_time,
                    'total_quizzes' => $quizCount,
                ],
            ], $quiz);

            $lastQuizAttempt = $this->systemEvaluation($request, $quiz, $lastQuizAttempt);
        } else {
            $lastQuizAttempt->status = 'ATTEMPTING';
        }
        $lastQuizAttempt->save();

        event(new \App\Events\QuizAttemptStatusChanged($lastQuizAttempt));

        if ($quiz->id !== 0) {
            //save progress
            $this->courseProgress->markProgress(auth()->user()->id, $lastQuizAttempt->course_id, $lastQuizAttempt);
        }

        //        dd($this->isCompleted( $lastQuizAttempt ), $quiz->id);

        $return = [$lastQuizAttempt, 'next_step' => $this->nextStep($quiz, $lastQuizAttempt), 'submitted_answers' => array_keys($submitted_answers), 'questions' => $lastQuizAttempt->questions->pluck('id')->toArray()];

        // Add intended URL for PTR quiz completion
        if ($quiz->id == config('ptr.quiz_id')) {
            // For PTR quizzes, check if we're in onboarding context
            $referer = request()->header('referer');
            $isOnboarding = $referer && strpos($referer, 'onboard') !== false;

            if ($isOnboarding) {
                // If coming from onboarding, redirect to step 6
                $intendedUrl = route('frontend.onboard.create', ['step' => 6, 'resumed' => 1]);
            } else {
                // Otherwise, redirect to the course that required PTR completion
                // Since this is updateQuizAttempt, we need to get the course from the quiz attempt
                $course = \App\Models\Course::find($lastQuizAttempt->course_id);
                if ($course) {
                    $intendedUrl = route('frontend.lms.courses.show', [$course->id, $course->slug]);
                } else {
                    $intendedUrl = session('url.intended', route('frontend.dashboard'));
                }
            }
            $return['intended_url'] = $intendedUrl;
        }

        return $return;
    }

    private function createPtrCompletionForAllQualifyingCourses(QuizAttempt $lastQuizAttempt)
    {
        $ptrService = app(\App\Services\PtrCompletionService::class);
        $user = auth()->user();

        // Get all qualifying courses for the user (no main course restriction)
        $enrolments = \App\Models\StudentCourseEnrolment::where('user_id', $user->id)
            ->where('status', '!=', 'DELIST')
            ->whereHas('course', function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->with('course')
            ->get();

        foreach ($enrolments as $enrolment) {
            // Skip excluded categories
            if (in_array($enrolment->course?->category, config('ptr.excluded_categories', []))) {
                continue;
            }

            $ptrService->createPtrCompletionRecord($user->id, $enrolment->course_id, $lastQuizAttempt->quiz_id, $lastQuizAttempt->id);
        }
    }
}
