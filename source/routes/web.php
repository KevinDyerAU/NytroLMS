<?php

use App\Http\Controllers\AccountManager\CompanyController;
use App\Http\Controllers\AccountManager\LeaderController;
use App\Http\Controllers\AccountManager\SignupController;
use App\Http\Controllers\AccountManager\StudentController;
use App\Http\Controllers\AccountManager\TrainerController;
use App\Http\Controllers\AccountManager\WorkPlacementController;
use App\Http\Controllers\AdminToolController;
use App\Http\Controllers\AssessmentsController;
use App\Http\Controllers\Auth\UsernameController;
use App\Http\Controllers\BulkActionsController;
use App\Http\Controllers\CompetencyController;
use App\Http\Controllers\EnrolmentController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\LMS\AttemptController;
use App\Http\Controllers\Frontend\LMS\CourseController as CourseControllerFrontend;
use App\Http\Controllers\Frontend\LMS\LessonController as LessonControllerFrontend;
use App\Http\Controllers\Frontend\LMS\QuizController as QuizControllerFrontend;
use App\Http\Controllers\Frontend\LMS\TopicController as TopicControllerFrontend;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LMS\CourseController;
use App\Http\Controllers\LMS\LessonController;
use App\Http\Controllers\LMS\QuestionController;
use App\Http\Controllers\LMS\QuizController;
use App\Http\Controllers\LMS\TopicController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\Reports\AdminReportController;
use App\Http\Controllers\Reports\CompetencyReportController;
use App\Http\Controllers\Reports\EnrolmentReportController;
use App\Http\Controllers\Reports\WorkPlacementsReport;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\StaterkitController;
use App\Http\Controllers\User\AvatarController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\UserController;
use App\Mail\testEmail;
use App\Models\User;
use App\Notifiables\CronJobNotifier;
use App\Notifications\SlackAlertNotification;
use App\Notifications\TestEmailNotification;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use UniSharp\LaravelFilemanager\Lfm;

Auth::routes(['register' => false]);
Route::post('username/request', [UsernameController::class, 'sendUsernameRequestForm'])->name('username.send');
Route::get('username/request', [UsernameController::class, 'showUsernameRequestForm'])->name('username.request');

Route::middleware('auth')->prefix('test')->group(function () {
    Route::get('/countries', [App\Http\Controllers\Settings\CountryController::class, 'toDatabaseArray'])->name('countriesWithTimezones');
    Route::get('/email', function (Request $request) {
        $theUser = $request->user();
        $mailable = new testEmail($theUser);
        Mail::to($theUser)->send($mailable);

        return $mailable;
    });
    Route::get('/notification', function () {
        $user = new User();
        $user->id = 123;
        $user->first_name = "John";
        $user->last_name = "Doe";
        $user->email = null; // Invalid email
        //        $user->notify(new TestEmailNotification);
        (new CronJobNotifier())->notify(new SlackAlertNotification(
            message: "Invalid or missing email address detected for user",
            fields: [
                'User ID' => $user->id ?? 'Unknown',
                'Name' => $user->name ?? 'Unknown',
                'Email' => $user->email ?? 'None',
                'Environment' => config('app.env', 'unknown'),
            ],
            level: 'error'
        ));

        return "Notification sent";
    });
    Route::get('/training-plan/{user}', [StudentController::class, 'getTrainingPlanNew'])
        ->middleware(['auth'])
        ->name('test.training-plan');
});
Route::get('/debug-sentry', function () {
    throw new Exception('My first Sentry error!');
});
Route::post('log/errors/{type}', function (Request $request, $type) {
    \Log::error($type . ' error:' . json_encode($request->toArray()));
});
Route::post('log/warnings/{type}', function (Request $request, $type) {
    \Log::warning($type . ' error:' . json_encode($request->toArray()));
});
Route::name('verification.')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->middleware('auth')->name('notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        $user = $request->user();
        if (empty($user->password_change_at) && !empty($user->email_verified_at)) {
            $status = Password::sendResetLink(
                ['email' => $user->email]
            );
            Auth::logout();

            return $status === Password::RESET_LINK_SENT
                ? redirect(route('login'))->with(['status' => __($status)])
                : redirect(route('login'))->withErrors(['email' => __($status)]);
        }
        Auth::logout();

        return redirect('/login');
    })->middleware(['auth', 'signed'])->name('verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent!');
    })->middleware(['auth', 'throttle:6,1'])->name('resend');
});

