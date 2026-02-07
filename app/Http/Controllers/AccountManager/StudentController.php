<?php

namespace App\Http\Controllers\AccountManager;

use App\DataTables\AccountManager\StudentActivityDataTable;
use App\DataTables\AccountManager\StudentDataTable;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\EnrolmentCollection;
use App\Http\Resources\EnrolmentResource;
use App\Http\Resources\StudentActivityCollection;
use App\Models\AdminReport;
use App\Models\Company;
use App\Models\Competency;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Document;
use App\Models\Enrolment;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentActivity;
use App\Models\StudentCourseEnrolment;
use App\Models\StudentCourseStats;
use App\Models\StudentLMSAttachables;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserDetail;
use App\Mail\DuplicateEmailNotification;
use App\Mail\DuplicateEmailLeaderNotification;
use App\Mail\DuplicateCourseNotification;
use App\Mail\DuplicateCourseLeaderNotification;
use App\Notifications\AnacondaAccountNotification;
use App\Notifications\AnacondaCourseNotification;
use App\Notifications\AssessmentReturned;
use App\Notifications\NewAccountNotification;
use App\Notifications\ResendPasswordEmailNotification;
use App\Notifications\StudentAssignedCourse;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\InitialPasswordGenerationService;
use App\Services\StudentActivityService;
use App\Services\StudentCourseService;
use App\Services\StudentTrainingPlanService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class StudentController extends Controller
{
    public StudentActivityService $activityService;
    public InitialPasswordGenerationService $passwordService;

    protected array $employment_service;

    protected array $schedule;

    public function __construct(
        StudentActivityService $activityService,
        InitialPasswordGenerationService $passwordService
    ) {
        $this->employment_service = [
            'Workforce Australia',
            'Inclusive Employment Australia (IEA)',
            'Transition to Work (TTW)',
            'Parent Pathways',
            'Other',
        ];
        $this->schedule = [
            '25 Hours',
            '15 Hours',
            '8 Hours',
            'No Time Limit',
            'Not Applicable',
        ];
        $this->activityService = $activityService;
        $this->passwordService = $passwordService;
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index(StudentDataTable $dataTable, Request $request)
    {
        //        ddd(auth()->user());
        $this->authorize('view students');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            [
                'link' => route('account_manager.students.index'),
                'name' => 'Students',
            ],
        ];

        $actionItems = [];
        if (
            auth()
                ->user()
                ->can('create students')
        ) {
            $actionItems = [
                0 => [
                    'link' => route('account_manager.students.create'),
                    'icon' => 'plus-square',
                    'title' => 'Add New Student',
                ],
            ];
        }
        $requestParams = [
            'for' => $request->for,
            'status' => $request->status,
            'show_all' => $request->show_all,
            'registration_date' => $request->registration_date,
        ];

        // Get data for modal if it exists
        $courses = Course::accessible()
            ->notRestricted()
            ->orderBy('category', 'asc')
            ->get();

        if (
            auth()
                ->user()
                ->can('published courses status')
        ) {
            $courses = Course::published()
                ->notRestricted()
                ->orderBy('category', 'asc')
                ->get();
        }

        $companies = auth()
            ->user()
            ->isLeader()
            ? auth()->user()->companies
            : Company::all();

        // Get existing students filtered by current user's companies
        $all_students = User::onlyStudents()
            ->when(auth()->user()->isLeader(), function ($query) {
                return $query->isRelatedCompany();
            })
            ->with('detail')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return $dataTable
            ->with($requestParams)
            ->render('content.account-manager.students.index', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create students');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            [
                'link' => route('account_manager.students.index'),
                'name' => 'Students',
            ],
            ['name' => 'Add New Student'],
        ];
        $courses = Course::accessible()
            ->notRestricted()
            ->orderBy('category', 'asc')
            ->get();

        if (
            auth()
                ->user()
                ->can('published courses status')
        ) {
            $courses = Course::published()
                ->notRestricted()
                ->orderBy('category', 'asc')
                ->get();
        }

        $companies = auth()
            ->user()
            ->isLeader()
            ? auth()->user()->companies
            : Company::all();

        // Get existing students filtered by current user's companies
        $all_students = User::onlyStudents()
            ->when(auth()->user()->isLeader(), function ($query) {
                return $query->isRelatedCompany();
            })
            ->with('detail')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view()
            ->make('content.account-manager.students.add-edit')
            ->with([
                'courses' => $courses,
                'trainers' => User::onlyTrainers()->get(),
                'leaders' => User::onlyLeaders()->get(),
                'companies' => $companies,
                'all_students' => $all_students,
                'employment_service' => $this->employment_service,
                'schedule' => $this->schedule,
                'action' => [
                    'url' => route('account_manager.students.store'),
                    'name' => 'Create',
                ],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create students');

        // Define $isExistingStudent before using it in validation
        $isExistingStudent = !empty($request->existing_student);

        $validated = $request->validate(
            [
                'existing_student' => ['nullable', 'exists:users,id'],
                'first_name' => [
                    $isExistingStudent ? 'nullable' : 'required',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s]+/',
                ],
                'last_name' => [
                    $isExistingStudent ? 'nullable' : 'required',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s]+/',
                ],
                'preferred_name' => ['nullable', 'max:255'],
                'email' => [
                    $isExistingStudent ? 'nullable' : 'required',
                ],
                'purchase_order' => 'required|string|max:255',
                'phone' => [
                    $isExistingStudent ? 'nullable' : 'required',
                    'regex:/^[\+0-9]+/',
                ],
                'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z\.]+/'],
                //            'country' => 'required|numeric|exists:countries,id',
                'language' => [
                    'required',
                    Rule::in(array_keys(config('constants.language'))),
                ],
                'preferred_language' => ['nullable', 'string'],
                // 'password' => 'required|confirmed|min:6',
                'trainers' => ['nullable', 'exists:users,id'],
                'company' =>
                    'required|required_with:leaders|exists:companies,id',
                'leaders' => [
                    'sometimes',
                    'exists:users,id',
                    Rule::requiredIf(
                        auth()
                            ->user()
                            ->isAdmin()
                    ),
                ],
                'schedule' => ['required', Rule::in($this->schedule)],
                'employment_service' => [
                    'required',
                    Rule::in($this->employment_service),
                ],
                'study_type' => [
                    'nullable',
                    Rule::in(config('constants.study_type')),
                ],
                'course' => 'required|exists:courses,id',
                'course_start_at' => [
                    'required_with:course|date',
                    Rule::requiredIf(
                        auth()
                            ->user()
                            ->isAdmin()
                    ),
                ],
                'course_ends_at' => [
                    'required_with:course|date',
                    Rule::requiredIf(
                        auth()
                            ->user()
                            ->isAdmin()
                    ),
                ],
                'allowed_to_next_course' => [
                    'sometimes', /* , Rule::requiredIf(!!auth()->user()->can('allow semester only')) */
                ],
                'is_chargeable' => [
                    'sometimes', /* , Rule::requiredIf(!!auth()->user()->can('allow semester only')) */
                ],
            ],
            [
                'phone.regex' => 'A valid phone is required',
                'address.regex' => 'A valid address is required',
            ]
        );

        $isNewAccount = false;
        $wasReactivated = false;

        if ($isExistingStudent) {
            // Load existing student
            $student = User::findOrFail($request->existing_student);

            // Verify access for leaders
            if (auth()->user()->isLeader()) {
                $userCompanies = auth()->user()->companies->pluck('id');
                $studentCompanies = $student->companies->pluck('id');
                if (!$userCompanies->intersect($studentCompanies)->count()) {
                    abort(403, 'You do not have access to enroll this student.');
                }
            }

            // Reactivate student if inactive
            if (!$student->isActive()) {
                $wasReactivated = true;
                $student->is_active = true;
                $student->save();

                $student->detail->status = 'ACTIVE';
                $student->detail->save();

                $causer = auth()->user();
                $causer_role = $causer->roles()->first();
                activity('user_status')
                    ->event('ACTIVATED')
                    ->causedBy($causer)
                    ->performedOn($student)
                    ->withProperties([
                        'role' => 'Student',
                        'user_id' => $student->id,
                        'by' => [
                            'role' => [
                                'id' => $causer_role->id,
                                'name' => $causer_role->name,
                            ],
                            'id' => $causer->id,
                        ],
                        'reason' => 'Course enrollment',
                    ])
                    ->log('Student reactivated due to course enrollment, status is ACTIVE now');

                // Add note to student profile
                $course = Course::find($request->course);
                $courseName = $course ? $course->title : 'Unknown Course';

                // Get leader name
                $leaderName = 'Unknown Leader';
                if ($causer->isLeader()) {
                    $leaderName = $causer->name;
                } else {
                    $leader = $student->leaders()->first();
                    if ($leader) {
                        $leaderName = $leader->name;
                    }
                }

                $noteBody = "{$student->name} was added to a course ({$courseName}) by {$leaderName} and reactivated";
                $note = Note::create([
                    'user_id' => 0,
                    'subject_type' => User::class,
                    'subject_id' => $student->id,
                    'note_body' => $noteBody,
                    'is_pinned' => false,
                ]);

                $this->activityService->setActivity(
                    [
                        'user_id' => $student->id,
                        'activity_event' => 'NOTE ADDED',
                        'activity_details' => [
                            'student' => $student->id,
                            'by' => [
                                'id' => $causer->id,
                                'role' => $causer->roleName(),
                            ],
                            'is_pinned' => $note->is_pinned,
                        ],
                    ],
                    $note
                );
            }

            // Update purchase order and other allowed fields
            $student->detail->update([
                'purchase_order' => $request->purchase_order,
            ]);

            // Update or create basic enrolment data
            $enrolments = $student->enrolments()->where('enrolment_key', '=', 'basic')->first();
            if (!empty($enrolments)) {
                $enrolments->enrolment_value = collect([
                    'schedule' => $request->schedule,
                    'employment_service' => $request->employment_service,
                ]);
                $enrolments->save();
            } else {
                $student->enrolments()->save(
                    new Enrolment([
                        'enrolment_key' => 'basic',
                        'enrolment_value' => collect([
                            'schedule' => $request->schedule,
                            'employment_service' => $request->employment_service,
                        ]),
                    ])
                );
            }

            // Refresh student to load latest relationships
            $student->refresh();

            // If adding a new course to an existing student and the logged in user is a leader,
            // update the student's leader to the currently logged in leader if different
            if (auth()->user()->isLeader()) {
                $currentLeaderIds = $student->leaders->pluck('id')->toArray();
                $loggedInLeaderId = auth()->user()->id;

                // If the student has a different leader, update to the currently logged in leader
                if (!in_array($loggedInLeaderId, $currentLeaderIds)) {
                    $student->leaders()->sync($loggedInLeaderId);
                    $student->refresh();
                }
            }

            // Update company/site if changed
            if (!empty($request->company)) {
                $student->companies()->sync($request->company);
                $student->refresh();
            }
        } else {
            // Create new student
            $isNewAccount = true;

            // Check if email already exists
            if (User::where('email', $request->email)->exists()) {
                // Email already exists - send notification to admin and return without creating user
                $existingUser = User::where('email', $request->email)->first();
                $newCourse = Course::find($request->course);

                // Prepare registration data for the notification
                $registrationData = [
                    'first_name' => $request->first_name,
                    'preferred_name' => $request->preferred_name ?? null,
                    'last_name' => $request->last_name,
                    'preferred_language' => $request->preferred_language ?? null,
                    'phone' => $request->phone,
                    'purchase_order' => $request->purchase_order,
                    'schedule' => $request->schedule,
                    'employment_service' => $request->employment_service,
                    'allowed_to_next_course' => $request->allowed_to_next_course ?? null,
                ];

                // Add company name if provided
                if ($request->company) {
                    $company = \App\Models\Company::find($request->company);
                    $registrationData['company'] = $company ? $company->name : 'Unknown Company';
                }

                // Add leader name if provided
                if ($request->leaders) {
                    $leader = User::find($request->leaders);
                    $registrationData['leader'] = $leader ? $leader->name . ' (' . $leader->email . ')' : 'Unknown Leader';
                }

                // Add trainer name if provided
                if ($request->trainers) {
                    $trainer = User::find($request->trainers);
                    $registrationData['trainer'] = $trainer ? $trainer->name . ' (' . $trainer->email . ')' : 'Unknown Trainer';
                }

                // Send duplicate email notification to admin
                try {
                    \Mail::to('admin@keycompany.com.au')->send(
                        new DuplicateEmailNotification(
                            $request->email,
                            $request->first_name . ' ' . $request->last_name,
                            $newCourse,
                            auth()->user(),
                            $existingUser,
                            $registrationData
                        )
                    );
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    \Log::error('Email transport failed - duplicate email notification to admin: ' . $e->getMessage(), [
                        'email' => $request->email,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.index')
                        ->with('error', 'Failed to send notification email to admin. Please check the email server.')
                        ->with('warning', 'Email Server Error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error('Unexpected error sending duplicate email notification: ' . $e->getMessage(), [
                        'email' => $request->email,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.index')
                        ->with('error', 'Failed to send notification email. Please check the email server.')
                        ->with('warning', 'Email Error: ' . $e->getMessage());
                }

                // Send receipt to leader (without existing account details)
                $leaderId = auth()->user()->isLeader() ? auth()->user()->id : $request->leaders;
                if ($leaderId) {
                    $leaderUser = User::find($leaderId);
                    if ($leaderUser) {
                        try {
                            \Mail::to($leaderUser->email)->send(
                                new DuplicateEmailLeaderNotification(
                                    $request->email,
                                    $request->first_name . ' ' . $request->last_name,
                                    $newCourse,
                                    auth()->user(),
                                    $registrationData
                                )
                            );
                        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                            \Log::error('Email transport failed - leader notification: ' . $e->getMessage(), [
                                'leader_email' => $leaderUser->email,
                                'exception' => $e
                            ]);

                            return redirect()
                                ->route('account_manager.students.index')
                                ->with('error', 'Failed to send notification email to leader. Please check with Admin')
                                ->with('warning', 'Email Server Error: ' . $e->getMessage());
                        } catch (\Exception $e) {
                            \Log::error('Unexpected error sending leader notification: ' . $e->getMessage(), [
                                'leader_email' => $leaderUser->email,
                                'exception' => $e
                            ]);

                            return redirect()
                                ->route('account_manager.students.index')
                                ->with('error', 'Failed to send notification email to leader. Please check the email server.')
                                ->with('warning', 'Email Error: ' . $e->getMessage());
                        }
                    }
                }

                // Return with appropriate message - using toastr for persistence
                return redirect()
                    ->route('account_manager.students.index')
                    ->with('toastr_info', "A student registration request for {$request->first_name} {$request->last_name} ({$request->email}) has been sent to the admin team for review.")
                    ->with('toastr_timeout', 10000);
            }

            // Generate initial password using the password service
            $request->password = $this->passwordService->generateInitialPassword();

            $student = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->first_name . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'study_type' => $request->study_type,
            ]);

            $student->detail()->save(
                new UserDetail([
                    'purchase_order' => $request->purchase_order,
                    'phone' => $request->phone ?? '',
                    'address' => $request->address ?? '',
                    //            'country_id' => $request->country,
                    'language' => $request->language,
                    'preferred_language' => $request->preferred_language,
                    'registered_by' => auth()->user()->id,
                    'timezone' => 'Australia/Melbourne',
                    'preferred_name' => $request->preferred_name,
                ])
            );
            $student->assignRole('Student');

            $student->enrolments()->save(
                new Enrolment([
                    'enrolment_key' => 'basic',
                    'enrolment_value' => collect([
                        'schedule' => $request->schedule,
                        'employment_service' => $request->employment_service,
                    ]),
                ])
            );

            if (!empty($validated['trainers'])) {
                $student->trainers()->sync($request->trainers);
            }

            $student->leaders()->sync(
                auth()
                    ->user()
                    ->isLeader()
                ? auth()->user()->id
                : $request->leaders
            );
            $student->companies()->sync($request->company);
        }

        //        $adminReportService = new AdminReportService(auth()->user()->id, null);
        //        $adminReportService->save($adminReportService->prepareData($student));
        $this->updateLeaderFirstRecord($student);

        $courseAssignmentResult = $this->assign_course_on_create($request, $student);

        // If course assignment returned null, it means duplicate course was detected
        if ($courseAssignmentResult === null) {
            $newCourse = Course::find($request->course);
            return redirect()
                ->route('account_manager.students.index')
                ->with('toastr_info', "A course assignment request for {$student->name} ({$student->email}) to course {$newCourse->title} has been sent to the admin team for review.")
                ->with('toastr_timeout', 10000);
        }

        // Add note when course is added to existing active student (not reactivated)
        if ($isExistingStudent && !$wasReactivated) {
            $course = Course::find($request->course);
            $courseName = $course ? $course->title : 'Unknown Course';

            // Get leader name
            $causer = auth()->user();
            $leaderName = 'Unknown Leader';
            if ($causer->isLeader()) {
                $leaderName = $causer->name;
            } else {
                $leader = $student->leaders()->first();
                if ($leader) {
                    $leaderName = $leader->name;
                }
            }

            $noteBody = "{$student->name} was added to a course ({$courseName}) by {$leaderName}";
            $note = Note::create([
                'user_id' => $causer->id,
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'note_body' => $noteBody,
                'is_pinned' => false,
            ]);

            $this->activityService->setActivity(
                [
                    'user_id' => $student->id,
                    'activity_event' => 'NOTE ADDED',
                    'activity_details' => [
                        'student' => $student->id,
                        'by' => [
                            'id' => $causer->id,
                            'role' => $causer->roleName(),
                        ],
                        'is_pinned' => $note->is_pinned,
                    ],
                ],
                $note
            );
        }

        $newCourse = Course::find($request->course);
        //        dd($newCourse, $request->all());

        // Only send welcome email and fire Registered event for new accounts
        if ($isNewAccount) {
            try {
                if (
                    !empty($newCourse) &&
                    \Str::lower($newCourse->category) === 'anaconda'
                ) {
                    $student->notify(
                        new AnacondaAccountNotification('Student', $request->password)
                    );
                } else {
                    $student->notify(
                        new NewAccountNotification('Student', $request->password)
                    );
                }

                event(new Registered($student));

                return redirect()
                    ->route('account_manager.students.show', $student)
                    ->with('success', 'Student created successfully.')
                    ->with('info', 'Email with details sent at: ' . $request->email);
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                \Log::error('Email transport failed - new student welcome email: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'email' => $student->email,
                    'exception' => $e
                ]);

                return redirect()
                    ->route('account_manager.students.show', $student)
                    ->with('error', 'Student created successfully, but failed to send welcome email. Please check the email server configuration (SMTP settings, credentials, or server status).')
                    ->with('warning', 'Email Server Error: ' . $e->getMessage());
            } catch (\Exception $e) {
                \Log::error('Unexpected error sending new student welcome email: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'email' => $student->email,
                    'exception' => $e
                ]);

                return redirect()
                    ->route('account_manager.students.show', $student)
                    ->with('error', 'Student created successfully, but failed to send welcome email. Please check the email server.')
                    ->with('warning', 'Email Error: ' . $e->getMessage());
            }
        } else {
            // For existing students, send single notification to leader about new course enrollment
            $leader = (!empty($student->leaders()) && $student->leaders()->count() > 0)
                ? $student->leaders()->first()->user
                : null;

            if ($leader && $newCourse) {
                // Refresh student to ensure latest course enrollments are loaded
                $student->refresh();

                // Only show the course that was just added
                $selectedCourseIds = [$newCourse->id];

                try {
                    if (\Str::lower($newCourse->category) === 'anaconda') {
                        $leader->notify(new AnacondaCourseNotification($student, $selectedCourseIds));
                    } else {
                        $leader->notify(new StudentAssignedCourse($student, $selectedCourseIds));
                    }

                    \Log::info('Student# ' . $student->id . ' ' . $student->name .
                        ' enrolled in additional course ' . $newCourse->name .
                        ' - Leader ' . $leader->id . ' notified');
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    \Log::error('Email transport failed - leader course notification: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                        'leader_id' => $leader->id,
                        'course_id' => $newCourse->id,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.show', $student)
                        ->with('warning', 'Student enrolled successfully, but failed to send notification email to leader. Please check the email server configuration (SMTP settings, credentials, or server status).')
                        ->with('info', 'Email Server Error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error('Unexpected error sending leader course notification: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                        'leader_id' => $leader->id,
                        'course_id' => $newCourse->id,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.show', $student)
                        ->with('warning', 'Student enrolled successfully, but failed to send notification email to leader. Please check the email server.')
                        ->with('info', 'Email Error: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('account_manager.students.show', $student)
                ->with('success', 'Student course enrollment updated successfully.')
                ->with('info', 'Leader notified at: ' . ($leader ? $leader->email : 'N/A'));
        }
    }

    private function updateLeaderFirstRecord($student)
    {
        $leaders = $student->leaders->load('detail');
        if (empty($leaders)) {
            return false;
        }
        foreach ($leaders as $leader) {
            $leaderDetail = $leader->detail;
            $leaderId = $leader->id;

            $setFirstEnrolment = false;
            $setFirstLogin = false;
            if (empty($leader)) {
                return false;
            }

            if (empty($leaderDetail->last_logged_in)) {
                continue;
            }
            if (empty($leaderDetail->first_enrollment)) {
                $firstEnrolment = StudentCourseEnrolment::whereHas(
                    'student',
                    function ($query) use ($leaderId) {
                        $query
                            ->whereHas('leaders', function ($query) use ($leaderId) {
                                $query
                                    ->where(
                                        'attachable_type',
                                        'App\Models\Leader'
                                    )
                                    ->where('attachable_id', $leaderId);
                            })
                            ->where('userable_type', null); // Filter for Students
                    }
                )
                    ->orderBy('student_course_enrolments.created_at')
                    ->first();

                $leaderDetail->first_enrollment = !empty($firstEnrolment)
                    ? $firstEnrolment->getRawOriginal('created_at')
                    : Carbon::today()->toDateTimeString();
                $setFirstEnrolment = true;
            }

            $first_login = $leaderDetail->first_login ?? '';

            if (
                empty($first_login) ||
                Carbon::parse($first_login)->greaterThanOrEqualTo(
                    Carbon::parse($leader->created_at)
                )
            ) {
                $activity = Activity::where('causer_id', $leader->id)
                    ->where('event', 'AUTH')
                    ->where('log_name', 'audit')
                    ->where('description', 'SIGN IN')
                    ->first();
                $first_logged_in = !empty($activity)
                    ? $activity->getRawOriginal('created_at')
                    : '';

                //                if ( empty( $activity ) || Carbon::parse( $first_logged_in )->equalTo( Carbon::parse( $leaderDetail->getRawOriginal( 'last_logged_in' ) ) ) ) {
                //                    $first_logged_in = $leader->getRawOriginal( 'created_at' );
                //                }

                $leaderDetail->first_login = $first_logged_in;
                $setFirstLogin = true;
            }

            if ($setFirstEnrolment || $setFirstLogin) {
                $leaderDetail->save();
            }
        }

        return true;
    }

    public function assign_course_on_create(Request $request, User $student)
    {
        $course = Course::find($request->course);

        // Check if the course is a semester 2 course
        $isSemester2Course = $course && stripos(Str::lower($course->title), 'semester 2') !== false;

        // Check if re-enrollment and agreement needs renewal (>1 year old)
        // Do NOT trigger re-enrollment if adding a semester 2 version of a course
        if (!$isSemester2Course) {
            $existingEnrolments = StudentCourseEnrolment::where('user_id', $student->id)
                ->where('status', '!=', 'DELIST')
                ->exists();

            if ($existingEnrolments) {
                $onboardEnrolment = Enrolment::where('user_id', $student->id)
                    ->where(function($query) {
                        $query->where('enrolment_key', 'onboard')
                              ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                    })
                    ->where('is_active', true)
                    ->first();

                if ($onboardEnrolment && $onboardEnrolment->enrolment_value) {
                    $enrolmentValue = $onboardEnrolment->enrolment_value->toArray();
                    $signedOn = $enrolmentValue['step-6']['signed_on'] ?? $enrolmentValue['step-5']['signed_on'] ?? null;

                    // Note: Adding a course should not clone/create enrollment records
                    // Re-enrollment should only happen when student goes through the enrollment process
                    if ($signedOn) {
                        $signedDate = is_array($signedOn) ? ($signedOn['key'] ?? $signedOn['value'] ?? $signedOn) : $signedOn;
                        // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                        $signedCarbon = is_numeric($signedDate)
                            ? Carbon::createFromTimestamp($signedDate)
                            : Carbon::parse($signedDate)->timezone(Helper::getTimeZone());

                        // If agreement is older than 1 year, trigger re-enrolment process
                        // The original enrolment should stay active until the student completes the new re-enrolment
                        if ($signedCarbon->diffInYears(Carbon::now()) >= 1) {
                            // Preserve first enrollment date before clearing onboard_at
                            if (empty($student->detail->first_enrollment) && !empty($student->detail->onboard_at)) {
                                $student->detail->first_enrollment = $student->detail->onboard_at;
                            }

                            // Clear onboard_at to force student back to enrollment screen on next login
                            // The EnrolmentController will handle creating the new enrolment and deactivating
                            // the old one when the student completes step 6 of the re-enrolment process
                            $student->detail->onboard_at = null;
                            $student->detail->save();

                            // Note: Re-enrollment note will be created when student completes the onboarding process
                            // The old enrolment will be deactivated and new one activated in EnrolmentController when step 6 is completed
                        }
                    }
                }
            }
        }
        $course_start_at = auth()
            ->user()
            ->isAdmin()
            ? Carbon::parse($request->course_start_at)->format('Y-m-d')
            : Carbon::today(Helper::getTimeZone())->format('Y-m-d');
        $course_ends_at = auth()
            ->user()
            ->isAdmin()
            ? Carbon::parse($request->course_ends_at)->format('Y-m-d')
            : Carbon::today(Helper::getTimeZone())
                ->addDays($course->course_length_days)
                ->format('Y-m-d');
        $timeNow = '00:00:00';

        // Check if the course title contains "Semester 2"
        $isSemester2 =
            stripos(Str::lower($course->title), 'semester 2') !== false;
        $isMainCourse = !$isSemester2;

        // Check if this specific course enrollment already exists (in any capacity/status)
        $existingCourseEnrolment = StudentCourseEnrolment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        // If existing student is selected and course already exists, send notifications and don't add course
        if (!empty($request->existing_student) && $existingCourseEnrolment) {
            // Prepare registration data for notifications
            $registrationData = [
                'purchase_order' => $request->purchase_order ?? $student->detail->purchase_order ?? null,
                'schedule' => $request->schedule ?? null,
                'employment_service' => $request->employment_service ?? null,
                'allowed_to_next_course' => $request->allowed_to_next_course ?? null,
            ];

            // Add company name if available
            if ($student->companies()->count() > 0) {
                $company = $student->companies()->first();
                $registrationData['company'] = $company ? $company->name : null;
            }

            // Send duplicate course notification to admin
            try {
                \Mail::to('admin@keycompany.com.au')->send(
                    new DuplicateCourseNotification(
                        $student,
                        $course,
                        auth()->user(),
                        $registrationData
                    )
                );
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                \Log::error('Email transport failed - duplicate course notification to admin: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'exception' => $e
                ]);

                return redirect()
                    ->route('account_manager.students.index')
                    ->with('error', 'Failed to send duplicate course notification to admin. Please check the email server configuration (SMTP settings, credentials, or server status).')
                    ->with('warning', 'Email Server Error: ' . $e->getMessage());
            } catch (\Exception $e) {
                \Log::error('Unexpected error sending duplicate course notification: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'exception' => $e
                ]);

                return redirect()
                    ->route('account_manager.students.index')
                    ->with('error', 'Failed to send notification email to admin. Please check the email server.')
                    ->with('warning', 'Email Error: ' . $e->getMessage());
            }

            // Send receipt to leader (only with course information)
            $leader = (!empty($student->leaders()) && $student->leaders()->count() > 0)
                ? $student->leaders()->first()->user
                : null;

            if ($leader) {
                try {
                    \Mail::to($leader->email)->send(
                        new DuplicateCourseLeaderNotification(
                            $student,
                            $course,
                            auth()->user(),
                            $registrationData
                        )
                    );
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    \Log::error('Email transport failed - duplicate course leader notification: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'leader_id' => $leader->id,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.index')
                        ->with('error', 'Failed to send notification to leader. Please check the email server configuration (SMTP settings, credentials, or server status).')
                        ->with('warning', 'Email Server Error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    \Log::error('Unexpected error sending duplicate course leader notification: ' . $e->getMessage(), [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'leader_id' => $leader->id,
                        'exception' => $e
                    ]);

                    return redirect()
                        ->route('account_manager.students.index')
                        ->with('error', 'Failed to send notification to leader. Please check the email server.')
                        ->with('warning', 'Email Error: ' . $e->getMessage());
                }
            }

            // Return null to indicate course was not added due to duplicate
            return null;
        }

        // If adding NEW course to existing student (via existing_student dropdown), set chargeable to 1
        if ($existingCourseEnrolment) {
            // Course enrollment exists - preserve existing is_chargeable
            $is_chargeable = $existingCourseEnrolment->is_chargeable;
        } else {
            // New course enrollment - set chargeable to 1 if adding to existing student
            $is_chargeable = !empty($request->existing_student) ? 1 : 0;
        }

        // Determine if this is a re-enrollment (leader adding course to existing student)
        // Do NOT treat as re-enrollment if adding a semester 2 version of a course
        $isReEnrollment = !empty($request->existing_student) && !$isSemester2;

        $data = [
            'user_id' => $student->id,
            'course_id' => intval($course->id),
            'allowed_to_next_course' => isset($request->allowed_to_next_course)
                ? 0
                : 1, // Only Semester 1 = 0
            'course_start_at' => $course_start_at . ' ' . $timeNow,
            'course_ends_at' => $course_ends_at . ' ' . $timeNow,
            'status' => 'ENROLLED',
            'version' => $course->version,
            'is_chargeable' => $is_chargeable,
            'registered_by' => auth()->user()->id,
            'registered_on_create' => $isReEnrollment ? 0 : 1, // 0 for re-enrollments (leader adds), 1 for new student self-registration
            'is_semester_2' => $isSemester2,
            'is_main_course' => $isMainCourse,
        ];

        // Check if this is the first course on the account
        // Accounts are always created with a course, so if there are no existing enrollments,
        // this is the first course and we should not set registration_date
        $existingEnrollmentsCount = StudentCourseEnrolment::where('user_id', $student->id)
            ->where('course_id', '!=', $course->id)
            ->count();
        $isFirstCourse = $existingEnrollmentsCount === 0;

        // Set registration date when adding a new course to an existing account, regardless of chargeable status
        if (!$isFirstCourse) {
            $data['registration_date'] = Carbon::today(Helper::getTimeZone())->format(
                'Y-m-d'
            );
            $data['show_registration_date'] = 1;
        } else {
            // first course - do not set registration_date
            $data['registration_date'] = null;
            $data['show_registration_date'] = 0;
        }

        $course_duration = Carbon::parse($course_ends_at)->diffInDays(
            Carbon::parse($course_start_at)
        );
        $data['course_expiry'] = Carbon::parse($course_start_at)->addDays(
            $course_duration
        );

        $record1 = StudentCourseEnrolment::updateOrCreate(
            ['user_id' => $student->id, 'course_id' => $data['course_id']],
            $data
        );
        $student->detail()->update(['status' => 'ENROLLED']);

        CourseProgressService::initProgressSession(
            $student->id,
            $course->id,
            $record1
        );
        CourseProgressService::updateStudentCourseStats(
            $record1,
            $isMainCourse
        );

        $adminReportService = new AdminReportService($student->id, $course->id);
        $adminReportService->update(
            $adminReportService->prepareData($student, $course),
            $record1
        );
        // REGISTER 2ND COURSE AS SEMESTER 2
        $record2 = [];
        if ($data['allowed_to_next_course'] === 1) {
            $record2 = $this->enrolNextCourse(
                $course,
                $course_ends_at,
                $student,
                $is_chargeable,
                true,
                $record1  // Pass semester 1 enrollment to inherit dates
            );
        }

        return [$record1, $record2];
    }

    public function enrolNextCourse(
        $course,
        $first_course_end_date,
        User $student,
        $is_chargeable = 0,
        $registered_on_create = 0,
        $semester1Enrollment = null
    ) {
        $next_course = Course::find(intval($course->next_course));
        if (empty($next_course)) {
            return false;
        }
        $timeNow = Carbon::now()->format('H:i:s');
        $next_course_start_date = Carbon::parse(
            filter_var($first_course_end_date, FILTER_SANITIZE_NUMBER_INT) .
            ' ' .
            $timeNow
        )->addDays(intval($course->next_course_after_days));
        //        dd($first_course_end_date, $next_course_start_date);
        $next_course_end_date = $next_course_start_date
            ->clone()
            ->addDays($next_course->course_length_days);
        Log::info([
            filter_var($first_course_end_date, FILTER_SANITIZE_NUMBER_INT),
            Carbon::parse(
                filter_var($first_course_end_date, FILTER_SANITIZE_NUMBER_INT) .
                ' ' .
                $timeNow
            )->toDateTime(),
            $next_course_start_date->toDateTime(),
            $next_course->course_length_days,
            $next_course_end_date->toDateTime(),
        ]);
        $next_course_data = [
            'user_id' => $student->id,
            'course_id' => intval($next_course->id),
            'allowed_to_next_course' => 0,
            'course_start_at' => $next_course_start_date->toDateTime(),
            'course_ends_at' => $next_course_end_date->toDateTime(),
            'status' => 'ENROLLED',
            'is_semester_2' => 1,
            'is_main_course' => 0,
            'is_chargeable' => $is_chargeable,
            'registered_by' => auth()->user()->id,
            'registered_on_create' => $registered_on_create,
        ];

        $course_duration = Carbon::parse($next_course_end_date)->diffInDays(
            Carbon::parse($next_course_start_date)
        );
        $next_course_data['course_expiry'] = Carbon::parse(
            $next_course_start_date
        )->addDays($course_duration);

        $enrolledRecord = StudentCourseEnrolment::where(
            'user_id',
            intval($student->id)
        )
            ->where('course_id', intval($next_course->id))
            ->first();

        //        Helper::debug([$enrolledRecord, $next_course_data, $is_chargeable],'dd');
        if (!empty($enrolledRecord)) {
            $this->setRegistrationData(
                $enrolledRecord,
                $next_course_data,
                $is_chargeable
            );
            $next_course_data[
                'registered_on_create'
            ] = $enrolledRecord->getRawOriginal('registered_on_create');
        } else {
            // New semester 2 course - inherit dates from semester 1 if available
            if (!empty($semester1Enrollment)) {
                // Inherit created_at and registration details from semester 1
                $next_course_data['created_at'] = $semester1Enrollment->getRawOriginal('created_at');
                $next_course_data['registration_date'] = $semester1Enrollment->getRawOriginal('registration_date');
                $next_course_data['registered_by'] = $semester1Enrollment->getRawOriginal('registered_by');
                $next_course_data['show_registration_date'] = $semester1Enrollment->getRawOriginal('show_registration_date');
                $next_course_data['show_on_widget'] = $semester1Enrollment->getRawOriginal('show_on_widget');
            } else {
                // Fallback: check if this is part of a re-enrollment
                // If the main course has registered_on_create = 0, this is a re-enrollment
                $isReEnrollment = !empty($enrolledRecord) &&
                    $enrolledRecord->getRawOriginal('registered_on_create') == 0;

                if ($isReEnrollment) {
                    // Re-enrollment: set registration_date if chargeable
                    if ($is_chargeable === 1) {
                        $next_course_data['registration_date'] = Carbon::today(
                            Helper::getTimeZone()
                        )->format('Y-m-d');
                        $next_course_data['registered_by'] = auth()->user()->id;
                        $next_course_data['show_registration_date'] = true;
                    } else {
                        $next_course_data['registration_date'] = null;
                        $next_course_data['registered_by'] = auth()->user()->id;
                        $next_course_data['show_registration_date'] = false;
                    }
                } else {
                    // Initial enrollment: do not set registration_date
                    $next_course_data['registration_date'] = null;
                    $next_course_data['registered_by'] = auth()->user()->id;
                    $next_course_data['show_registration_date'] = false;
                }
            }
        }

        $record = StudentCourseEnrolment::updateOrCreate(
            [
                'user_id' => intval($student->id),
                'course_id' => intval($next_course->id),
            ],
            $next_course_data
        );

        CourseProgressService::initProgressSession(
            $student->id,
            $next_course->id,
            $record
        );
        CourseProgressService::updateStudentCourseStats($record, false);
        // Setup Course Expiry Date
        $calculatedCourseExpiry = CourseProgressService::setupExpiryDate(
            $record
        );

        if ($is_chargeable === 1) {
            activity('course_charged')
                ->event('ENROLEMENT')
                ->causedBy(auth()->user())
                ->performedOn($record)
                ->withProperties([
                    'class' => StudentController::class,
                    'method' => 'enrolNextCourse',
                    'user_id' => $student->id,
                    'course_id' => $next_course->id,
                    'ip' => request()->ip(),
                ])
                ->log('Course is chargeable now');
        }
        // Only call AdminReportService if the enrollment was successfully created
        if ($record) {
            $adminReportService = new AdminReportService(
                $student->id,
                $next_course->id
            );
            $adminReportService->update(
                $adminReportService->prepareData($student, $next_course),
                $record
            );
        }

        return $record;
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function show(User $student, Request $request)
    {
        $debugTime = [microtime(true)];

        // Prevent accessing a leader or admin account via the student route.
        // This throws a 500 error with a clear explanation.
        if ($student->isLeader()) {
            abort(500, '500 - Attempting to access a leader account as a student');
        }

        if ($student->isAdmin()) {
            abort(500, '500 - Attempting to access an admin account as a student');
        }

        $this->authorize('view students', $student);

        $debugTime[1] = microtime(true);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            [
                'link' => route('account_manager.students.index'),
                'name' => 'Students',
            ],
            ['name' => 'View Student'],
        ];
        $actionItems = [
            0 => [
                'link' => route('account_manager.students.edit', $student),
                'icon' => 'edit',
                'title' => 'Edit Student',
            ],
            1 => [
                'link' => route('account_manager.students.create'),
                'icon' => 'plus-square',
                'title' => 'Add New Student',
            ],
        ];

        $student->load(['companies', 'detail']);

        // Retrieves and prepares the enrolments associated with the given student.
        $enrolments = $student->enrolments()->prepare();

        // Prepare action items based on permissions
        $actionItems = $this->prepareActionItems($student, $enrolments);
        //        Helper::debug( $actionItems );
        $debugTime[2] = microtime(true);

        // Optimize courses query
        $coursesQuery = auth()
            ->user()
            ->can('published courses status')
            ? Course::published()
            : Course::accessible();
        $courses = $coursesQuery->orderBy('category', 'asc')->get();

        $debugTime[3] = microtime(true);

        //        \DB::enableQueryLog();
        //        $enrolments = StudentCourseEnrolment::with('progress')
        //                                            ->where( 'user_id', $student->id )->get();
        //        $enrolment = StudentCourseEnrolment::find(19386);
        //        Helper::debug([\DB::getQueryLog(), $enrolment->course_id],'dd');

        // Fetch registered courses with optimized queries
        $registeredCourses = StudentCourseEnrolment::active()
            ->where('user_id', $student->id)
            ->with(['student', 'course', 'enrolmentStats', 'progress'])
            ->get();
        $registeredCourses->each(function ($enrolment) use ($student) {
            $save = false;

            if (empty($enrolment->course_progress_id)) {
                $save = true;
                $courseProgress = CourseProgress::where('user_id', $student->id)
                    ->where('course_id', $enrolment->course_id)
                    ->first();

                if (empty($courseProgress)) {
                    $studentTrainingPlanService = new StudentTrainingPlanService(
                        $student->id
                    );
                    $newProgress = $studentTrainingPlanService->populateProgress(
                        $enrolment->course_id
                    );
                    $courseProgress = CourseProgress::create([
                        'user_id' => $student->id,
                        'course_id' => $enrolment->course_id,
                        'percentage' => $studentTrainingPlanService->getTotalCounts(
                            $newProgress
                        ),
                        'details' => $newProgress,
                    ]);
                    \Log::warning('Course Progress not found for enrolment', [
                        'student_id' => $student->id,
                        'course_id' => $enrolment->course_id,
                        'enrolment_id' => $enrolment->id,
                        'new_progress_id' => $courseProgress?->id,
                    ]);
                }
                //                Helper::debug($courseProgress->id);
                $enrolment->course_progress_id = $courseProgress?->id;
            }

            // Check if course progress percentage is numeric or in local environment, then update StudentCourseStats
            if (!empty($enrolment->course_progress_id)) {
                $courseProgress = CourseProgress::find(
                    $enrolment->course_progress_id
                );
                if ($courseProgress) {
                    $percentageValue = $courseProgress->getRawOriginal(
                        'percentage'
                    );
                    $isNumericPercentage = is_numeric($percentageValue);
                    $isLocalEnvironment = app()->environment('local');

                    if ($isNumericPercentage || $isLocalEnvironment) {
                        $isMainCourse =
                            $enrolment->course?->is_main_course == 1 ||
                            !str_contains(
                                strtolower($enrolment->course?->title),
                                'semester 2'
                            );
                        $processEnrolmentStats = CourseProgressService::updateStudentCourseStats(
                            $enrolment,
                            $isMainCourse
                        );

                        \Log::info(
                            'Updated StudentCourseStats due to numeric percentage or local environment',
                            [
                                'student_id' => $student->id,
                                'course_id' => $enrolment->course_id,
                                'enrolment_id' => $enrolment->id,
                                'is_numeric_percentage' => $isNumericPercentage,
                                'is_local_environment' => $isLocalEnvironment,
                                'processEnrolment' => $processEnrolmentStats,
                            ]
                        );

                        $enrolment->refresh();
                    }
                }
            }
            if (empty($enrolment->admin_reports_id)) {
                $save = true;
                $adminReport = AdminReport::where('student_id', $student->id)
                    ->where('course_id', $enrolment->course_id)
                    ->first();
                if (empty($adminReport)) {
                    $adminReportService = new AdminReportService(
                        $student->id,
                        $enrolment->course_id
                    );
                    $adminReport = $adminReportService->save(
                        $adminReportService->prepareData(
                            $student,
                            $enrolment->course
                        )
                    );
                    \Log::warning('Admin Report not found for enrolment', [
                        'student_id' => $student->id,
                        'course_id' => $enrolment->course_id,
                        'enrolment_id' => $enrolment->id,
                        'new_admin_report_id' => $adminReport?->id,
                    ]);
                }
                //                Helper::debug($adminReport->id);
                $enrolment->admin_reports_id = $adminReport?->id;
            }
            if (empty($enrolment->student_course_stats_id)) {
                $save = true;
                $courseStats = StudentCourseStats::where(
                    'user_id',
                    $student->id
                )
                    ->where('course_id', $enrolment->course_id)
                    ->first();
                if (empty($courseStats)) {
                    \Log::warning('Course Stats not found for enrolment', [
                        'student_id' => $student->id,
                        'course_id' => $enrolment->course_id,
                        'enrolment_id' => $enrolment->id,
                    ]);
                }
                //                Helper::debug($courseStats->id);
                $enrolment->student_course_stats_id = $courseStats?->id;
            }
            $enrolmentStats = $enrolment->enrolmentStats;
            if ($enrolmentStats && empty($enrolmentStats->course_status)) {
                $courseStatus = CourseProgressService::getCourseStatus(
                    $enrolment
                );
                $enrolmentStats->course_status = $courseStatus;
                $enrolmentStats->save();
            }
            if ($save) {
                $enrolment->save();
                $enrolment->refresh();
            }
            //            Helper::debug($enrolment,'dd');
        });
        //        Helper::debug($registeredCourses, 'dd');
        // Iterate through enrolments
        /*$registeredCourses->each( function ( $enrolment ) use ( $student ) {
            $isMainCourse = $enrolment->course?->is_main_course || !\Str::contains( \Str::lower( $enrolment->course?->title ), 'emester 2' );

            $enrolmentStats = $enrolment->enrolmentStats;

            // Setup Course Expiry Date
            $calculatedCourseExpiry = CourseProgressService::setupExpiryDate( $enrolment );

//            Helper::debug([$enrolmentStats->course_status,$enrolment->course_completed_at]);
            if ( !empty( $enrolmentStats->course_status ) && $enrolmentStats->course_status === 'COMPLETED' && empty( $enrolment->course_completed_at ) ) {
//                Helper::debug('update course completed at');
                CourseProgressService::updateStudentCourseStats( $enrolment, $isMainCourse );
                //update course progress on admin_reports
                $adminReportService = new AdminReportService( $student->id, $enrolment->course_id );
                $adminReportService->update( $adminReportService->prepareData( $student, $enrolment->course ) );
                // Optionally refresh enrolment relationships to reflect updates
                $enrolment->refresh();
//                Helper::debug($enrolment->toArray(),'dd');
            }

//            if($student->id === 52){
//                CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
////                Helper::debug('updateStudentCourseStats', 'dd');
//            }


//            Helper::debug($enrolmentStats->course_stats['current_course_progress'],'dd');
            // Check if enrolmentStats has been updated today
            if ( !$enrolmentStats ||
                empty( $enrolmentStats->course_status ) ||
                !$enrolmentStats->updated_at ||
                $enrolmentStats->updated_at->isToday() === FALSE ||
                ( isset( $enrolmentStats->course_stats[ 'current_course_progress' ] ) && $enrolmentStats->course_stats[ 'current_course_progress' ] === 0 ) ) {
                CourseProgressService::updateStudentCourseStats( $enrolment, $isMainCourse );
                //update course progress on adminreport
                $adminReportService = new AdminReportService( $student->id, $enrolment->course_id );
                $adminReportService->update( $adminReportService->prepareData( $student, $enrolment->course ) );
                // Optionally refresh enrolment relationships to reflect updates
                $enrolment->refresh();

                //log if the course progress is different then whats on admin_report
                $student_course_progress = $adminReportService->get()?->student_course_progress;
                if ( !empty( $student_course_progress )
                    && !empty( $enrolmentStats ) && intval( $enrolmentStats->course_stats[ 'current_course_progress' ] )
                    !== intval( $student_course_progress[ 'current_course_progress' ] ) ) {
                    \Log::warning( 'Course progress mismatch', [
                        'student_id' => $student->id,
                        'course_id' => $enrolment->course_id,
                        'enrolment_stats' => $enrolmentStats->course_stats[ 'current_course_progress' ] ?? 'not found',
                        'admin_report' => $student_course_progress[ 'current_course_progress' ] ?? 'not found',
                    ] );
                }
            }

        } );*/

        //        Helper::debug( $registeredCourses, 'dd' );

        $debugTime[4] = microtime(true);

        // Check activity status for inactive students
        $activityStatus = null;
        if (!empty($student) && intval($student->is_active) === 0) {
            $activityStatus = $this->fetchActivityStatus($student);
        }
        $debugTime[5] = microtime(true);

        //        // Calculate the time differences
        //        $timeDifferences = [];
        //
        //        $previousTimestamp = NULL; // Keep track of the previous timestamp
        //        foreach ( $debugTime as $index => $timestamp ) {
        //            // Ensure the current timestamp is a float
        //            if ( !is_float( $timestamp ) ) {
        //                continue; // Skip invalid entries
        //            }
        //            if ( $previousTimestamp !== NULL ) {
        //                $timeDifferences[ $index ] = $timestamp - $previousTimestamp;
        //            }
        //            $previousTimestamp = $timestamp; // Update the previous timestamp
        //        }
        //        Helper::debug( [
        //            $debugTime, $timeDifferences,
        //        ], 'dd' );

        // Cache pinned notes count for 1 day
        //        $pinnedNotesCount = 0;
        $pinnedNotesCount = Cache::remember(
            "student_{$student->id}_pinned_notes_count",
            now()->addDays(1),
            function () use ($student) {
                return Note::where('subject_type', User::class)
                    ->where('subject_id', $student->id)
                    ->where('is_pinned', true)
                    ->count();
            }
        );

        return view()
            ->make('content.account-manager.students.show')
            ->with([
                'student' => $student,
                'registeredCourses' => $registeredCourses,
                'data' => [
                    'trainers' => $student->trainers,
                    'leaders' => $student->leaders,
                    'enrolment' => $enrolments->toArray(),
                    'company' => $student->companies()?->first(),
                    'hasPinnedNotes' => $pinnedNotesCount > 0, // Boolean flag
                    'pinnedNotesCount' => $pinnedNotesCount, // Optional: exact count
                ],
                'courses' => $courses,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
                'activity' => [
                    'by' => $activityStatus?->causer->name,
                    'on' => $activityStatus
                        ? $activityStatus->created_at
                            ->timezone(Helper::getTimeZone())
                            ->format('j F, Y')
                        : $student->updated_at,
                ],
            ]);
    }

    /**
     * Prepare action items for the student profile.
     */
    private function prepareActionItems(User $student, $enrolments = null): array
    {
        $actionItems = [
            [
                'link' => route('account_manager.students.edit', $student),
                'icon' => 'edit',
                'title' => 'Edit Student',
            ],
            [
                'link' => route('account_manager.students.create'),
                'icon' => 'plus-square',
                'title' => 'Add New Student',
            ],
        ];

        if (
            auth()
                ->user()
                ->can('manage students')
        ) {
            if ($student->isActive()) {
                $actionItems[] = [
                    'link' => route(
                        'account_manager.students.deactivate',
                        $student
                    ),
                    'icon' => 'user-x',
                    'title' => 'Deactivate',
                ];
            } else {
                $actionItems[] = [
                    'link' => route(
                        'account_manager.students.activate',
                        $student
                    ),
                    'icon' => 'user-check',
                    'title' => 'Activate',
                ];
            }

            // Check if student has onboard enrolment
            $hasOnboardEnrolment = false;
            if ($enrolments) {
                foreach ($enrolments->keys() as $key) {
                    if ($key === 'onboard' || preg_match('/^onboard\d+$/', $key)) {
                        $hasOnboardEnrolment = true;
                        break;
                    }
                }
            }

            if ($hasOnboardEnrolment) {
                $actionItems[] = [
                    'link' => route('account_manager.students.edit-enrolment', [
                        'student' => $student,
                        'step' => 1,
                    ]),
                    'icon' => 'edit',
                    'title' => 'Edit Enrolment',
                ];
            } else {
                $actionItems[] = [
                    'link' => '#',
                    'icon' => 'ban',
                    'title' => 'No Enrolment To Edit',
                    'class' => 'disabled text-grey',
                    'disabled' => true,
                ];
            }

            // Add Resend Password Email option
            $actionItems[] = [
                'link' => '#',
                'icon' => 'mail',
                'title' => 'Resend Password Email',
                'class' => 'resend-password-btn',
                'data-student-id' => $student->id,
                'data-student-email' => $student->email,
            ];

            // Add Skip LLND option for Root accounts only (only if student doesn't already have completed LLND)
            if (auth()->user()->isRoot()) {
                // Check if student already has a completed LLND attempt
                $llndQuiz = Quiz::where('is_lln', true)->first();
                $hasCompletedLLND = false;

                if ($llndQuiz) {
                    $hasCompletedLLND = QuizAttempt::where('user_id', $student->id)
                        ->where('quiz_id', $llndQuiz->id)
                        ->where('system_result', 'COMPLETED')
                        ->where('status', 'SATISFACTORY')
                        ->exists();
                }

                // Only show Skip LLND option if student doesn't have completed LLND
                // and only on the DEV instance
                if (
                    !$hasCompletedLLND
                ) {
                    $actionItems[] = [
                        'link' => '#',
                        'icon' => 'skip-forward',
                        'title' => 'Skip LLND?',
                        'data-student-id' => $student->id,
                        'data-student-name' => $student->name,
                        'class' => 'skip-llnd-btn',
                    ];
                }
            }
        }

        return $actionItems;
    }

    /**
     * Fetch the latest activity status for inactive students.
     */
    private function fetchActivityStatus(User $student)
    {
        return Activity::where('subject_id', $student->id)
            ->where('subject_type', User::class)
            ->where('event', 'DEACTIVATED')
            ->where('log_name', 'user_status')
            ->orderBy('id', 'desc')
            ->first();
    }

    protected function updateStudentActivity($student_id)
    {
        $activities = StudentActivity::with('actionable', 'course')
            ->where(function ($query) use ($student_id) {
                $query
                    ->where(
                        'activity_details',
                        'LIKE',
                        "%student\":{$student_id},%"
                    )
                    ->orWhere('user_id', $student_id);
            })
            ->get();
        $userId = $student_id;
        $total = 0;
        foreach ($activities as $activity) {
            $activity_details = json_decode(
                $activity->getRawOriginal('activity_details'),
                true
            );
            if (
                !empty($activity_details['student']) ||
                !empty($activity_details['student_id'])
            ) {
                $userId =
                    $activity_details['student'] ??
                    $activity_details['student_id'];
                $activity->user_id = $userId;
            }
            if (
                empty($activity_details['student']) &&
                empty($activity_details['student_id'])
            ) {
                $userId = $activity->user_id;
            }

            if ($activity->activity_event === 'TOPIC END') {
                $activity->time_spent = $activity_details['topic_time'];
            }
            if (
                in_array($activity->activity_event, [
                    'LESSON MARKED',
                    'LESSON END',
                    'LESSON START',
                    'TOPIC MARKED',
                    'TOPIC END',
                    'TOPIC START',
                ])
            ) {
                $activityTime =
                    $activity_details['activity_by__at'] ??
                    $activity_details['at'];
                $activity->activity_details = array_merge($activity_details, [
                    'student' => intval(
                        $activity_details['student'] ?? $userId
                    ),
                    'activity_by__at' => $activityTime,
                    'activity_by_id' => intval(
                        $activity_details['activity_by_id'] ??
                        ($activity_details['id'] ?? auth()->check())
                        ? auth()->user()?->id
                        : 0
                    ),
                    'activity_by_role' =>
                        $activity_details['activity_by_role'] ??
                        ($activity_details['by'] ?? auth()->check())
                        ? auth()->user()?->roleName()
                        : '',
                ]);
                if ($activity->activity_event === 'TOPIC END') {
                    $total += $activity->time_spent;
                    //                    dump( 'details => ' . $activity_details[ 'topic_time' ] );
                    //                    dump( 'column => ' . $activity->time_spent );
                    $lesson_id = $activity->actionable->lesson_id;
                    //                    dump( [ $activity->user_id, $activity->course_id, 'lessons.list.' . $lesson_id . '.topics.list.' . $activity->actionable_id ] );
                    if (!empty($lesson_id)) {
                        $activityTime = CourseProgressService::getActivityTime(
                            $activity->user_id,
                            $activity->course_id,
                            'lessons.list.' .
                            $lesson_id .
                            '.topics.list.' .
                            $activity->actionable_id
                        );
                    }
                    //                    dd( [
                    //                        $activity->user_id,
                    //                        $activity->id,
                    //                        $activity->activity_on,
                    //                        Carbon::parse( $activity->activity_on )->toDateString(),
                    //                        $activityTime,
                    //                        Carbon::parse( $activityTime )->toDateString(),
                    //                    ] );
                    //                    dump( Carbon::parse( $activityTime )->toDateString() );
                }
                if (empty($activityTime)) {
                    $activityTime = \Carbon\Carbon::now();
                }
                if (
                    \Carbon\Carbon::parse($activity->activity_on)->greaterThan(
                        Carbon::parse($activityTime)
                    )
                ) {
                    $activity->activity_on = Carbon::parse(
                        $activityTime
                    )->toDateString();
                }
            }

            if (!empty($activity->actionable)) {
                $actionable = $activity->actionable;
                if (!empty($actionable->course_id)) {
                    $activity->course_id = $actionable->course_id;
                } elseif (
                    method_exists($actionable, 'course') &&
                    count($actionable->course) > 0
                ) {
                    $activity->course_id = $actionable->load('course')
                        ->course?->id;
                }
            }
            $activity->save();
        }

        dd($student_id, $total);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $student)
    {
        $this->authorize('update students', $student);

        // Check if attempting to access a leader account via the student route
        if ($student->isLeader()) {
            return response()
                ->view('errors.500', [
                    'customMessage' => 'Cannot access a leader account via the student route. Please use the appropriate leader route instead.',
                ], 500);
        }
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            [
                'link' => route('account_manager.students.index'),
                'name' => 'Students',
            ],
            ['name' => 'Edit Student'],
        ];
        $actionItems = [
            0 => [
                'link' => route('account_manager.students.show', $student),
                'icon' => 'file-text',
                'title' => 'View Student',
            ],
            1 => [
                'link' => route('account_manager.students.create'),
                'icon' => 'plus-square',
                'title' => 'Add New Student',
            ],
        ];
        if (
            auth()
                ->user()
                ->can('manage students')
        ) {
            if ($student->isActive()) {
                $actionItems[2] = [
                    'link' => route(
                        'account_manager.students.deactivate',
                        $student
                    ),
                    'icon' => 'user-x',
                    'title' => 'Deactivate',
                ];
            } else {
                $actionItems[2] = [
                    'link' => route(
                        'account_manager.students.activate',
                        $student
                    ),
                    'icon' => 'user-check',
                    'title' => 'Activate',
                ];
            }

            if (
                !auth()
                    ->user()
                    ->can('update students')
            ) {
                unset($actionItems[2]);
            }
        }
        $courses = Course::accessible()
            ->notRestricted()
            ->orderBy('category', 'asc')
            ->get();
        if (
            auth()
                ->user()
                ->can('published courses status')
        ) {
            $courses = Course::published()
                ->notRestricted()
                ->orderBy('category', 'asc')
                ->get();
        }
        $enrolments = $student->enrolments()->prepare();
        $companies = auth()
            ->user()
            ->isLeader()
            ? auth()->user()->companies
            : Company::all();

        $selected_company = $student->companies?->first();
        if (!empty($selected_company)) {
            $company_leaders = User::onlyCompanyLeaders(
                $selected_company->id
            )->get();
        }

        return view()
            ->make('content.account-manager.students.add-edit')
            ->with([
                'student' => $student,
                'courseEnrolments' => $student
                    ->courseEnrolments()
                    ->orderBy('id')
                    ->get(),
                'trainers' => User::onlyTrainers()->get(),
                'leaders' => $company_leaders ?? null,
                'companies' => $companies,
                'employment_service' => $this->employment_service,
                'schedule' => $this->schedule,
                'enrolments' => [
                    'basic' => $enrolments->get('basic')?->toArray(),
                ],
                'courses' => $courses,
                'action' => [
                    'url' => route('account_manager.students.update', $student),
                    'name' => 'Edit',
                ],
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'actionItems' => $actionItems,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $student)
    {
        //        ddd($student->company());

        $this->authorize('update students', $student);

        // Check if attempting to access a leader account via the student route
        if ($student->isLeader()) {
            return response()
                ->view('errors.500', [
                    'customMessage' => 'Cannot access a leader account via the student route. Please use the appropriate leader route instead.',
                ], 500);
        }
        $validated = $request->validate(
            [
                'first_name' => [
                    'required',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s]+/',
                ],
                'last_name' => [
                    'required',
                    'max:255',
                    'regex:/^[A-Za-z0-9\s]+/',
                ],
                'preferred_name' => ['nullable', 'max:255'],
                'email' =>
                    'required|unique:users,email,' . $student->id . ',id',
                'purchase_order' => 'required|string|max:255',
                'phone' => ['required', 'regex:/^[\+0-9]+/'],
                'address' => ['nullable', 'regex:/[- ,\/0-9a-zA-Z]+/'],
                //            'country' => 'required|numeric|exists:countries,id',
                'language' => [
                    'required',
                    Rule::in(array_keys(config('constants.language'))),
                ],
                'preferred_language' => ['nullable', 'string'],
                'password' => [
                    'nullable',
                    'min:6',
                    'confirmed',
                    'required_with:password_confirmation',
                ],
                'trainers' => ['nullable', 'exists:users,id'],
                'company' => [
                    'sometimes',
                    'exists:companies,id',
                    'required_with:leaders',
                    Rule::requiredIf(
                        auth()
                            ->user()
                            ->isAdmin()
                    ),
                ],
                'leaders' => [
                    'sometimes',
                    'exists:users,id',
                    Rule::requiredIf(
                        auth()
                            ->user()
                            ->isAdmin()
                    ),
                ],
                'schedule' => ['required', Rule::in($this->schedule)],
                'employment_service' => [
                    'required',
                    Rule::in($this->employment_service),
                ],
                'study_type' => [
                    'nullable',
                    Rule::in(config('constants.study_type')),
                ],
            ],
            [
                'phone.regex' => 'A valid phone is required',
                'address.regex' => 'A valid address is required',
            ]
        );

        //        $oldTrainer = $student->trainers()?->first()->id;
        //        $oldLeader = $student->leaders()?->first()->id;
        //        $oldComapny = $student->companies()?->first()->id;

        $student->first_name = $request->first_name;
        $student->last_name = $request->last_name;
        $student->email = $request->email;
        $student->study_type = $request->study_type;

        if (!empty($request->password)) {
            $student->password = Hash::make($request->password);
        }
        $student->save();
        $student->detail()->update([
            'purchase_order' => $request->purchase_order ?? '',
            'phone' => $request->phone ?? '',
            'address' => $request->address ?? '',
            //            'country_id' => $request->country,
            'language' => $request->language,
            'preferred_language' => $request->preferred_language,
            'timezone' => 'Australia/Melbourne',
            'preferred_name' => $request->preferred_name,
        ]);
        $enrolments = $student
            ->enrolments()
            ->where('enrolment_key', '=', 'basic')
            ->first();
        if (!empty($enrolments)) {
            $enrolments->enrolment_value = new Collection([
                'schedule' => $request->schedule,
                'employment_service' => $request->employment_service,
            ]);
            $enrolments->save();
        } else {
            $student->enrolments()->save(
                new Enrolment([
                    'enrolment_key' => 'basic',
                    'enrolment_value' => new Collection([
                        'schedule' => $request->schedule,
                        'employment_service' => $request->employment_service,
                    ]),
                ])
            );
        }

        // Sync trainers - if empty/null, sync with empty array to clear relationship
        $trainerIds = $request->filled('trainers') && !empty($request->trainers) ? [$request->trainers] : [];
        $student->trainers()->sync($trainerIds);

        $student->leaders()->sync(
            auth()
                ->user()
                ->isLeader()
            ? auth()->user()->id
            : $request->leaders
        );
        $student->companies()->sync($request->company);

        $student->refresh();

        AdminReportService::updateStudentWithRelation($student);

        return redirect()
            ->route('account_manager.students.show', $student)
            ->with('success', 'Student updated successfully');
    }

    public function activate(Request $request, User $student)
    {
        if ($student->isStudent()) {
            $student->is_active = true;
            $student->save();

            $student->detail->status = 'ACTIVE';
            $student->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('ACTIVATED')
                ->causedBy($causer)
                ->performedOn($student)
                ->withProperties([
                    'role' => 'Student',
                    'user_id' => $student->id,
                    'by' => [
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                        'id' => $causer->id,
                    ],
                ])
                ->log('Student is activated, status is ACTIVE now');

            // Add note to student profile
            $dateTime = Carbon::now(Helper::getTimeZone())->format('j F, Y g:i A');
            $noteBody = 'Account re-activated';
            $note = Note::create([
                'user_id' => $causer->id,
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'note_body' => $noteBody,
                'is_pinned' => false,
            ]);

            $this->activityService->setActivity(
                [
                    'user_id' => $student->id,
                    'activity_event' => 'NOTE ADDED',
                    'activity_details' => [
                        'student' => $student->id,
                        'by' => [
                            'id' => $causer->id,
                            'role' => $causer->roleName(),
                        ],
                        'is_pinned' => $note->is_pinned,
                    ],
                ],
                $note
            );
        }

        $registeredCourses = StudentCourseEnrolment::with('progress')
            ->where('user_id', $student->id)
            ->active()
            ->get();

        foreach ($registeredCourses as $regCourse) {
            //            if($regCourse->is_chargeable === 1){
            //                $regCourse->registration_date = Carbon::today( Helper::getTimeZone() )->format( 'Y-m-d' );
            //                $regCourse->save();
            //                activity( 'course_charged' )
            //                    ->event( 'ENROLEMENT' )
            //                    ->causedBy( auth()->user() )
            //                    ->performedOn( $regCourse )
            //                    ->withProperties([
            //                        'class' => StudentController::class,
            //                        'method' => 'activate',
            //                        'user_id' => $student->id,
            //                        'course_id' => $regCourse->course->id,
            //                        'ip' => $request->ip(),
            //                    ])
            //                    ->log( 'Course is chargeable now' );
            //            }

            $adminReportService = new AdminReportService(
                $student->id,
                $regCourse->course->id
            );
            $data = $adminReportService->prepareData(
                $student,
                $regCourse->course
            );
            $adminReportService->update($data);
        }

        (new AdminReport())->deListCourses(
            $student->id,
            $registeredCourses->pluck('course_id')
        );

        return redirect()
            ->route('account_manager.students.show', $student)
            ->with('success', 'Student status updated successfully');
    }

    public function deactivate(Request $request, User $student)
    {
        if ($student->isStudent()) {
            $student->is_active = false;
            $student->save();

            $student->detail->status = 'INACTIVE';
            $student->detail->save();

            $causer = auth()->user();
            $causer_role = $causer->roles()->first();
            activity('user_status')
                ->event('DEACTIVATED')
                ->causedBy($causer)
                ->performedOn($student)
                ->withProperties([
                    'role' => 'Student',
                    'user_id' => $student->id,
                    'causer' => [
                        'id' => $causer->id,
                        'role' => [
                            'id' => $causer_role->id,
                            'name' => $causer_role->name,
                        ],
                    ],
                ])
                ->log('Student is deactivated, status is INACTIVE now');

            // Add note to student profile
            $dateTime = Carbon::now(Helper::getTimeZone())->format('j F, Y g:i A');
            $noteBody = 'Account deactivated';
            $note = Note::create([
                'user_id' => $causer->id,
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'note_body' => $noteBody,
                'is_pinned' => false,
            ]);

            $this->activityService->setActivity(
                [
                    'user_id' => $student->id,
                    'activity_event' => 'NOTE ADDED',
                    'activity_details' => [
                        'student' => $student->id,
                        'by' => [
                            'id' => $causer->id,
                            'role' => $causer->roleName(),
                        ],
                        'is_pinned' => $note->is_pinned,
                    ],
                ],
                $note
            );
        }

        $registeredCourses = StudentCourseEnrolment::with('progress')
            ->where('user_id', $student->id)
            ->active()
            ->get();
        foreach ($registeredCourses as $regCourse) {
            $adminReportService = new AdminReportService(
                $student->id,
                $regCourse->course->id
            );
            $data = $adminReportService->prepareData(
                $student,
                $regCourse->course
            );
            $adminReportService->update($data, $regCourse);
        }

        (new AdminReport())->deListCourses(
            $student->id,
            $registeredCourses->pluck('course_id')
        );

        return redirect()
            ->route('account_manager.students.show', $student)
            ->with('success', 'Student status updated successfully');
    }

    /**
     * Resend password email to student.
     *
     * @param User $student
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendPassword(User $student)
    {
        $this->authorize('manage students');

        // Generate a new password
        $newPassword = $this->passwordService->generateInitialPassword();

        // Update student password
        $student->password = Hash::make($newPassword);
        $student->save();

        // Send notification with new password
        $student->notify(new ResendPasswordEmailNotification($newPassword));

        return redirect()
            ->route('account_manager.students.show', $student)
            ->with('success', 'Password email has been sent to ' . $student->email);
    }


    public function assign_course(Request $request, User $student)
    {
        // Check if any of the courses being added is a semester 2 course
        $hasSemester2Course = false;
        if (!empty($request['course'])) {
            foreach ($request['course'] as $item) {
                $course = Course::find(intval($item['course_id']));
                if ($course && stripos(Str::lower($course->title), 'semester 2') !== false) {
                    $hasSemester2Course = true;
                    break;
                }
            }
        }

        // Check if re-enrollment and agreement needs renewal (>1 year old)
        // Do NOT trigger re-enrollment if adding a semester 2 version of a course
        if (!$hasSemester2Course) {
            $existingEnrolments = StudentCourseEnrolment::where('user_id', $student->id)
                ->where('status', '!=', 'DELIST')
                ->exists();

            if ($existingEnrolments) {
                $onboardEnrolment = Enrolment::where('user_id', $student->id)
                    ->where(function($query) {
                        $query->where('enrolment_key', 'onboard')
                              ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                    })
                    ->where('is_active', true)
                    ->first();

                if ($onboardEnrolment && $onboardEnrolment->enrolment_value) {
                    $enrolmentValue = $onboardEnrolment->enrolment_value->toArray();
                    $signedOn = $enrolmentValue['step-6']['signed_on'] ?? $enrolmentValue['step-5']['signed_on'] ?? null;

                    if ($signedOn) {
                        $signedDate = is_array($signedOn) ? ($signedOn['key'] ?? $signedOn['value'] ?? $signedOn) : $signedOn;
                        // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                        $signedCarbon = is_numeric($signedDate)
                            ? Carbon::createFromTimestamp($signedDate)
                            : Carbon::parse($signedDate)->timezone(Helper::getTimeZone());

                        // If agreement is older than 1 year, archive original and create new enrollment without signature
                        if ($signedCarbon->diffInYears(Carbon::now()) >= 1) {
                            // Archive the original enrollment (preserve signed_on date)
                            $onboardEnrolment->is_active = false;
                            $onboardEnrolment->save();

                            // Create new enrollment without signed_on date for re-signing
                            $newEnrolmentValue = $enrolmentValue;
                            if (isset($newEnrolmentValue['step-6']['signed_on'])) {
                                unset($newEnrolmentValue['step-6']['signed_on']);
                            }
                            if (isset($newEnrolmentValue['step-5']['signed_on'])) {
                                unset($newEnrolmentValue['step-5']['signed_on']);
                            }

                            Enrolment::create([
                                'user_id' => $student->id,
                                'enrolment_key' => 'onboard',
                                'enrolment_value' => $newEnrolmentValue,
                                'is_active' => true,
                            ]);

                            // Preserve first enrollment date before clearing onboard_at
                            if (empty($student->detail->first_enrollment) && !empty($student->detail->onboard_at)) {
                                $student->detail->first_enrollment = $student->detail->onboard_at;
                            }

                            // Clear onboard_at to force student back to enrollment screen on next login
                            $student->detail->onboard_at = null;
                            $student->detail->save();

                            // Note: Re-enrollment note will be created when student completes the onboarding process
                        }
                    }
                }
            }
        }

        // Capture existing enrollments before processing to detect replacements
        $existingEnrollments = StudentCourseEnrolment::where('user_id', $student->id)
            ->where('status', '!=', 'DELIST')
            ->get()
            ->keyBy('course_id');
        $incomingCourseIds = [];

        $records = [];
        $assignedCourses = [];
        $assignedCoursesIds = [];
        if (!empty($request['course'])) {
            // First pass: collect incoming course IDs
            foreach ($request['course'] as $item) {
                $incomingCourseIds[] = intval($item['course_id']);
            }

            foreach ($request['course'] as $item) {
                $item['course_start_at'] = Carbon::parse(
                    $item['course_start_at']
                )->format('Y-m-d');
                $item['course_ends_at'] = Carbon::parse(
                    $item['course_ends_at']
                )->format('Y-m-d');
                $course = Course::find(intval($item['course_id']));
                if (empty($course)) {
                    continue;
                }
                $timeNow = '00:00:00';

                $is_chargeable = isset($item['is_chargeable']) ? 1 : 0;

                // Check if the course title contains "Semester 2"
                $isSemester2 =
                    stripos(Str::lower($course->title), 'semester 2') !== false;
                $isMainCourse = !$isSemester2;

                $is_locked = isset($item['is_locked']) ? 1 : 0;

                $course_start_date = filter_var(
                    $item['course_start_at'],
                    FILTER_SANITIZE_NUMBER_INT
                );
                $course_end_date = filter_var(
                    $item['course_ends_at'],
                    FILTER_SANITIZE_NUMBER_INT
                );
                $data = [
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'allowed_to_next_course' => isset(
                        $item['allowed_to_next_course']
                    )
                        ? 0
                        : 1, // Only Semester 1 = 0
                    'course_start_at' => $course_start_date . ' ' . $timeNow,
                    'course_ends_at' => $course_end_date . ' ' . $timeNow,
                    'status' => 'ENROLLED',
                    'version' => $course->version,
                    'is_chargeable' => $is_chargeable,
                    'registered_by' => auth()->user()->id,
                    'registered_on_create' => 0,
                    'is_semester_2' => $isSemester2,
                    'is_main_course' => $isMainCourse,
                    'is_locked' => $is_locked,
                ];

                $course_duration = Carbon::parse($course_end_date)->diffInDays(
                    Carbon::parse($course_start_date)
                );
                $data['course_expiry'] = Carbon::parse(
                    $course_start_date
                )->addDays($course_duration);

                $enrolledRecord = StudentCourseEnrolment::where(
                    'user_id',
                    intval($student->id)
                )
                    ->where('course_id', intval($course->id))
                    ->first();
                //                Helper::debug($course->id,'dd');
                //                if(auth()->user()->id === 1) {
                //                    if ( $course->id !== 100062 ) {
                //                        continue;
                //                    }
                //                }
                //                Helper::debug( [ 'request' => $request->toArray(), 'item' => $item, 'enrolment found ' => !empty( $enrolledRecord ) ] );
                //                dd($data, $enrolledRecord->toArray(), !empty( $enrolledRecord ));
                if (!empty($enrolledRecord)) {
                    //                    if($enrolledRecord->course_id === 4){
                    //                        Helper::debug('skipping course 4');
                    //                        continue;
                    //                    }
                    unset($data['version']);
                    //                    dd($data[ 'course_start_at' ], $enrolledRecord->getRawOriginal( 'course_start_at' ));
                    //                    Helper::debug( '$enrolledRecord found' );
                    //                    Helper::debug( [ $item, $data ] );
                    $this->setRegistrationData(
                        $enrolledRecord,
                        $data,
                        $is_chargeable
                    );

                    $data[
                        'registered_on_create'
                    ] = $enrolledRecord->getRawOriginal('registered_on_create');

                    //                    dd($data, $enrolledRecord->toArray());
                } else {
                    // Check if this is the first course on the account
                    // Accounts are always created with a course, so if there are no existing enrollments,
                    // this is the first course and we should not set registration_date
                    $existingEnrollmentsCount = StudentCourseEnrolment::where('user_id', $student->id)
                        ->where('course_id', '!=', $course->id)
                        ->count();
                    $isFirstCourse = $existingEnrollmentsCount === 0;

                    // Detect if a course is being replaced (removed from enrollment)
                    $replacedCourse = null;
                    foreach ($existingEnrollments as $existingCourseId => $existingEnrollment) {
                        // If an existing course is NOT in the incoming list, it's being replaced
                        if (!in_array($existingCourseId, $incomingCourseIds)) {
                            $replacedCourse = $existingEnrollment;
                            break;
                        }
                    }

                    // If replacing a course, check chargeability change
                    if (!empty($replacedCourse)) {
                        $wasChargeable = intval($replacedCourse->getRawOriginal('is_chargeable')) === 1;

                        // Only set today's date if new course is chargeable AND old course was NOT chargeable
                        if ($is_chargeable === 1 && !$wasChargeable) {
                            $data['registration_date'] = Carbon::today(
                                Helper::getTimeZone()
                            )->format('Y-m-d');
                            $data['registered_by'] = auth()->user()->id;
                            $data['show_registration_date'] = true;
                        } else {
                            // Otherwise, inherit dates from replaced course
                            $data['registration_date'] = $replacedCourse->getRawOriginal('registration_date');
                            $data['registered_by'] = $replacedCourse->getRawOriginal('registered_by');
                            $data['show_registration_date'] = $replacedCourse->getRawOriginal('show_registration_date');
                            $data['show_on_widget'] = $replacedCourse->getRawOriginal('show_on_widget');
                            // Inherit created_at timestamp to preserve original enrollment date
                            $data['created_at'] = $replacedCourse->getRawOriginal('created_at');
                        }
                    } elseif (!$isFirstCourse && $is_chargeable === 1) {
                        $data['registration_date'] = Carbon::today(
                            Helper::getTimeZone()
                        )->format('Y-m-d');
                        $data['registered_by'] = auth()->user()->id;
                        $data['show_registration_date'] = true;
                    } else {
                        // First course - do not set registration_date
                        $data['registration_date'] = null;
                        $data['registered_by'] = auth()->user()->id;
                        $data['show_registration_date'] = false;
                    }
                }

                if (isset($item['deferred'])) {
                    $data['deferred'] = true;
                    $deferred_details = [];
                    if (!empty($enrolledRecord)) {
                        $deferred_details =
                            $enrolledRecord->deferred_details ?? [];
                        $values = [
                            'course_start_at' => $enrolledRecord->getRawOriginal(
                                'course_start_at'
                            ),
                            'course_ends_at' => $enrolledRecord->getRawOriginal(
                                'course_ends_at'
                            ),
                            'changed_at' => Carbon::now(),
                            'user_id' => auth()->user()->id,
                        ];
                        array_unshift($deferred_details, $values);
                    }
                    $data['deferred_details'] = $deferred_details;
                    $adminReportService = new AdminReportService(
                        $student->id,
                        $course->id
                    );
                    $adminReportService->updateCourse($course);
                    //                    $adminReportService->update( $adminReportService->prepareData( $student, $course ) );
                } else {
                    $data['deferred'] = false;
                    $data['deferred_details'] = [];
                }
                unset($data['user_id']);
                unset($data['course_id']);
                //                Helper::debug( [ $data, $enrolledRecord ],'dd' );
                $record = StudentCourseEnrolment::updateOrCreate(
                    [
                        'user_id' => intval($student->id),
                        'course_id' => intval($course->id),
                    ],
                    $data
                );
                //                Helper::debug([$record, $is_chargeable, $isMainCourse]);
                CourseProgressService::updateStudentCourseStats(
                    $record,
                    $isMainCourse
                );

                // Setup Course Expiry Date
                $calculatedCourseExpiry = CourseProgressService::setupExpiryDate(
                    $record
                );

                if (
                    $is_chargeable === 1 &&
                    !empty($enrolledRecord) &&
                    intval($enrolledRecord->getRawOriginal('is_chargeable')) ===
                    0
                ) {
                    activity('course_charged')
                        ->event('ENROLEMENT')
                        ->causedBy(auth()->user())
                        ->performedOn($record)
                        ->withProperties([
                            'class' => StudentController::class,
                            'method' => 'assign_course',
                            'user_id' => $student->id,
                            'course_id' => $course->id,
                            'ip' => $request->ip(),
                        ])
                        ->log('Course is chargeable now');
                }
                if ($is_locked === 1) {
                    activity('course_locked')
                        ->event('ENROLEMENT')
                        ->causedBy(auth()->user())
                        ->performedOn($record)
                        ->withProperties([
                            'class' => StudentController::class,
                            'method' => 'assign_course',
                            'user_id' => $student->id,
                            'course_id' => $course->id,
                            'ip' => $request->ip(),
                        ])
                        ->log('Course is locked now');
                }

                $assignedCourses[] = $course;
                $assignedCoursesIds[] = $course->id;
                $records[] = $record->id;

                // REGISTER 2ND COURSE AS SEMESTER 2
                if ($data['allowed_to_next_course'] === 1) {
                    $semester2Record = $this->enrolNextCourse(
                        $course,
                        $item['course_ends_at'],
                        $student,
                        $is_chargeable,
                        false,
                        $record  // Pass semester 1 enrollment to inherit dates
                    );
                    $next_course = Course::find(intval($course->next_course));
                    if (!empty($next_course)) {
                        //                        $assignedCourses[] = $next_course;
                        $assignedCoursesIds[] = $next_course->id;
                        $records[] = $semester2Record->id;
                    }
                }
                //                dd($assignedCourses, $assignedCoursesIds, $records);
                // \Log::debug(
                //     'deferred '.(isset($data['deferred']) ? 'Yes' : 'No'),
                //     [
                //         'request' => $request['course'],
                //         'input' => $item,
                //         'data' => $data,
                //     ]
                // );
            }

            $records = array_unique($records);
            $assignedCoursesIds = array_unique($assignedCoursesIds);
        }

        $removed = StudentCourseEnrolment::where('user_id', $student->id)
            ->whereNotIn('id', $records)
            ->update(['status' => 'DELIST']);
        //        dd($records, $removed);

        $leader =
            !empty($student->leaders()) && $student->leaders()->count() > 0
            ? $student->leaders()->first()->user
            : null;

        // Check if leader should be notified (default to true if not specified)
        $notifyLeader = $request->has('notify_leader')
            ? (bool)$request->notify_leader
            : true;

        // \Log::debug(
        //     'Assigned Courses for student# '.
        //         $student->id.
        //         ' '.
        //         $student->name,
        //     $assignedCourses
        // );

        foreach ($assignedCourses as $course) {
            CourseProgressService::initProgressSession(
                $student->id,
                $course->id
            );

            $adminReportService = new AdminReportService(
                $student->id,
                $course->id
            );
            $preparedData = $adminReportService->prepareData($student, $course);
            //            dd($preparedData);
            $adminReportService->update($preparedData);
            // NOTIFY LEADER (only if notifyLeader is true)
            if (!empty($leader) && $notifyLeader) {
                activity('communication')
                    ->event('NOTIFICATION')
                    ->causedBy(auth()->user())
                    ->performedOn($leader)
                    ->withProperties([
                        'for' => 'Student Registered',
                        'action' => 'email sent to leader',
                        'event' => 'COURSE ASSIGNED',
                        'event_details' => $course->toArray(),
                        'event_user' => $student,
                        'leader' => $leader,
                    ])
                    ->log('Email Notification');
                //                Log::info( 'StudentRegisteredToLeader', [
                //                        'leader' => $leader,
                //                        'event' => 'COURSE ASSIGNED',
                //                        'event_details' => $course->toArray(),
                //                        'event_user' => $student,
                //                    ]
                //                );
                \Log::info(
                    'Student# ' .
                    $student->id .
                    ' ' .
                    $student->name .
                    ' Registered to ' .
                    $course->name .
                    ' ' .
                    $course->category .
                    ' by ' .
                    $leader->id
                );
            } else {
                if (!empty($leader) && !$notifyLeader) {
                    \Log::info(
                        'Leader notification skipped by user choice for student# ' .
                        $student->id
                    );
                } else {
                    \Log::debug('Leader Not found for student', [
                        'student' => $student,
                        'leader' => $leader,
                    ]);
                }
            }
        }

        // Send notification to leader only if notifyLeader is true
        if (!empty($leader) && !empty($assignedCourses) && $notifyLeader) {
            // Get selected course IDs from checkboxes (if provided)
            $emailCourseIds = $request->has('email_course_ids')
                ? array_map('intval', (array)$request->email_course_ids)
                : [];

            if (\Str::lower($course->category) === 'anaconda') {
                $leader->notify(new AnacondaCourseNotification($student, $emailCourseIds));
            } else {
                $leader->notify(new StudentAssignedCourse($student, $emailCourseIds));
            }
        }

        $removeAssignedCourses = AdminReportService::softDelete(
            $student->id,
            $assignedCoursesIds
        );

        return response()->json(
            [
                'data' => [
                    'added' => $records,
                    'removed' => $removed,
                    'removeAssignedCourses' => $removeAssignedCourses,
                ],
                'success' => true,
                'status' => 'success',
                'message' =>
                    'Successfully updated  ' .
                    \Str::plural('Course', count($records)) .
                    ' enrolment',
            ],
            200
        );
    }

    /**
     * Updates registration data and visibility based on various assign-course scenarios.
     *
     * @param StudentCourseEnrolment $enrolledRecord Existing StudentCourseEnrolment record
     * @param array &                $data           Incoming data to save (course dates, flags, etc.)
     * @param int $is_chargeable New "is_chargeable" flag from form (0 or 1)
     */
    private function setRegistrationData(
        StudentCourseEnrolment $enrolledRecord,
        array &$data,
        int $is_chargeable
    ): void {
        // Fetch original values
        $originalStart = $enrolledRecord->getRawOriginal('course_start_at');
        $originalEnd = $enrolledRecord->getRawOriginal('course_ends_at');
        $originalCourseId = $enrolledRecord->getRawOriginal('course_id');
        $originalCharge = intval(
            $enrolledRecord->getRawOriginal('is_chargeable')
        );
        $originalRegDate = $enrolledRecord->getRawOriginal('registration_date');
        $originalRegBy = $enrolledRecord->getRawOriginal('registered_by');
        $createdAt = Carbon::parse(
            $enrolledRecord->getRawOriginal('created_at'),
            Helper::getTimeZone()
        );
        $originalShowOnWidget = $enrolledRecord->getRawOriginal(
            'show_on_widget'
        );

        $isSemester2 =
            stripos(
                Str::lower($enrolledRecord->course->title),
                'semester 2'
            ) !== false;

        // Detect what changed - normalize dates for comparison
        $startChanged = !empty($data['course_start_at']) && !empty($originalStart) 
            && trim($data['course_start_at']) !== trim($originalStart);
        $endChanged = !empty($data['course_ends_at']) && !empty($originalEnd) 
            && trim($data['course_ends_at']) !== trim($originalEnd);
        $courseChanged = $data['course_id'] !== $originalCourseId;
        $nowChargeable = $is_chargeable === 1;
        $wasChargeable = $originalCharge === 1;
        $createdToday = $createdAt->isToday();

        // Initialize visibility flag for admin report
        $data['show_registration_date'] = !empty($originalRegDate);
        //        Helper::debug([$enrolledRecord, $isSemester2, $data, $is_chargeable],'dd');
        //        if($enrolledRecord->id === 3155){ return; }
        //        Helper::debug([$enrolledRecord->id,$isSemester2,$data[ 'course_start_at' ], Carbon::parse( $data[ 'course_start_at' ] )->isFuture(), $nowChargeable ],'dd');
        
        // PRIORITY: Only end date changed - preserve original registration date
        // This must be checked FIRST to prevent other scenarios from overwriting registration_date
        if (!$courseChanged && !$startChanged && $endChanged) {
            $data['registration_date'] = $originalRegDate;
            $data['registered_by'] = $originalRegBy;
            $data['show_registration_date'] = !empty($originalRegDate);
            $data['show_on_widget'] = $originalShowOnWidget ?? true;
        
        // 1️⃣ Created today AND chargeable: register and show
        } elseif ($createdToday && $nowChargeable) {
            //            Helper::debug( '1️⃣ Created today AND chargeable: register and show' );
            $data['registration_date'] = Carbon::today(
                Helper::getTimeZone()
            )->format('Y-m-d');
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;

            // 2️⃣ Scenario 2: New course added AND now chargeable (wasn't before)
        } elseif ($courseChanged && $nowChargeable && !$wasChargeable) {
            // Fresh registration today
            //            Helper::debug( '2️⃣ Scenario 2: New course added AND now chargeable (wasn\'t before)' );
            $data['registration_date'] = Carbon::today(
                Helper::getTimeZone()
            )->format('Y-m-d');
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;

            // 3️⃣ Scenario 4: Start date changed AND now chargeable (wasn't before)
        } elseif ($startChanged && $nowChargeable && !$wasChargeable) {
            // Fresh registration on chargeable toggle
            //            Helper::debug( '3️⃣ Scenario 4: Start date changed AND now chargeable (wasn\'t before)' );
            $data['registration_date'] = Carbon::today(
                Helper::getTimeZone()
            )->format('Y-m-d');
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;
        } elseif (
            $isSemester2 &&
            Carbon::parse($data['course_start_at'])->isFuture() &&
            $nowChargeable
        ) {
            // Scenario: Semester 2 course with future start date and chargeable
            //            Helper::debug('Scenario: Semester 2 course with future start date and chargeable');
            $data['registration_date'] = Carbon::today(
                Helper::getTimeZone()
            )->format('Y-m-d');
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;
        } elseif (($courseChanged || $startChanged) && !$nowChargeable) {
            // 4️⃣ Scenario 3: Course/start changed but still NOT chargeable
            // Log new date for reporting
            //            Helper::debug( '4️⃣ Scenario 3: Course/start changed but still NOT chargeable' );
            $data['registration_date'] = null;
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = false;
            $data['show_on_widget'] = true;

            // 5️⃣ Scenario 5: Course/start changed but was already chargeable
        } elseif (($courseChanged || $startChanged) && $wasChargeable) {
            // Keep original registration and show it in report
            //            Helper::debug( '5️⃣ Scenario 5: Course/start changed but was already chargeable' );
            $data['registration_date'] = $originalRegDate;
            $data['registered_by'] = $originalRegBy;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;

            // 6️⃣ Scenario: Chargeability changed from Not Chargeable to Chargeable
        } elseif ($nowChargeable && !$wasChargeable) {
            // Create new registration date when making course chargeable
            //            Helper::debug( '6️⃣ Scenario: Chargeability changed from Not Chargeable to Chargeable' );
            $data['registration_date'] = Carbon::today(
                Helper::getTimeZone()
            )->format('Y-m-d');
            $data['registered_by'] = auth()->user()->id;
            $data['show_registration_date'] = true;
            $data['show_on_widget'] = true;
        } else {
            // 🔶 Fallback: no relevant change — carry forward existing data
            //            Helper::debug( '🔶 Fallback: no relevant change — carry forward existing data' );
            $data['registration_date'] = $originalRegDate;
            $data['registered_by'] = $originalRegBy;
            $data['show_registration_date'] = !empty($originalRegDate);
            $data['show_on_widget'] = $originalShowOnWidget;
        }
        //        Helper::debug($data,'dd');
    }

    public function delete_courses(User $student)
    {
        $delist = StudentCourseEnrolment::where(
            'user_id',
            $student->id
        )->update(['status' => 'DELIST']);

        return response()->json(
            [
                'data' => $delist,
                'success' => true,
                'status' => 'success',
                'message' => 'Successfully deleted',
            ],
            201
        );
    }

    public function uploadEvidenceChecklist(Request $request, Lesson $lesson)
    {
        if (empty($request->student)) {
            return Helper::errorResponse('Invalid Student Information', 404);
        }
        $student_id = $request->student;
        $file = '';
        $filePath = '';
        $destination = '';
        $nowTime = Carbon::now();
        $markedBy = auth()->user();
        //        dd($request->all(), $lesson);

        if ($request->hasFile('file')) {
            $filePath = 'public/user/' . $student_id . '/evidence';
            Helper::ensureDirectoryWithPermissions($filePath);
            if (!File::isDirectory($filePath)) {
                File::makeDirectory($filePath, 0755, true);
            }
            $file = $request->file('file');
            $destination = $file->storeAs(
                $filePath,
                'quiz_' .
                $lesson->id .
                '.' .
                $file->getClientOriginalExtension()
            );
            $upload = [
                'filePath' => $filePath,
                'destination' => $destination,
                'title' => 'lesson_' . $lesson->id,
                'name' => $file?->getClientOriginalName(),
                'ext' => $file?->getClientOriginalExtension(),
                'mime' => $file?->getMimeType(),
                'path' => $file?->getRealPath(),
                'size' => $file?->getSize(),
            ];

            $attachment = StudentLMSAttachables::create([
                'student_id' => $student_id,
                'event' => 'EVIDENCE',
                'attachable_type' => Lesson::class,
                'attachable_id' => $lesson->id,
                'causer_type' => User::class,
                'causer_id' => $markedBy->id,
                'description' => 'Evidence uploaded.',
                'properties' => [
                    'file_name' => $request->name ?? '',
                    'file' => $upload,
                    'status' => $request->status ?? 'N/A',
                    'ip' => request()->ip(),
                    'time' => $nowTime->toDateTimeString(),
                ],
                'created_at' => $nowTime,
                'updated_at' => $nowTime,
            ]);
            if (!empty($attachment)) {
                CourseProgressService::createStudentActivity(
                    $lesson,
                    'EVIDENCE UPLOADED',
                    $student_id,
                    [
                        'status' => 'COMPLETE',
                        'student' => $student_id,
                        'course_id' => $lesson->course_id,
                        'marked_by_id' => $markedBy->id,
                        'marked_by_role' => $markedBy->roleName(),
                        'marked_at' => $nowTime->toDateTimeString(),
                    ]
                );
                StudentCourseService::addCompetency($student_id, $lesson);
            }

            return Helper::successResponse(
                [
                    'upload' =>
                        strtolower(env('APP_ENV')) === 'local'
                        ? $upload
                        : $destination,
                    'redirect' =>
                        route(
                            'account_manager.students.show',
                            $request->student
                        ) . '#student-training-plan-tab',
                ],
                'EVIDENCE UPLOADED'
            );
        }

        return Helper::errorResponse('MISSING EVIDENCE FILE', 404);
    }

    public function uploadQuizChecklist(
        Request $request,
        Quiz $quiz
    ): \Illuminate\Http\JsonResponse {
        if (empty($request->student)) {
            return Helper::errorResponse('Invalid Student Information', 404);
        }
        $student_id = $request->student;
        $nowTime = Carbon::now();
        $markedBy = auth()->user();
        $file = '';
        $filePath = '';
        $destination = '';
        $checklistCount = StudentLMSAttachables::forEvent('CHECKLIST')
            ->forAttachable(Quiz::class, $quiz->id)
            ->where('student_id', $student_id)
            ->count();

        if ($checklistCount >= 3) {
            return Helper::errorResponse('Maximum upload limit reached', 401);
        }

        if ($request->hasFile('file')) {
            $filePath = 'public/user/' . $student_id . '/checklist';
            Helper::ensureDirectoryWithPermissions($filePath);
            if (!File::isDirectory($filePath)) {
                File::makeDirectory($filePath, 0755, true);
            }
            $file = $request->file('file');
            $destination = $file->storeAs(
                $filePath,
                'quiz_' . $quiz->id . '.' . $file->getClientOriginalExtension()
            );
            $upload = [
                'filePath' => $filePath,
                'destination' => $destination,
                'title' => 'quiz_' . $quiz->id,
                'name' => $file?->getClientOriginalName(),
                'ext' => $file?->getClientOriginalExtension(),
                'mime' => $file?->getMimeType(),
                'path' => $file?->getRealPath(),
                'size' => $file?->getSize(),
            ];

            $attachment = StudentLMSAttachables::create([
                'student_id' => $student_id,
                'event' => 'CHECKLIST',
                'attachable_type' => Quiz::class,
                'attachable_id' => $quiz->id,
                'causer_type' => User::class,
                'causer_id' => $markedBy->id,
                'description' => 'Checklist uploaded.',
                'properties' => [
                    'file_name' => $request->name ?? '',
                    'file' => $upload,
                    'status' => $request->status ?? 'N/A',
                    'ip' => request()->ip(),
                    'time' => $nowTime->toDateTimeString(),
                ],
                'created_at' => $nowTime,
                'updated_at' => $nowTime,
            ]);
            if (!empty($attachment)) {
                CourseProgressService::createStudentActivity(
                    $quiz,
                    'CHECKLIST UPLOADED',
                    $student_id,
                    [
                        'status' => 'COMPLETE',
                        'student' => $student_id,
                        'course_id' => $quiz->course_id,
                        'marked_by_id' => $markedBy->id,
                        'marked_by_role' => $markedBy->roleName(),
                        'marked_at' => $nowTime->toDateTimeString(),
                    ]
                );
                StudentCourseService::addCompetency($student_id, $quiz->lesson);
            }

            return Helper::successResponse(
                [
                    'upload' =>
                        strtolower(env('APP_ENV')) === 'local'
                        ? $upload
                        : $destination,
                    'redirect' =>
                        route(
                            'account_manager.students.show',
                            $request->student
                        ) . '#student-training-plan-tab',
                ],
                'CHECKLIST UPLOADED'
            );
        }

        return Helper::errorResponse('MISSING CHECKLIST FILE', 404);
    }

    public function markWorkPlacementComplete(
        Request $request,
        Lesson $lesson
    ): \Illuminate\Http\JsonResponse {
        if (empty($request->student)) {
            return Helper::errorResponse('Invalid Student Information', 404);
        }
        $student_id = $request->student;
        //        dd([$request->all(), $student_id, $lesson->toArray()]);
        $nowTime = Carbon::now();
        $markedBy = auth()->user();
        $attachment = StudentLMSAttachables::create([
            'student_id' => $student_id,
            'event' => 'WORK_PLACEMENT',
            'attachable_type' => Lesson::class,
            'attachable_id' => $lesson->id,
            'causer_type' => User::class,
            'causer_id' => $markedBy->id,
            'properties' => [
                'ip' => request()->ip(),
                'time' => $nowTime->toDateTimeString(),
            ],
            'created_at' => $nowTime,
            'updated_at' => $nowTime,
        ]);

        if (!empty($attachment)) {
            CourseProgressService::createStudentActivity(
                $lesson,
                'WORK PLACEMENT MARKED',
                $student_id,
                [
                    'status' => 'COMPLETE',
                    'student' => $student_id,
                    'course_id' => $lesson->course?->id,
                    'marked_by_id' => $markedBy->id,
                    'marked_by_role' => $markedBy->roleName(),
                    'marked_at' => Carbon::now()->toDateTimeString(),
                ]
            );

            StudentCourseService::updateLessonEndDates(
                $student_id,
                $lesson->course_id,
                $lesson->id
            );

            StudentCourseService::addCompetency($student_id, $lesson);

            // Add Note
            $note = Note::create([
                'user_id' => auth()->user()->id,
                'subject_type' => User::class,
                'subject_id' => $student_id,
                'note_body' => "<p>Work Placement Completed for lesson# $lesson->id: '$lesson->title'</p>",
                'data' => json_encode([
                    'type' => 'WORK PLACEMENT MARKED',
                    'lesson_id' => $lesson->id,
                    'course_id' => $lesson->course_id,
                    'student_id' => $student_id,
                    'marked_by_id' => $markedBy->id,
                    'marked_by_role' => $markedBy->roleName(),
                    'marked_at' => Carbon::now()->toDateTimeString(),
                ]),
            ]);
            $this->activityService->setActivity(
                [
                    'user_id' => $student_id,
                    'activity_event' => 'NOTE ADDED',
                    'activity_details' => [
                        'student' => $student_id,
                        'by' => [
                            'id' => auth()->user()->id,
                            'role' => auth()
                                ->user()
                                ->roleName(),
                        ],
                    ],
                ],
                $note
            );
        }

        return Helper::successResponse(
            [
                'redirect' =>
                    route('account_manager.students.show', $request->student) .
                    '#student-training-plan-tab',
            ],
            'WORK PLACEMENT COMPLETE'
        );
    }

    public function competentLessonComplete(Request $request, Lesson $lesson)
    {
        if (empty($request->student)) {
            Helper::errorResponse('Invalid Student Information', 404);
        }
        $student_id = $request->student;
        //        dd($request->all(), $student_id, $lesson->toArray());
        $endDate =
            $request->endDate && $lesson->lesson_end_date
            ? (new Carbon($request->endDate) <
                new Carbon($lesson->lesson_end_date)
                ? $lesson->lesson_end_date
                : $request->endDate)
            : $request->endDate;
        CourseProgressService::createStudentActivity(
            $lesson,
            'LESSON COMPETENT',
            $student_id,
            [
                'status' => 'COMPETENT',
                'student_id' => $student_id,
                'lesson_id' => $lesson->id,
                'course_id' => $lesson->course?->id,
                'marked_by_id' => auth()->user()->id,
                'marked_by_role' => auth()
                    ->user()
                    ->roleName(),
                'marked_at' => Carbon::parse($endDate)->toDateTimeString(),
                'request' => $request->all(),
            ]
        );

        if (
            !StudentCourseService::markCompetency(
                $student_id,
                $lesson,
                $request->remarks,
                $endDate
            )
        ) {
            return Helper::errorResponse('Please add remarks', 404);
        }

        return Helper::successResponse(
            [
                'redirect' =>
                    route('account_manager.students.show', $request->student) .
                    '#student-training-plan-tab',
            ],
            'LESSON MARKED COMPETENT'
        );
    }

    public function markLessonComplete(Request $request, Lesson $lesson)
    {
        if (empty($request->student)) {
            Helper::errorResponse('Invalid Student Information', 404);
        }
        $student_id = $request->student;
        CourseProgressService::createStudentActivity(
            $lesson,
            'LESSON MARKED',
            $student_id,
            [
                'status' => 'COMPLETE',
                'student' => $student_id,
                'course_id' => $lesson->course?->id,
                'by' => auth()->user()->id,
                'marked_by_id' => auth()->user()->id,
                'marked_by_role' => auth()
                    ->user()
                    ->roleName(),
                'marked_at' => Carbon::now()->toDateTimeString(),
            ]
        );
        // set auto_correct = true if you want to pass lesson without any quiz submissions
        $progress = CourseProgressService::markComplete(
            'lesson',
            [
                'user_id' => $student_id,
                'course_id' => $lesson->course->id,
                'lesson' => $lesson->id,
            ],
            true
        );
        CourseProgressService::updateProgressSession($progress);

        StudentCourseService::addCompetency($student_id, $lesson);

        Helper::successResponse(
            [
                'redirect' =>
                    route('account_manager.students.show', $request->student) .
                    '#student-training-plan-tab',
            ],
            'LESSON MARKED COMPLETE'
        );
    }

    public function markTopicComplete(Request $request, Topic $topic)
    {
        if (empty($request->student)) {
            Helper::errorResponse('Invalid User', 404);
        }
        $student_id = $request->student;
        CourseProgressService::createStudentActivity(
            $topic,
            'TOPIC MARKED',
            $student_id,
            [
                'total_quizzes' => $topic->quizzes()->count(),
                'topic_time' => $topic->estimated_time,
                'student' => $student_id,
                'course_id' => $topic->course?->id,
                'by' => auth()->user()->id,
                'marked_by_id' => auth()->user()->id,
                'marked_by_role' => auth()
                    ->user()
                    ->roleName(),
                'marked_at' => Carbon::now()->toDateTimeString(),
            ]
        );
        // set auto_correct = true if you want to pass lesson without any quiz submissions
        $progress = CourseProgressService::markComplete(
            'topic',
            [
                'user_id' => $request->student,
                'course_id' => $topic->course->id,
                'lesson' => $topic->lesson->id,
                'topic' => $topic->id,
            ],
            true
        );
        CourseProgressService::updateProgressSession($progress);

        Helper::successResponse(
            [
                'redirect' =>
                    route('account_manager.students.show', $request->student) .
                    '#student-training-plan-tab',
            ],
            'LESSON MARKED COMPLETE'
        );
    }

    public function get_courses(User $student)
    {
        return response()->json(
            [
                'data' => $student->courseEnrolments->toArray(),
                'success' => true,
                'status' => 'success',
                'message' => 'Successfully loaded',
            ],
            200
        );
    }

    public function getEnrolment(User $student, $key = null)
    {
        if ($key === null) {
            return new EnrolmentCollection(
                Enrolment::where('user_id', '=', $student->id)
                    ->prepare()
                    ->get()
            );
        }

        // If key is 'onboard', find the latest active enrolment (onboard, onboard2, onboard3, etc.)
        if ($key === 'onboard') {
            $enrolment = Enrolment::where('user_id', $student->id)
                ->where(function($query) {
                    $query->where('enrolment_key', 'onboard')
                          ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                })
                ->where('is_active', true)
                ->orderBy('id', 'desc')
                ->first();

            if ($enrolment) {
                return new EnrolmentResource($enrolment);
            }

            // If no active enrolment found, return 404
            abort(404, 'No active enrolment found');
        }

        return new EnrolmentResource(
            Enrolment::where('user_id', $student->id)
                ->where('enrolment_key', $key)
                ->firstOrFail()
        );
    }

    public function getDocuments(User $student)
    {
        return new DocumentCollection(
            Document::where('user_id', $student->id)->get()
        );
    }

    public function getHistory(Request $request, User $student)
    {
        $startDate = Carbon::parse($request->start)->toDateString();
        $endDate = Carbon::parse($request->end)->toDateString();

        $activityCollection = $this->activityService->getActivityWhere(
            [
                'user_id' => $student->id,
                ['activity_on', '>=', $startDate],
                ['activity_on', '<=', $endDate],
                ['activity_event', '!=', 'LESSON END'],
                ['activity_event', '!=', 'LESSON MARKED'],
                ['activity_event', '!=', 'TOPIC END'],
                ['activity_event', '!=', 'TOPIC MARKED'],
            ],
            ['actionable']
        );
        //        $activityService = app()[ StudentActivityService::class ];

        $competencyCollection = Competency::with('lesson')
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->where(function ($q) use ($startDate, $endDate) {
                        // If competent_on is not null, consider it as the lesson_end date
                        $q->whereNotNull('competent_on')->whereBetween(
                            'competent_on',
                            [$startDate, $endDate]
                        );
                    })
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        // If competent_on is null, use lesson_end as the lesson_end date
                        $q->whereNull('competent_on')->whereBetween(
                            'lesson_end',
                            [$startDate, $endDate]
                        );
                    });
            })
            ->where('user_id', $student->id)
            ->get();

        $theCollection = $activityCollection->merge($competencyCollection);

        //        if($student->id === 268){
        //            Helper::debug($competencyCollection->first(), 'dd');
        // //            Helper::debug([$activityCollection->first(),$competencyCollection->first()], 'dd');
        //        }
        $studentActivityCollection = new StudentActivityCollection(
            $theCollection
        );

        //        if($student->id === 268){
        //            Helper::debug($studentActivityCollection, 'dd');
        //        }

        return $studentActivityCollection;
    }

    public function getTrainingPlan(User $student)
    {
        $StudentTrainingPlanService = new StudentTrainingPlanService(
            $student->id
        );
        $trainingPlan = $StudentTrainingPlanService->getTrainingPlan(true);

        $output = $StudentTrainingPlanService->renderTrainingPlan(
            $trainingPlan,
            $student
        );

        return Helper::successResponse(
            $output,
            'Training Plan Rendered Successfully!'
        );
    }

    public function getAssessments(User $student)
    {
        $quizAttempts = QuizAttempt::where('user_id', $student->id)
            ->with(['quiz', 'lesson', 'topic', 'course'])
            ->where('system_result', '!=', 'INPROGRESS')
            ->latestAttemptSubmittedOnly()
            ->orderBy('created_at', 'DESC')
            ->get();
        //        \Log::debug('ASSESSMENTS',['query' => QuizAttempt::where( 'user_id', $student->id )
        //                               ->with( 'quiz' )
        //                               ->where( 'system_result', '!=', 'INPROGRESS' )
        //                               ->latestAttempt()
        //                               ->orderBy( 'created_at', 'DESC' )->toSql()]);
        //        dd($quizAttempts);
        $output = '';

        if (count($quizAttempts) < 1) {
            return Helper::successResponse($output, 'No Assessments found');
        }
        $output .=
            '<div class="table-responsive"><table class="table table-striped" id="student_assessments_table">';
        $output .=
            '<thead class="table-dark"><tr><th>Submitted On</th><th>Quiz</th><th>Related</th><th>Status</th><th>Details</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($quizAttempts as $attempt) {
            $output .= '<tr>';

            $output .=
                '<td>' .
                (!empty($attempt->submitted_at)
                    ? $attempt->submitted_at
                    : ($attempt->system_result === 'MARKED'
                        ? 'MARKED COMPLETED'
                        : 'IN PROGRESS')) .
                '</td>';
            if (!empty($attempt->quiz)) {
                if (
                    !auth()
                        ->user()
                        ->can('view assessments') ||
                    $attempt->system_result === 'INPROGRESS'
                ) {
                    $output .= "<td>{$attempt->quiz->title}</td>";
                } elseif (
                    auth()
                        ->user()
                        ->can('view assessments')
                ) {
                    $output .=
                        "<td><a href='" .
                        route('assessments.show', [
                            'assessment' => $attempt->id,
                            'redirect' => 'student',
                        ]) .
                        "' title='{$attempt->quiz->title}'>{$attempt->quiz->title}</a></td>";
                }
            } else {
                \Log::alert('check Quiz Attempt details', $attempt->toArray());
            }
            $output .= "<td><p>Topic: {$attempt->topic?->title}</p><p>Lesson: {$attempt->lesson?->title}</p><p>Course: {$attempt->course?->title}</p></td>";
            $output .=
                "<td><span class='text-" .
                config('lms.status.' . $attempt->status . '.class') .
                "'>" .
                (in_array($attempt->status, ['FAIL', 'RETURNED'])
                    ? 'NOT SATISFACTORY'
                    : $attempt->status) .
                '</span></td>';

            $evaluation = $attempt->evaluation()?->latest()->first();
            $activity = $this->activityService->getActivityWhere([
                'activity_event' => 'ASSESSMENT MARKED',
                'actionable_type' => QuizAttempt::class,
                'actionable_id' => $attempt->id,
            ]);

            if (!empty($evaluation)) {
                $accessor_id = $attempt->accessor_id;
                $assessed_by = User::find($accessor_id);
                $activity_time = Carbon::parse(
                    $attempt->getRawOriginal('accessed_at')
                )
                    ->timezone(Helper::getTimeZone())
                    ->format('j F, Y g:i A');
                if (empty($accessor_id)) {
                    $output .= '<td>Not assessed yet.</td>';
                } elseif (
                    $attempt->system_result === 'EVALUATED' ||
                    ($accessor_id === $student->id &&
                        $attempt->system_result === 'MARKED')
                ) {
                    $output .= "<td><p>Assessed By: <a href='javascript:void(0)'>(Auto Competent)</a></p><p>Assessed On: $activity_time</p></td>";
                } elseif (!empty($assessed_by) && !empty($accessor_id)) {
                    $output .=
                        "<td><p>Assessed By: <a href='" .
                        (auth()
                            ->user()
                            ->can('view users')
                            ? route('user_management.users.show', $accessor_id)
                            : 'javascript:void(0)') .
                        "'>" .
                        $assessed_by->name .
                        "</a></p><p>Assessed On: $activity_time</p></td>";
                }
            } else {
                $output .= '<td>Not assessed yet.</td>';
            }

            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table></div>';

        return Helper::successResponse(
            ['html' => $output, 'raw' => $quizAttempts],
            'Successfully loaded assessments'
        );
    }

    public function getStudentActivities(
        User $student,
        StudentActivityDataTable $dataTable,
        Request $request
    ) {
        $this->authorize('create students');

        // Render the table HTML for the tab
        return view('content.account-manager.students.activities-table', [
            'student' => $student,
            'dataTable' => $dataTable,
        ]);
    }

    public function getStudentActivitiesData(
        User $student,
        StudentActivityDataTable $dataTable,
        Request $request
    ) {
        $this->authorize('create students');

        return $dataTable
            ->with([
                'student' => $student->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'period' => $request->period,
            ])
            ->render('content.account-manager.students.activities-table');
    }

    public function reEvaluateProgressTest(User $student, Request $request)
    {
        return $this->reEvaluateProgress($request);
    }

    protected function updateQuizAttemptsWithActivity(int $student_id)
    {
        $quizAttempts = QuizAttempt::with('evaluation')
            ->selectRaw('quiz_attempts.*') // , quiz_attempts.user_id as 'quser_id',student_activities.user_id as 'suser_id',student_activities.activity_details as 'activity_details',student_activities.id as 'sid'" )
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
                $quizAttempt = QuizAttempt::where('id', $attempt->id)->first();
                $evaluation = $quizAttempt->evaluation()?->latest()->first();
                $evaluatorID = !empty($evaluation)
                    ? intval($evaluation->evaluator_id)
                    : null;
                $accessor = !empty($evaluatorID)
                    ? User::find($evaluatorID)
                    : null;
                $accessed_on = !empty($evaluation)
                    ? $evaluation->getRawOriginal('updated_at')
                    : null;
                $activityData = [
                    'activity_on' => $quizAttempt->getRawOriginal('updated_at'),
                    'student' => $quizAttempt->user_id,
                    'status' => $quizAttempt->status,
                    'user_id' => !empty($accessor) ? $accessor->id : null,
                    'accessor_id' => !empty($accessor) ? $accessor->id : null,
                    'accessor_role' => !empty($accessor)
                        ? $accessor->roleName()
                        : null,
                    'accessed_at' => !empty($accessed_on) ? $accessed_on : null,
                    'activity_by__at' => !empty($accessed_on)
                        ? $accessed_on
                        : null,
                    'activity_by_id' => !empty($accessor)
                        ? $accessor->id
                        : null,
                    'activity_by_role' => !empty($accessor)
                        ? $accessor->roleName()
                        : null,
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
            //            \Log::info( "Updated assessments for STUDENT {$student_id}->{$count}" );
        }
    }

    public function issueCertificate(Request $request)
    {
        if (
            auth()
                ->user()
                ->can('issue certificate')
        ) {
            $option = [
                'user_id' => intval($request->student),
                'course_id' => intval($request->course),
                'next_course_id' => intval($request->next_course_id),
            ];

            $studentEnrolments = StudentCourseEnrolment::where(
                'user_id',
                $option['user_id']
            )
                ->whereIn('course_id', [
                    $option['course_id'],
                    $option['next_course_id'],
                ])
                ->get();
            //            dd($studentEnrolments, $option);
            if (!empty($studentEnrolments)) {
                foreach ($studentEnrolments as $enrolment) {
                    $enrolment->cert_issued = true;
                    $enrolment->cert_issued_on = $request->cert_issued_on
                        ? $request->cert_issued_on
                        : Carbon::now();
                    $enrolment->cert_issued_by = auth()->user()->id;
                    $enrolment->cert_details = [
                        'today' => Carbon::now(),
                    ];
                    $enrolment->status = 'COMPLETED';
                    $enrolment->save();

                    $adminReportService = new AdminReportService(
                        $option['user_id'],
                        $option['course_id']
                    );
                    $adminReportService->updateCourse($enrolment->course);
                }

                return Helper::successResponse(
                    $studentEnrolments->toArray(),
                    'Successfully certificate issued'
                );
            }

            return Helper::errorResponse('Record Not found', 404);
        }

        return Helper::errorResponse('You are not meant to be here.', 403);
    }

    public function reEvaluateProgress(Request $request)
    {
        if (
            auth()
                ->user()
                ->can('update student progress')
        ) {
            $option = [
                'user_id' => intval($request->student),
                'course_id' => intval($request->course),
            ];
            //            dump($option);
            $this->updateQuizAttemptsWithActivity($option['user_id']);
            $enrolment = StudentCourseEnrolment::where(
                'user_id',
                $option['user_id']
            )
                ->where('course_id', $option['course_id'])
                ->first();
            $isMainCourse =
                $enrolment->course->is_main_course ||
                !\Str::contains(
                    \Str::lower($enrolment->course->title),
                    'emester 2'
                );

            CourseProgressService::updateStudentCourseStats(
                $enrolment,
                $isMainCourse
            );
            $progress = CourseProgress::where('user_id', $option['user_id'])
                ->where('course_id', $option['course_id'])
                ->first();
            //            dump('Progress found', $progress);
            if (!empty($progress)) {
                $progressDetails = $progress->details?->toArray();
                if (empty($progressDetails)) {
                    $progressDetails = CourseProgressService::populateProgress(
                        $option['course_id']
                    );
                }
                $details = CourseProgressService::reEvaluateProgress(
                    $option['user_id'],
                    $progressDetails
                );
                $progress->details = $details;
                $progress->percentage = CourseProgressService::getTotalCounts(
                    $option['user_id'],
                    $details
                );
                $progress->save();
                $progress->refresh();
                //                dump('progress updated');
                //                Helper::debug($progress->toArray(),'dd');

                // AdminReport
                $data = [];
                $student = User::find($option['user_id']);
                $adminReportService = new AdminReportService(
                    $option['user_id'],
                    $option['course_id']
                );
                $data = $adminReportService->prepareData($student);
                $adminReportService->getCourseData($progress, $data);
                $adminReportService->update($data);

                //                dd('admin report updated', $data);
                return Helper::successResponse(
                    $progress->toArray(),
                    'Successfully re-evaluated'
                );
            }

            //            dd('Progress not found');
            return Helper::errorResponse('Record Not found', 404);
        }

        return Helper::errorResponse('You are not meant to be here.', 403);
    }

    public function resetProgress(Request $request)
    {
        if (
            auth()->user()->email === 'mohsin@inceptionsol.com' &&
            strtolower(env('APP_ENV')) === 'local'
        ) {
            // Reset Course Progress
            $newProgress = CourseProgressService::populateProgress(
                $request->course
            );
            $newPercentage = CourseProgressService::getTotalCounts(
                $request->student,
                $newProgress
            );

            $courseProgress = CourseProgress::where(
                'user_id',
                $request->student
            )
                ->where('course_id', $request->course)
                ->firstOrFail();
            $courseProgress->details = $newProgress;
            $courseProgress->percentage = $newPercentage;
            $courseProgress->save();

            $quizAttempt = QuizAttempt::where(
                'user_id',
                $request->student
            )->where('course_id', $request->course);

            // Reset Feedbacks
            $quizAttemptIds = $quizAttempt->get()->pluck('id');
            Feedback::where('attachable_type', QuizAttempt::class)
                ->whereIn('attachable_id', $quizAttemptIds)
                ->delete();

            // Reset Evaluation
            Evaluation::where('evaluable_type', QuizAttempt::class)
                ->whereIn('evaluable_id', $quizAttemptIds)
                ->delete();

            // Reset Quiz Attempts
            $quizAttempt->delete();

            // Reset Admin Report
            $adminReport = AdminReport::where('student_id', $request->student)
                ->where('course_id', $request->course)
                ->firstOrFail();
            $adminReport->course_status = 'NOT STARTED';
            $adminReport->student_course_progress = [];
            $adminReport->save();

            // Reset Student Activities
            StudentActivity::where('user_id', $request->student)
                ->where('course_id', $request->course)
                ->delete();

            return Helper::successResponse([], 'Successfully Reset');
        }

        return Helper::errorResponse('You are not meant to be here.', 403);
    }

    public function cleanStudent(Request $request, User $student)
    {
        $student_id = $student->id;
        $attempts = QuizAttempt::where('user_id', $student_id)
            ->orderBy('attempt', 'DESC')
            ->get();

        $cleanedAttempts = [];
        foreach ($attempts as $attempt) {
            $cleanedAttempts = $this->clearNotifications($attempt, $student_id);
        }

        return $cleanedAttempts;
    }

    protected function clearNotifications(
        QuizAttempt $attempt,
        mixed $student_id
    ) {
        $notifications = [];
        $hasQuiz = Quiz::where('id', $attempt->quiz_id)->exists();

        if (
            $attempt->system_result === 'MARKED' ||
            $attempt->status === 'SATISFACTORY' ||
            !$hasQuiz
        ) {
            $query = User::find($student_id)
                ->notifications()
                ->where('type', AssessmentReturned::class)
                ->whereRaw(
                    "notifications.data LIKE '%assessment__{$attempt->id}%'"
                );
            if ($query->count() > 0) {
                $notifications['AssessmentReturned'][] = $query->delete();
            }
        }

        return $notifications;
    }

    public function editEnrolment(Request $request, User $student)
    {
        return redirect()->route('frontend.onboard.edit', [
            'step' => 1,
            'resumed' => 1,
            'student' => $student,
        ]);
    }

    public function updateEnrolment(Request $request, User $student)
    {
    }

    public function skipLLND(User $student)
    {
        $this->authorize('manage students');

        // Only allow Root users to skip LLND
        if (!auth()->user()->isRoot()) {
            abort(403, 'Only Root users can skip LLND.');
        }

        // Get the LLND quiz
        $llndQuiz = Quiz::where('is_lln', true)->first();

        if (!$llndQuiz) {
            return redirect()
                ->route('account_manager.students.show', $student)
                ->with('error', 'LLND quiz not found.');
        }

        // Check if student already has a completed LLND attempt
        $existingAttempt = QuizAttempt::where('user_id', $student->id)
            ->where('quiz_id', $llndQuiz->id)
            ->where('system_result', 'COMPLETED')
            ->where('status', 'SATISFACTORY')
            ->first();

        if ($existingAttempt) {
            return redirect()
                ->route('account_manager.students.show', $student)
                ->with('info', 'Student already has a completed LLND attempt.');
        }

        // Get student's main course for LLND association
        $mainCourse = StudentCourseEnrolment::where('user_id', $student->id)
            ->where('is_main_course', true)
            ->where('status', '!=', 'DELIST')
            ->whereHas('course', function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->first();

        if (!$mainCourse) {
            return redirect()
                ->route('account_manager.students.show', $student)
                ->with('error', 'Student must have a main course enrolled to skip LLND.');
        }

        // Get LLND course ID from config
        $llndCourseId = config('lln.course_id');
        $llndLessonId = config('lln.lesson_id');
        $llndTopicId = config('lln.topic_id');

        // Use quiz's lesson_id and topic_id if available, otherwise use config
        $lessonId = $llndQuiz->lesson_id ?? $llndLessonId;
        $topicId = $llndQuiz->topic_id ?? $llndTopicId;

        if (!$lessonId) {
            return redirect()
                ->route('account_manager.students.show', $student)
                ->with('error', 'LLND lesson ID not found. Please ensure LLND content is properly configured.');
        }

        // Create a completed LLND quiz attempt
        $quizAttempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => 11111,
            'course_id' => $llndCourseId ?? $mainCourse->course_id,
            'lesson_id' => $lessonId,
            'topic_id' => $topicId,
            'questions' => ['Skipped LLND'],
            'submitted_answers' => [],
            'attempt' => 1,
            'system_result' => 'COMPLETED',
            'status' => 'SATISFACTORY',
            'submitted_at' => Carbon::now(),
            'user_ip' => request()->ip(),
        ]);

        // Update enrollment stats if they exist
        if ($mainCourse && $mainCourse->enrolmentStats) {
            $mainCourse->enrolmentStats->update([
                'pre_course_attempt_id' => $quizAttempt->id,
            ]);
        }

        // Log activity
        activity('user_status')
            ->event('LLND_SKIPPED')
            ->causedBy(auth()->user())
            ->performedOn($student)
            ->withProperties([
                'user_id' => $student->id,
                'quiz_attempt_id' => $quizAttempt->id,
                'by' => auth()->user()->id,
            ])
            ->log('LLND skipped for student by ' . auth()->user()->name);

        return redirect()
            ->route('account_manager.students.show', $student)
            ->with('success', 'LLND has been skipped for ' . $student->name . '. A completed LLND quiz attempt has been created.');
    }
}
