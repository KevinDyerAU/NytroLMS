<?php

use App\Http\Controllers\AccountManager\CompanyController;
use App\Http\Controllers\AccountManager\DocumentController;
use App\Http\Controllers\AccountManager\NoteController;
use App\Http\Controllers\AccountManager\StudentController;
use App\Http\Controllers\AccountManager\WorkPlacementController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\AssessmentsController;
use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\LMS\CourseController;
use App\Http\Controllers\LMS\LessonController;
use App\Http\Controllers\LMS\QuestionController;
use App\Http\Controllers\LMS\QuizController;
use App\Http\Controllers\LMS\TopicController;
use App\Http\Controllers\Reports\AdminReportController;
use App\Http\Controllers\Reports\EnrolmentReportController;
use App\Http\Controllers\Reports\WorkPlacementsReport;
use App\Http\Controllers\Select2Controller;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\Course;
use App\Models\Image;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Route::middleware('auth:api')->prefix('v1')->group(function(){
// Route::middleware('auth:sanctum')->prefix('v1')->group(function(){
Route::prefix('v1')
    ->middleware(['auth', 'enforce.user.session'])
    ->group(function () {
        // Activity tracking routes
        Route::post('/activity/update', [
            ActivityController::class,
            'updateActivity',
        ]);
        Route::get('/activity/status', [
            ActivityController::class,
            'getSessionStatus',
        ]);

        Route::get('/select2/{source}', [
            Select2Controller::class,
            'getSource',
        ]);
        Route::get('/leaders', function (Request $request) {
            $leaders = User::onlyLeaders()->get();

            return new UserCollection($leaders);
        });

        // User validation endpoint
        Route::get('/validate-user/{userId}', function ($userId) {
            $exists = User::userIdExists($userId);

            return response()->json([
                'exists' => $exists,
                'user_id' => $userId,
            ]);
        })->where('userId', '[0-9]+');

        // Email validation endpoint
        Route::get('/email-already-registered/{email}', function ($email) {
            $exists = User::where('email', $email)->exists();

            return response()->json([
                'exists' => $exists,
                'email' => $email,
            ]);
        });

        // Check if email belongs to a student in leader's company
        Route::get('/email-student-in-company/{email}', function ($email) {
            $user = auth()->user();

            if (!$user->isLeader()) {
                return response()->json([
                    'found' => false,
                    'email' => $email,
                ]);
            }

            // Get leader's company IDs
            $leaderCompanyIds = $user->companies->pluck('id')->toArray();

            if (empty($leaderCompanyIds)) {
                return response()->json([
                    'found' => false,
                    'email' => $email,
                ]);
            }

            // Check if email belongs to a student in the leader's company
            $student = User::where('email', $email)
                ->onlyStudents()
                ->whereHas('companies', function ($query) use ($leaderCompanyIds) {
                    $query->whereIn('companies.id', $leaderCompanyIds);
                })
                ->first();

            if ($student) {
                return response()->json([
                    'found' => true,
                    'email' => $email,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                ]);
            }

            return response()->json([
                'found' => false,
                'email' => $email,
            ]);
        });

        // Course enrollment validation endpoint
        Route::get('/course-already-enrolled/{studentId}/{courseId}', function ($studentId, $courseId) {
            $exists = \App\Models\StudentCourseEnrolment::where('user_id', $studentId)
                ->where('course_id', $courseId)
                ->where('status', '!=', 'DELIST')
                ->exists();

            return response()->json([
                'exists' => $exists,
                'student_id' => $studentId,
                'course_id' => $courseId,
            ]);
        })->where(['studentId' => '[0-9]+', 'courseId' => '[0-9]+']);

        // Bulk user validation endpoint
        Route::post('/validate-users', function (Request $request) {
            $userIds = $request->input('user_ids', []);
            $results = [];

            foreach ($userIds as $userId) {
                $results[$userId] = User::userIdExists($userId);
            }

            return response()->json([
                'results' => $results,
                'total' => count($userIds),
                'valid' => array_sum($results),
                'invalid' => count($userIds) - array_sum($results),
            ]);
        });
        Route::get('/leaders/{leader}', function (User $leader) {
            if (!$leader->hasRole('Leader')) {
                throw new NotFoundHttpException(
                    'This user does not exists or is invalid leader.'
                );
            }

            return new UserResource($leader->load('detail'));
        });
        Route::get('/companies/{id}', function ($id) {
            $company = Company::findOrFail($id);

            return new CompanyResource($company->load('leaders'));
        })->where('id', '[0-9]+');
        // companies tab
        Route::get('/company/leaders/{company}', [
            CompanyController::class,
            'getLeaders',
        ])->where('company', '[0-9]+');
        Route::get('/company/leaders/{company}/data', [
            CompanyController::class,
            'getLeadersData',
        ])->where('company', '[0-9]+');
        Route::get('/company/students/{company}', [
            CompanyController::class,
            'getStudents',
        ])->where('company', '[0-9]+');
        Route::get('/company/students/{company}/data', [
            CompanyController::class,
            'getStudentsData',
        ])->where('company', '[0-9]+');

        // student tabs
        Route::get('/student/{id}', function ($id) {
            $student = User::with('detail', 'companies', 'leaders', 'trainers', 'enrolments')->findOrFail($id);

            // Check if user has access to this student
            if (auth()->user()->isLeader()) {
                $userCompanies = auth()->user()->companies->pluck('id');
                $studentCompanies = $student->companies->pluck('id');
                if (!$userCompanies->intersect($studentCompanies)->count()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }

            // Get basic enrolment data
            $basicEnrolment = $student->enrolments()->where('enrolment_key', 'basic')->first();
            $enrolmentData = $basicEnrolment ? $basicEnrolment->enrolment_value : null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'preferred_name' => $student->detail->preferred_name ?? '',
                    'email' => $student->email,
                    'phone' => $student->detail->phone ?? '',
                    'address' => $student->detail->address ?? '',
                    'purchase_order' => $student->detail->purchase_order ?? '',
                    'preferred_language' => $student->detail->preferred_language ?? '',
                    'language' => $student->detail->language ?? 'en',
                    'study_type' => $student->study_type ?? '',
                    'schedule' => $enrolmentData['schedule'] ?? '',
                    'employment_service' => $enrolmentData['employment_service'] ?? '',
                    'companies' => $student->companies->pluck('id')->toArray(),
                    'leaders' => $student->leaders->pluck('id')->toArray(),
                    'trainers' => $student->trainers->pluck('id')->toArray(),
                ],
            ]);
        })->where('id', '[0-9]+');
        Route::get('/student/enrolment/{student}/{key?}', [
            StudentController::class,
            'getEnrolment',
        ])->where('student', '[0-9]+');
        //    Route::get( '/student/documents/{student}', [ StudentController::class, 'getDocuments' ] )->where( 'student', '[0-9]+' );
        Route::post('/student/history/{student}', [
            StudentController::class,
            'getHistory',
        ])->where('student', '[0-9]+');
        Route::get('/student/training-plan/{student}', [
            StudentController::class,
            'getTrainingPlan',
        ])->where('student', '[0-9]+');
        Route::get('/student/assessments/{student}', [
            StudentController::class,
            'getAssessments',
        ])->where('student', '[0-9]+');
        Route::get('/student/activities/{student}', [
            StudentController::class,
            'getStudentActivities',
        ])->where('student', '[0-9]+');
        Route::get('/student/activities/{student}/data', [
            StudentController::class,
            'getStudentActivitiesData',
        ])->where('student', '[0-9]+');

        Route::get('documents/all/{student}', [
            DocumentController::class,
            'index',
        ])->where('student', '[0-9]+');
        Route::get('documents/{document}', [
            DocumentController::class,
            'show',
        ])->where('document', '[0-9]+');
        Route::delete('documents/{document}', [
            DocumentController::class,
            'destroy',
        ])->where('document', '[0-9]+');
        Route::post('documents/{student}', [
            DocumentController::class,
            'store',
        ])->where('student', '[0-9]+');

        Route::get('notes/all/{subject}/{id}', [NoteController::class, 'index'])
            ->where('subject', '[a-zA-Z]+')
            ->where('id', '[0-9]+');
        Route::get('notes/{note}', [NoteController::class, 'show'])->where(
            'note',
            '[0-9]+'
        );
        Route::delete('notes/{note}', [
            NoteController::class,
            'destroy',
        ])->where('note', '[0-9]+');
        Route::post('notes', [NoteController::class, 'store']);
        Route::post('notes/bulk', [NoteController::class, 'bulkStore']);
        Route::post('notes/{note}/pin', [NoteController::class, 'pin'])->where(
            'note',
            '[0-9]+'
        );

        Route::get('/work-placements/{studentId}/{courseId}/dates', [
            WorkPlacementController::class,
            'getCourseDates',
        ]);
        Route::get('/work-placements/{student}', [
            WorkPlacementController::class,
            'index',
        ])->name('api.work-placements.all');
        Route::get('/work-placements/data/{student}', [
            WorkPlacementController::class,
            'data',
        ])->name('api.work-placements.data');
        Route::get('/work-placements/show/{workPlacement}', [
            WorkPlacementController::class,
            'show',
        ])->name('api.work-placements.show');
        Route::post('/work-placements', [
            WorkPlacementController::class,
            'store',
        ])->name('api.work-placements.store');
        Route::put('/work-placements/{workPlacement}', [
            WorkPlacementController::class,
            'update',
        ])->name('api.work-placements.update');
        Route::delete('/work-placements/{workPlacement}', [
            WorkPlacementController::class,
            'destroy',
        ])->name('api.work-placements.destroy');

        Route::post('/students/certificate/issue', [
            StudentController::class,
            'issueCertificate',
        ]);
        Route::post('/students/progress/evaluate', [
            StudentController::class,
            'reEvaluateProgress',
        ]);
        Route::post('/students/progress/reset', [
            StudentController::class,
            'resetProgress',
        ]);
        Route::get('/students/{student}/get_courses', [
            StudentController::class,
            'get_courses',
        ])->where('student', '[0-9]+');
        Route::post('/students/{student}/assign_course', [
            StudentController::class,
            'assign_course',
        ])->where('student', '[0-9]+');
        Route::post('/mark/work_placement/{lesson}', [
            StudentController::class,
            'markWorkPlacementComplete',
        ])->where('lesson', '[0-9]+');
        Route::post('/upload/checklist/{quiz}', [
            StudentController::class,
            'uploadQuizChecklist',
        ])->where('quiz', '[0-9]+');
        Route::post('/upload/evidence/{lesson}', [
            StudentController::class,
            'uploadEvidenceChecklist',
        ])->where('lesson', '[0-9]+');
        Route::post('/mark/lesson/{lesson}', [
            StudentController::class,
            'markLessonComplete',
        ])->where('lesson', '[0-9]+');
        Route::post('/mark/topic/{topic}', [
            StudentController::class,
            'markTopicComplete',
        ])->where('topic', '[0-9]+');
        Route::post('/competent/lesson/{lesson}', [
            StudentController::class,
            'competentLessonComplete',
        ])->where('lesson', '[0-9]+');

        Route::post('/courses/{course}/reorder/{type}', [
            CourseController::class,
            'reorder',
        ])->where('course', '[0-9]+');
        Route::post('/lessons/{lesson}/reorder/{type}', [
            LessonController::class,
            'reorder',
        ])->where('lesson', '[0-9]+');
        Route::post('/topics/{topic}/reorder/{type}', [
            TopicController::class,
            'reorder',
        ])->where('topic', '[0-9]+');
        Route::post('/quizzes/{quiz}/reorder/{type}', [
            QuizController::class,
            'reorder',
        ])->where('quiz', '[0-9]+');
        Route::post('/questions/{question}/reorder/{type}', [
            QuestionController::class,
            'reorder',
        ])->where('question', '[0-9]+');
        Route::delete('/questions/{question}', [
            QuestionController::class,
            'destroy',
        ])->where('question', '[0-9]+');
        Route::delete('/lessons/{lesson}', [
            LessonController::class,
            'destroy',
        ])->where('lesson', '[0-9]+');
        Route::delete('/topics/{topic}', [
            TopicController::class,
            'destroy',
        ])->where('topic', '[0-9]+');
        Route::delete('/quizzes/{quiz}', [
            QuizController::class,
            'destroy',
        ])->where('quiz', '[0-9]+');

        Route::get('/courses/{id}', function ($id) {
            $course = Course::with('lessons')
                ->where('id', $id)
                ->first();

            return new \App\Http\Resources\CourseResource($course);
        })->where('id', '[0-9]+');
        Route::get('/lessons/{id}', function ($id) {
            $lesson = Lesson::with('topics')
                ->where('id', $id)
                ->first();

            return new \App\Http\Resources\LessonResource($lesson);
        })->where('id', '[0-9]+');
        Route::get('/topics/{id}', function ($id) {
            $topic = Topic::with('quizzes')
                ->where('id', $id)
                ->first();

            return new \App\Http\Resources\TopicResource($topic);
        })->where('id', '[0-9]+');

        Route::post('/attempt/{quiz}', [
            \App\Http\Controllers\Frontend\LMS\QuizController::class,
            'attempt',
        ])->where('quiz', '[0-9]+');

        Route::middleware('teachable')->group(function () {
            Route::post('assessments/{assessment}/answer', [
                AssessmentsController::class,
                'answerPost',
            ])->whereNumber('assessment');
            Route::post('assessments/{assessment}/feedback', [
                AssessmentsController::class,
                'feedbackPost',
            ])->whereNumber('assessment');
            Route::post('assessments/{assessment}/email', [
                AssessmentsController::class,
                'emailPost',
            ])->whereNumber('assessment');
            Route::post('assessments/{assessment}/return', [
                AssessmentsController::class,
                'returnPost',
            ])->whereNumber('assessment');
        });

        Route::get('reports/work-placements/{report}', [
            WorkPlacementsReport::class,
            'getReport',
        ])->whereNumber('work-placement');
        Route::get('reports/admins/{report}', [
            AdminReportController::class,
            'getReport',
        ])->whereNumber('report');
        Route::get('competencies/{competency}', [
            CompetencyController::class,
            'getCompetency',
        ])->whereNumber('competency');
        //    Route::get('reports/enrolments/{report}', [ EnrolmentReportController::class, 'getReport'])->whereNumber('report');

        Route::delete('/images/{image}', function (Image $image) {
            Storage::delete($image->file_path);
            $image->delete();

            return response()->json(
                [
                    'data' => $image,
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Image Deleted Successfully!',
                ],
                202
            );
        })->where('image', '[0-9]+');

        Route::get('/theme/session/{theme}', function ($theme) {
            session()->put('theme', $theme);

            return response()->json(
                [
                    'data' => ['newTheme' => $theme],
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Theme Updated!',
                ],
                202
            );
        });
    });