Route::get('/', [StaterkitController::class, 'home'])->name('home');
Route::get('/signup/{link}', [SignupController::class, 'create'])->name('signup-link');
Route::post('/signup/{link}', [SignupController::class, 'store'])->name('signup-store');
Route::get('usage-terms', [StaterkitController::class, 'layout_blank'])->name('usage-terms');
Route::get('content/{page}', [StaterkitController::class, 'getContent'])->name('cms');
//Route::resource('content/{article}', CMSController::class)->name('cms')->parameters(['content' => 'article:slug']);

Route::middleware(['auth', 'enforce.user.session'])->group(function () {
    Route::middleware('owner')->group(function () {
        Route::get('profile/{user}/deactivate', [ProfileController::class, 'deactivate'])->name('profile.deactivate');
        Route::get('profile/password/{user}', [ProfileController::class, 'password'])->name('profile.password');
        Route::post('profile/password/{user}', [ProfileController::class, 'passwordReset'])->name('profile.password.reset');
    });
});
Route::middleware(['auth', 'password-reset', 'enforce.user.session'])->group(function () {
    Route::middleware('owner')->group(function () {
        Route::resource('profile', ProfileController::class)
            ->parameters(['profile' => 'user'])
            ->missing(function (Request $request) {
                return Redirect::route('profile.show');
            });
        Route::resource('avatar', AvatarController::class)->only(['show', 'edit', 'update', 'destroy'])
            ->parameters(['avatar' => 'user']);
    });

    //FRONTEND ROUTES
    Route::post('frontend/onboard/{step}/{resumed?}', [EnrolmentController::class, 'store'])->name('frontend.onboard.store')
        ->where('step', '[0-9]*')
        ->where('resumed', '[0-1]*');

    Route::middleware(['onboard'])->group(function () {
        Route::name('frontend.')->prefix('frontend')->group(function () {
            Route::get('dashboard', [FrontendController::class, 'dashboard'])->middleware(['lln.access'])->name('dashboard');
            Route::get('onboard/{step?}/{resumed?}', [EnrolmentController::class, 'create'])->name('onboard.create')
                ->where('step', '[0-9]*')
                ->where('resumed', '[0-1]*');
            // LMS Complete Routes - Protected by enrolment check only (no PTR middleware)
            // These must come BEFORE the generic routes to avoid route conflicts
            Route::middleware(['check.enrolment.locked'])->name('lms.')->prefix('lms')->group(function () {
                Route::get('courses/complete/{course}', [CourseControllerFrontend::class, 'markComplete'])->name('courses.complete');
                Route::get('lessons/complete/{lesson}', [LessonControllerFrontend::class, 'markComplete'])->name('lessons.complete');
                Route::get('topics/complete/{topic}', [TopicControllerFrontend::class, 'markComplete'])->name('topics.complete');
            });

            // LMS Content Routes - Protected by both LLN and PTR middlewares
            Route::middleware(['check.enrolment.locked', 'lln.access', 'ptr.access'])->name('lms.')->prefix('lms')->group(function () {
                Route::get('courses/{course}/{slug?}', [CourseControllerFrontend::class, 'show'])->name('courses.show')->where('slug', '[a-zA-Z0-9-_]+');
                Route::get('lessons/{lesson}/{slug?}', [LessonControllerFrontend::class, 'show'])->name('lessons.show')->where('slug', '[a-zA-Z0-9-_]+');
                Route::get('topics/{topic}/{slug?}', [TopicControllerFrontend::class, 'show'])->name('topics.show')->where('slug', '[a-zA-Z0-9-_]+');
            });

            // LMS Quiz Routes - NO assessment middlewares (allow direct access)
            Route::middleware(['check.enrolment.locked'])->name('lms.')->prefix('lms')->group(function () {
                Route::get('quizzes/{quiz}/{attempt}', [QuizControllerFrontend::class, 'viewResult'])->name('quizzes.attempt')->whereNumber('attempt');
                Route::get('quizzes/{quiz}/{slug?}', [QuizControllerFrontend::class, 'show'])->name('quizzes.show')->where('slug', '[a-zA-Z0-9-_]+');
                Route::post('attempt/{quiz}', [AttemptController::class, 'process'])->name('attempt.quiz')->whereNumber('quiz');
            });
        });
    });

    //ADMIN ROUTES
    Route::middleware(['privileged'])->group(function () {
        Route::get('dashboard', [StaterkitController::class, 'dashboard'])->name('dashboard');

        Route::get('playground', [PlaygroundController::class, 'index'])->name('playground');
        Route::post('playground/process', [PlaygroundController::class, 'process'])->name('playground-process');
        Route::post('playground/course-progress', [PlaygroundController::class, 'courseProgress'])->name('playground-course-progress');
        Route::get('playground/test', [PlaygroundController::class, 'test'])->name('playground-test');
        Route::post('/playground/migrate-content', [PlaygroundController::class, 'migrateLarabergContent'])->name('playground-migrate-content');
        // User data export route - only accessible by mohsina
        Route::get('/user-data-export', [PlaygroundController::class, 'exportUserData'])->name('user.data.export');
        Route::post('/user-data-import', [PlaygroundController::class, 'importUserData'])->name('user.data.import');
        Route::post('/playground/import-user-data', [PlaygroundController::class, 'importUserData'])->name('playground.importUserData');

        // User data deletion routes
        Route::get('/playground/user-deletion', [PlaygroundController::class, 'showUserDeletionForm'])->name('playground.user-deletion-form');
        Route::post('/playground/user-deletion/preview', [PlaygroundController::class, 'previewUserDeletion'])->name('playground.user-deletion-preview');
        Route::post('/playground/user-deletion/execute', [PlaygroundController::class, 'executeUserDeletion'])->name('playground.user-deletion-execute');
        // Admin Tools Routes
        Route::middleware(['can:access admin tools'])->group(function () {
            Route::get('admin-tools', [AdminToolController::class, 'index'])->name('admin-tools.index');
            Route::get('admin-tools/sync-stats', [AdminToolController::class, 'showSyncStudentProfilesForm'])->name('admin-tools.sync-stats');
            Route::get('admin-tools/sync-student-profiles/api', [AdminToolController::class, 'syncStudentProfiles'])->name('admin-tools.sync-student-profiles');
            Route::get('admin-tools/compare-stats', [AdminToolController::class, 'showTestServiceConsistencyForm'])->name('admin-tools.compare-stats');
            Route::get('admin-tools/test-service-consistency/api', [AdminToolController::class, 'testServiceConsistency'])->name('admin-tools.test-service-consistency');
        });

        Route::name('user_management.')->prefix('user-management')->group(function () {
            Route::resource('roles', RoleController::class)->except(['destroy']);
            Route::get('roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
            Route::resource('users', UserController::class)->except(['destroy']);
        });
        Route::group(['middleware' => ['can:view assessments', 'authorized.role']], function () {
            Route::resource('assessments', AssessmentsController::class)->only(['index', 'show'])->whereNumber('assessment');
            Route::post('assessments/export', [AssessmentsController::class, 'index'])->whereNumber('assessment');
        });
        Route::group(['middleware' => ['can:view competency', 'authorized.role']], function () {
            Route::resource('competencies', CompetencyController::class)->only(['index', 'show'])->whereNumber('competency');
            Route::post('competencies/export', [CompetencyController::class, 'index'])->whereNumber('competency');
        });
        Route::name('reports.')->prefix('reports')->group(function () {
            Route::resource('admins', AdminReportController::class)->only(['index', 'show'])->whereNumber('admin');
            Route::post('admins/export', [AdminReportController::class, 'index'])->whereNumber('admin');
            Route::get('enrolments', [EnrolmentReportController::class, 'index'])->name('enrolments.index');
            Route::post('enrolments/export', [EnrolmentReportController::class, 'index'])->name('enrolments.index');
            Route::get('enrolments/{user_id}', [EnrolmentReportController::class, 'show'])->whereNumber('user_id')->name('enrolments.show');
            Route::get('competencies', [CompetencyReportController::class, 'index'])->name('competencies.index');
            Route::post('competencies/{enrolment}/export', [CompetencyReportController::class, 'show'])->whereNumber('enrolment')->name('competencies.export');
            Route::get('competencies/{enrolment}', [CompetencyReportController::class, 'show'])->whereNumber('enrolment')->name('competencies.show');
            Route::get('commenced-units', [\App\Http\Controllers\Reports\CommencedUnitsReportController::class, 'index'])->name('commenced-units.index');
            Route::post('commenced-units/export', [\App\Http\Controllers\Reports\CommencedUnitsReportController::class, 'index'])->name('commenced-units.export');
            Route::resource('work-placements', WorkPlacementsReport::class)->only(['index', 'show'])->whereNumber('work-placement');
            Route::post('work-placements/export', [WorkPlacementsReport::class, 'index'])->whereNumber('work-placement');
            Route::get('daily-enrolment', [\App\Http\Controllers\Reports\DailyEnrolmentReportController::class, 'index'])->name('daily-enrolment.index');
            Route::post('daily-enrolment/export', [\App\Http\Controllers\Reports\DailyEnrolmentReportController::class, 'index'])->name('daily-enrolment.export');
        });
        Route::middleware(['authorized.role'])->name('account_manager.')->prefix('account-manager')->group(function () {
            Route::get('companies/{company}/deactivate', [CompanyController::class, 'deactivate'])->name('companies.deactivate');
            Route::get('companies/{company}/activate', [CompanyController::class, 'activate'])->name('companies.activate');
            Route::post('companies/{company}/signup', [CompanyController::class, 'signupLink'])->name('companies.signup');
            Route::delete('companies/{link}/delete', [CompanyController::class, 'deleteLink'])->name('companies.deleteLink');
            Route::post('companies/export', [CompanyController::class, 'index'])->whereNumber('company');
            Route::resource('companies', CompanyController::class);

            Route::name('leaders.')->prefix('leaders')->group(function () {
                Route::get('onboard', [LeaderController::class, 'onboard'])->name('onboard');
                Route::post('onboard', [LeaderController::class, 'onboardAgreement'])->name('onboard-agreement');
                Route::post('export', [LeaderController::class, 'index'])->whereNumber('leader');
                Route::get('{leader}/activate', [LeaderController::class, 'activate'])->name('activate');
                Route::get('{leader}/deactivate', [LeaderController::class, 'deactivate'])->name('deactivate');
            });
            Route::resource('leaders', LeaderController::class)->except(['destroy']);
            Route::name('trainers.')->prefix('trainers')->group(function () {
                Route::post('export', [TrainerController::class, 'index'])->whereNumber('trainer');
                Route::get('{trainer}/activate', [TrainerController::class, 'activate'])->name('activate');
                Route::get('{trainer}/deactivate', [TrainerController::class, 'deactivate'])->name('deactivate');
            });
            Route::resource('trainers', TrainerController::class)->except(['destroy']);
            Route::name('students.')->prefix('students')->group(function () {
                Route::get('{student}/get_courses', [StudentController::class, 'get_courses'])->name('get_courses');
                Route::get('{student}/overview', [StudentController::class, 'overview'])->name('overview');
                Route::get('{student}/activate', [StudentController::class, 'activate'])->name('activate');
                Route::get('{student}/deactivate', [StudentController::class, 'deactivate'])->name('deactivate');
                Route::post('{student}/resend-password', [StudentController::class, 'resendPassword'])->name('resend-password');
                Route::get('{student}/clean', [StudentController::class, 'cleanStudent'])->name('clean');
                Route::get('{student}/enrolment', [StudentController::class, 'enrolment'])->name('enrolment');
                Route::get('{student}/documents', [StudentController::class, 'documents'])->name('documents');
                Route::get('{student}/skip-llnd', [StudentController::class, 'skipLLND'])->name('skip-llnd');

                Route::get('{student}/edit-enrolment/{step?}', [EnrolmentController::class, 'edit'])
                    ->name('edit-enrolment')->where('step', '[0-9]*')->where('student', '[0-9]+');
                Route::post('{student}/update-enrolment/{step}/{resumed?}', [EnrolmentController::class, 'update'])
                    ->name('update-enrolment')->where('step', '[0-9]*')->where('resumed', '[0-1]*')->where('student', '[0-9]*');
            });
            Route::resource('students', StudentController::class)->except(['destroy']);
            Route::post('students/export', [StudentController::class, 'index'])->whereNumber('student');
            Route::post('/students/{student}/export', [StudentController::class, 'show'])->whereNumber('student');

            Route::get('/students/{student}/re-evaluate', [StudentController::class, 'reEvaluateProgressTest']);

            //            Route::get('/work-placements/create', [WorkPlacementController::class, 'create'])->name('work-placements.create');
            //            Route::post('/work-placements', [WorkPlacementController::class, 'store'])->name('work-placements.store');
        });
        Route::name('lms.')->prefix('lms')->group(function () {
            //            Route::resource('course/posts', PostController::class)->parameters(['posts' => 'course:slug']);
            //            Route::resource('lesson/posts', PostController::class)->parameters(['posts' => 'lesson:slug']);
            //            Route::resource('topic/posts', PostController::class)->parameters(['posts' => 'topic:slug']);
            //            Route::resource('quiz/posts', PostController::class)->parameters(['posts' => 'quiz:slug']);
            Route::get('archived-courses', [CourseController::class, 'getArchivedCourses'])->name('archived-courses.index');
            Route::resource('courses', CourseController::class)->parameters(['courses' => 'course:id']);
            Route::resource('lessons', LessonController::class)->parameters(['lessons' => 'lesson:id']);
            Route::resource('topics', TopicController::class)->parameters(['topics' => 'topic:id']);
            Route::resource('quizzes', QuizController::class)->parameters(['quizzes' => 'quiz:id']);
            Route::resource('questions', QuestionController::class)->parameters(['questions' => 'quiz:id'])->only(['edit', 'update']);
        });
        // Settings
        Route::name('settings.')->prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::get('/site', [SettingsController::class, 'editSiteSettings'])->name('site.edit');
            Route::get('/menu', [SettingsController::class, 'editMenuSettings'])->name('menu.edit');
            Route::get('/featured-images', [SettingsController::class, 'editFeaturedImageSettings'])->name('featured-images.edit');
            Route::post('/{type}', [SettingsController::class, 'update'])->name('update');
        });
        Route::name('bulk-actions.')->prefix('bulk-actions')->group(function () {
            Route::get('bulk-notes', [BulkActionsController::class, 'bulkNotes'])->name('bulk-notes');
        });
    });
    // locale Route
    Route::get('lang/{locale}', [LanguageController::class, 'swap']);
});
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    Lfm::routes();
});

Route::middleware(['auth'])->group(function () {
    // Lesson unlock/lock routes
    Route::prefix('account-manager')->group(function () {
        Route::post('lessons/{lesson}/unlock', [App\Http\Controllers\AccountManager\LessonUnlockController::class, 'unlock'])->name('lessons.unlock');
        Route::post('lessons/{lesson}/lock', [App\Http\Controllers\AccountManager\LessonUnlockController::class, 'lock'])->name('lessons.lock');
    });
});

/*
If you need it for lots of route definitions, then consider registering a Route macro:

Route::macro('resourceAndActive', function ($uri, $controller) {
    Route::get("{$uri}/active", "{$controller}@active")->name("{$uri}.active");
    Route::resource($uri, $controller);
});

You would use this similarly to the normal resource route declaration:

Route::resourceAndActive('users', 'UserController');
*/

// Playground routes
Route::prefix('playground')->group(function () {
    Route::get('/', [PlaygroundController::class, 'index'])->name('playground.index');
    Route::get('verify-progress/{user_id?}/{course_id?}', [PlaygroundController::class, 'verifyProgress'])->name('playground.verify-progress');
    Route::get('verify-progress/{user_id}/{course_id}/details', [PlaygroundController::class, 'getProgressDetails'])->name('playground.verify-progress.details');
    Route::get('verify-progress/query', [PlaygroundController::class, 'queryProgress'])->name('playground.verify-progress.query');

    // Progress comparison routes
    Route::get('compare-progress/{user_id?}/{course_id?}', [PlaygroundController::class, 'compareProgress'])->name('playground.compare-progress');
    Route::get('compare-progress/{user_id}/{course_id}/details', [PlaygroundController::class, 'getComparisonDetails'])->name('playground.compare-progress.details');

    // Test getTotalCounts method
    Route::get('test-get-total-counts', [PlaygroundController::class, 'testGetTotalCounts'])->name('playground.test-get-total-counts');

    // Test StudentTrainingPlanService getTotalCounts method
    Route::get('test-student-training-plan-total-counts', [PlaygroundController::class, 'testStudentTrainingPlanTotalCounts'])->name('playground.test-student-training-plan-total-counts');
});
