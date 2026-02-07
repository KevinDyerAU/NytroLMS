<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Country;
use App\Models\Document;
use App\Models\Enrolment;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Models\UserDetail;
use App\Rules\YesWithoutAll;
use App\Services\AdminReportService;
use App\Services\StudentActivityService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class EnrolmentController extends Controller
{
    public StudentActivityService $activityService;

    public function __construct(StudentActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function create(Request $request, $step = 1, $resumed = 0)
    {
        //        $request->session()->flush();
        //        $this->authorize('create',Enrolment::class);
        $step = intval($step);
        $validStep = $this->isValidStep($request, $step);
        if ($validStep !== $step && $validStep > 0 && $resumed === 0) {
            return redirect()->route('frontend.onboard.create', ['step' => $validStep, 'resumed' => 1])->with('info', 'Continue Enrolment Process at Step#'.$validStep);
        }
        if ($this->nextStep($step) === 0) {
            return redirect()->route('frontend.dashboard')->with('success', 'You have successfully enrolled.');
        }
        $pageConfigs = [
            'showMenu' => false,
            'layoutWidth' => 'full',
        ];
        $breadcrumbs = [
            ['name' => 'Onboarding'],
        ];
        $currentUserId = auth()->user()->id;
        // Get active enrolment (check for 'onboard' or 'onboard{N}' keys)
        $enrolment = Enrolment::select('enrolment_value', 'enrolment_key')
            ->where('user_id', '=', $currentUserId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', true)
            ->first();
        $quiz = [];
        $ptrSkipped = false;
        $studentCourseEnrolment = null;

        if ($step === 5) {
            // Skip step 5 (PTR) for KnowledgeSpace branding
            if (env('SETTINGS_KEY') !== 'KeyInstitute') {
                return redirect()->route('frontend.onboard.create', ['step' => 6, 'resumed' => 1]);
            }

            // Check if this is a re-enrollment by looking for any completed enrollment (has step-6)
            // A re-enrollment means there's a COMPLETED enrollment (initial or previous re-enrollment)
            $isReEnrollment = false;
            $allEnrolments = Enrolment::where('user_id', $currentUserId)
                ->where(function($query) {
                    $query->where('enrolment_key', 'onboard')
                          ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                })
                ->get();

            foreach ($allEnrolments as $enrol) {
                if ($enrol->enrolment_value) {
                    $enrolValue = $enrol->enrolment_value->toArray();
                    // If any enrollment has step-6, this is a re-enrollment
                    if (isset($enrolValue['step-6'])) {
                        $isReEnrollment = true;
                        break;
                    }
                }
            }

            if ($isReEnrollment) {
                // Skip step 5 on re-enrollment - redirect to step 6
                return redirect()->route('frontend.onboard.create', ['step' => 6, 'resumed' => 1]);
            }

            // Get the student's main course for PTR association
            $studentCourseEnrolment = \App\Models\StudentCourseEnrolment::with('course')
                ->where('user_id', $currentUserId)
                ->where('is_main_course', true) // Only check main course for PTR exclusion
                ->where('status', '!=', 'DELIST') // Exclude DELIST courses
                ->whereHas('course', function ($q) {
                    $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
                })
                ->first();

            if ($studentCourseEnrolment && $studentCourseEnrolment->course) {
                // Check if PTR should be excluded based on course category
                $ptrSkipped = \App\Helpers\Helper::isPTRExcluded($studentCourseEnrolment->course->category);

                if ($ptrSkipped) {
                    // If PTR is excluded, redirect to step 6 (skip step 5 entirely)
                    return redirect()->route('frontend.onboard.create', ['step' => 6, 'resumed' => 1]);
                }

                // If PTR is not excluded, show the quiz
                $data = Quiz::with(['questions'])
                            ->where('id', config('ptr.quiz_id'))
                            ->first();
                if (!empty($data)) {
                    $questions = $data->questions()->orderBy('order', 'ASC')->get();

                    // Use the student's enrolled course ID for PTR association
                    $ptrCourseId = $studentCourseEnrolment->course_id;

                    // Look for existing PTR attempt for this specific course
                    $last_attempt = QuizAttempt::where('quiz_id', $data->id)
                                               ->where('user_id', auth()->user()->id)
                                               ->where('course_id', $ptrCourseId)
                                               ->first();

                    // Helper::debug([
                    //     'ptrCourseId' => $ptrCourseId,
                    //     'studentCourseEnrolment_course_id' => $studentCourseEnrolment->course_id,
                    //     'ptrSkipped' => $ptrSkipped,
                    //     'last_attempt' => $last_attempt,
                    //     'last_attempt_system_result' => $last_attempt ? $last_attempt->system_result : 'null',
                    //     'last_attempt_status' => $last_attempt ? $last_attempt->status : 'null',
                    //     'last_attempt_submitted_answers' => $last_attempt ? $last_attempt->submitted_answers : 'null'
                    // ], 'dump', 'sharonw6');

                    $attempted_answers = [];
                    if (!empty($last_attempt->system_result) && $last_attempt->system_result === 'INPROGRESS') {
                        $attempted_answers = $last_attempt;
                    }
                    $attempted_answersO = $attempted_answers;
                    $attempted_answers = !empty($attempted_answersO) ? $attempted_answersO->submitted_answers->toArray() : [];

                    $answer_types = $data->questions->pluck('answer_type', 'id')->toArray();

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
                    //                dd($this->nextQuestionStep( $data, $attempted_answersO ));
                    $quiz = [
                        'data' => $data,
                        'attempted_answers' => $attempted_answers,
                        'attempted_answersO' => $attempted_answersO,
                        'submitted_answers' => !empty($attempted_answers) ? array_keys($attempted_answers) : [],
                        'related' => [
                            'data' => $questions,
                        ],
                        'already_submitted' => !empty($last_attempt) && $last_attempt->status !== 'ATEMPTING' && $last_attempt->system_result !== 'INPROGRESS',
                        'is_ptr' => true,
                        'ptr_course_id' => $ptrCourseId, // Pass the course ID for PTR association
                    ];
                }
            }
        }

        // Use the PTR exclusion status we already determined above

        $steps = [
            1 => ['title' => 'Personal Info', 'subtitle' => 'Step #1', 'slug' => 'step-1'],
            2 => ['title' => 'Education Details', 'subtitle' => 'Step #2', 'slug' => 'step-2'],
            3 => ['title' => 'Employer Details', 'subtitle' => 'Step #3', 'slug' => 'step-3'],
            4 => ['title' => 'Requirements', 'subtitle' => 'Step #4', 'slug' => 'step-4'],
        ];

        // Determine if this is a re-enrollment by checking for any completed enrollment (has step-6)
        // A re-enrollment means there's a COMPLETED enrollment (initial or previous re-enrollment)
        $isReEnrollment = false;
        $allEnrolments = Enrolment::where('user_id', $currentUserId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->get();

        foreach ($allEnrolments as $enrol) {
            if ($enrol->enrolment_value) {
                $enrolValue = $enrol->enrolment_value->toArray();
                // If any enrollment has step-6, this is a re-enrollment
                if (isset($enrolValue['step-6'])) {
                    $isReEnrollment = true;
                    break;
                }
            }
        }

        // Always add step 5, but mark it as disabled on re-enrollment, PTR exclusion, or KnowledgeSpace branding
        $isKnowledgeSpace = env('SETTINGS_KEY') !== 'KeyInstitute';
        $step5Disabled = $isReEnrollment || $ptrSkipped || $isKnowledgeSpace;
        $steps[5] = [
            'title' => 'Pre-Training Review',
            'subtitle' => 'Step #5',
            'slug' => 'step-5',
            'disabled' => $step5Disabled
        ];

        $steps[6] = ['title' => 'Agreement', 'subtitle' => 'Step #6', 'slug' => 'step-6'];

        // Show verification message only for first enrollments (key 'onboard') that have been signed
        $requireAgreementRenewal = false;
        if ($enrolment && $enrolment->enrolment_key === 'onboard' && $enrolment->enrolment_value) {
            $enrolmentValue = $enrolment->enrolment_value->toArray();
            $signedOn = $enrolmentValue['step-6']['signed_on'] ?? $enrolmentValue['step-5']['signed_on'] ?? null;
            $requireAgreementRenewal = !empty($signedOn);
        }

        $data = [
            'title' => config('settings.site.institute_name', 'Key Institute'),
            'step' => $step,
            'steps' => $steps,
            'enrolment' => $enrolment?->enrolment_value->toArray(),
            'quiz' => $quiz,
            'next_step' => !empty($quiz) ? $this->nextQuestionStep($quiz['data'], $quiz['attempted_answersO']) : '',
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
            'isReEnrollment' => $isReEnrollment,
            'requireAgreementRenewal' => $requireAgreementRenewal, // Same as isReEnrollment for backwards compatibility
        ];

        // Helper::debug([$data, $quiz['attempted_answersO']], 'dd', 'sharonw6');
        //        ddd($data);
        return view('/frontend/content/onboard/index', $data);
    }

    public function edit(Request $request, User $student, $step = 1)
    {
        auth()->user()->can('update students');
        $step = intval($step);
        //        $validStep = $this->isValidStep($request, $step);
        session()->put('edit-enrolment', true);

        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Account Manager'],
            ['link' => route('account_manager.students.index'), 'name' => 'Students'],
            ['name' => 'Edit Enrollment'],
        ];
        $actionItems = [
            0 => ['link' => route('account_manager.students.show', $student), 'icon' => 'file-text', 'title' => 'View Student'],
            1 => ['link' => route('account_manager.students.create'), 'icon' => 'plus-square', 'title' => 'Add New Student'],
        ];
        $currentUserId = $student->id;
        // Get latest active enrolment (onboard, onboard2, onboard3, etc.)
        $enrolment = Enrolment::select('enrolment_value')
            ->where('user_id', '=', $currentUserId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->first();
        //        dd($enrolment);
        $data = [
            'title' => config('settings.site.institute_name', 'Key Institute'),
            'userId' => $currentUserId,
            'currentUser' => $student,
            'step' => $step,
            'steps' => [
                1 => ['title' => 'Personal Info', 'subtitle' => 'Step #1', 'slug' => 'step-1'],
                2 => ['title' => 'Education Details', 'subtitle' => 'Step #2', 'slug' => 'step-2'],
                3 => ['title' => 'Employer Details', 'subtitle' => 'Step #3', 'slug' => 'step-3'],
                4 => ['title' => 'Requirements', 'subtitle' => 'Step #4', 'slug' => 'step-4'],
                //                5 => [ 'title' => 'Pre-Training Review', 'subtitle' => 'Step #5', 'slug' => 'step-5' ],
                //                6 => [ 'title' => 'Agreement', 'subtitle' => 'Step #6', 'slug' => 'step-6' ],
            ],
            'enrolment' => $enrolment?->enrolment_value->toArray(),
            'breadcrumbs' => $breadcrumbs,
            'pageConfigs' => $pageConfigs,
            'actionItems' => $actionItems,
        ];

        return view('content.account-manager.students.edit-enrolment', $data);
    }

    public function isValidStep(Request $request, int $step)
    {
        //        $completedSteps = $request->session()->get('steps-completed');
        ////        ddd($completedSteps, $step);
        //        if (!empty($completedSteps) && !in_array($step, $completedSteps)) {
        //            ddd('Previous Step');
        //            return $this->previousStep($step);
        //        }

        // Check if PTR should be excluded for step 5
        if ($step === 5) {
            // Skip step 5 (PTR) for KnowledgeSpace branding
            if (env('SETTINGS_KEY') !== 'KeyInstitute') {
                return 6;
            }

            $currentUserId = auth()->user()->id;

            // Check if this is a re-enrollment by looking for any completed enrollment (has step-6)
            // A re-enrollment means there's a COMPLETED enrollment (initial or previous re-enrollment)
            $isReEnrollment = false;
            $allEnrolments = Enrolment::where('user_id', $currentUserId)
                ->where(function($query) {
                    $query->where('enrolment_key', 'onboard')
                          ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                })
                ->get();

            foreach ($allEnrolments as $enrol) {
                if ($enrol->enrolment_value) {
                    $enrolValue = $enrol->enrolment_value->toArray();
                    // If any enrollment has step-6, this is a re-enrollment
                    if (isset($enrolValue['step-6'])) {
                        $isReEnrollment = true;
                        break;
                    }
                }
            }

            if ($isReEnrollment) {
                // Skip step 5 on re-enrollment - redirect to step 6
                return 6;
            }

            $studentCourseEnrolment = \App\Models\StudentCourseEnrolment::with('course')
                ->where('user_id', $currentUserId)
                ->where('is_main_course', true) // Only check main course for PTR exclusion
                ->where('status', '!=', 'DELIST') // Exclude DELIST courses
                ->where('registered_on_create', 1) // Only students registered during onboarding
                ->first();

            if ($studentCourseEnrolment && $studentCourseEnrolment->course) {
                // \Log::info('PTR Debug - isValidStep - Course category:', [
                //     'course_id' => $studentCourseEnrolment->course->id,
                //     'course_category' => $studentCourseEnrolment->course->category,
                //     'excluded_categories' => config('ptr.excluded_categories')
                // ]);
                $ptrSkipped = \App\Helpers\Helper::isPTRExcluded($studentCourseEnrolment->course->category);
                // \Log::info('PTR Debug - isValidStep - PTR exclusion result:', [
                //     'ptrSkipped' => $ptrSkipped
                // ]);
                if ($ptrSkipped) {
                    // If PTR is excluded, redirect to step 6
                    return 6;
                }
            }
        }

        // Get active enrolment
        $enrolment = Enrolment::select('enrolment_value')
            ->where('user_id', '=', auth()->user()->id)
            ->where('enrolment_key', 'onboard')
            ->where('is_active', true)
            ->first();
        if (!empty($enrolment)) {
            $keys = $enrolment->enrolment_value->keys()->toArray();
            $max_step = intval(substr(max($keys), strlen('step-')));
            if ($max_step >= 6) {
                return 0;
            }
            if (in_array('step-'.$step, $keys)) {
                return $this->nextStep($max_step);
            }

            //            ddd('Not Found in DB');
            return $this->previousStep($max_step + 2);
        }

        //        else if (empty($completedSteps) && $step > 1) {
        //            ddd('No Step Completed');
        //            return $this->previousStep(1);
        //        }
        //
        //        $request->session()->put('step', $step);
        return 1;
    }

    public function update(Request $request, User $student, int $step, $resumed = 1)
    {
        //        dump($request->all());
        $request->request->add(['student_id' => $student->id]);
        if (!session()->has('edit-enrolment')) {
            session()->put('edit-enrolment', true);
        }

        //        dd($request->all(), \Str::contains(url()->current(), 'account-manager/students'));
        //        session()->put('student_id', $student->id);
        return $this->store($request, $step, intval($resumed));
    }

    public function store(Request $request, int $step, $resumed = 0)
    {
        $validated = $this->{'validateStep'.$step}($request);

        $currentUser = auth()->user()->id;
        if (session('edit-enrolment', false)) {
            $currentUser = $request->student_id ?? null;
        }
        //        dd($request->all(), $currentUser, session()->all());
        if (empty($currentUser)) {
            abort(422, 'Unable to modify user enrolment');
        }

        // Prevent duplicate step-6 submissions for new enrollments
        if ($step === 6 && !session('edit-enrolment', false)) {
            $existingActiveEnrolment = Enrolment::where('user_id', $currentUser)
                ->where(function($query) {
                    $query->where('enrolment_key', 'onboard')
                          ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                })
                ->where('is_active', true)
                ->first();

            // If an active enrollment exists with step-6, this is a duplicate submission
            if ($existingActiveEnrolment && $existingActiveEnrolment->enrolment_value) {
                $enrolmentData = $existingActiveEnrolment->enrolment_value->toArray();
                if (isset($enrolmentData['step-6'])) {
                    // Already completed - redirect to dashboard to prevent duplicate
                    return redirect()->route('frontend.dashboard')->with('success', 'You have successfully enrolled.');
                }
            }
        }

        //        dump($validated);
        if ($step === 4 && !empty($validated['document1'])) {
            $validated['document1'] = $this->uploadFile($currentUser, $validated['document1_type'], $request->file('document1'));
        }
        if ($step === 4 && !empty($validated['document2'])) {
            $validated['document2'] = $this->uploadFile($currentUser, $validated['document2_type'], $request->file('document2'));
        }
        $enrolment_value = $enrolment_record = ['step-'.$step => $validated];

        // Check for re-enrolment
        $enrolmentKey = 'onboard';
        $isReEnrollment = false;
        $originalEnrolmentData = null;
        $isEditing = session('edit-enrolment', false);

        // Check if this is a re-enrolment by looking for existing active enrolment
        $existingActiveEnrolment = Enrolment::where('user_id', $currentUser)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', true)
            ->first();

        // Only treat as re-enrollment if the existing active enrolment is COMPLETE (has step-6)
        // AND we're NOT editing (editing should always update the existing active enrollment)
        // If incomplete, it's just a normal enrollment in progress that should be updated
        if ($existingActiveEnrolment && !$isEditing) {
            $existingEnrolmentData = $existingActiveEnrolment->enrolment_value->toArray();
            // Check if the enrolment is complete (has step-6)
            if (isset($existingEnrolmentData['step-6'])) {
                $isReEnrollment = true;
                // Store original enrolment data in session for comparison at step 6
                if (!session()->has('original_enrolment_data')) {
                    session()->put('original_enrolment_data', $existingEnrolmentData);
                }
                $originalEnrolmentData = session('original_enrolment_data');
            }
        }

        // Determine the next enrolment key for re-enrolments
        $activeEnrolmentKey = $enrolmentKey;
        if ($isReEnrollment) {
            // Use the key from session if it exists (to ensure all steps use the same key)
            if (session()->has('re_enrollment_key')) {
                $activeEnrolmentKey = session('re_enrollment_key');
            } else {
                // Get all existing enrolment keys for this user (both active and inactive)
                $existingKeys = Enrolment::where('user_id', $currentUser)
                    ->where(function($query) {
                        $query->where('enrolment_key', 'onboard')
                              ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                    })
                    ->pluck('enrolment_key')
                    ->toArray();

                // Find the highest number
                $maxNumber = 0;
                foreach ($existingKeys as $key) {
                    if ($key === 'onboard') {
                        $maxNumber = max($maxNumber, 1);
                    } elseif (preg_match('/^onboard(\d+)$/', $key, $matches)) {
                        $maxNumber = max($maxNumber, (int)$matches[1]);
                    }
                }

                // Next enrolment key will be 'onboard2', 'onboard3', etc.
                if ($maxNumber === 0) {
                    // First re-enrolment (after 'onboard')
                    $activeEnrolmentKey = 'onboard2';
                } else {
                    $activeEnrolmentKey = 'onboard' . ($maxNumber + 1);
                }

                // Store in session for subsequent steps
                session()->put('re_enrollment_key', $activeEnrolmentKey);
            }
        } elseif ($existingActiveEnrolment) {
            // For normal enrollment, use the existing active enrolment's key
            // This ensures we continue updating the same record across all steps
            $activeEnrolmentKey = $existingActiveEnrolment->enrolment_key;
        }

        // On re-enrollment, ALWAYS create a NEW enrollment record (never edit the original)
        // Otherwise, work with the active enrollment
        if ($isReEnrollment) {
            // Look for existing inactive enrollment with the incremented key (the new re-enrollment being built)
            $newEnrolmentEntry = Enrolment::select('enrolment_value', 'id', 'enrolment_key')
                ->where('user_id', '=', $currentUser)
                ->where('enrolment_key', '=', $activeEnrolmentKey)
                ->where('is_active', false)
                ->first();

            if ($newEnrolmentEntry) {
                // Continue building the new enrollment - merge with existing data
                $filteredEntry = $newEnrolmentEntry->enrolment_value->toArray();
                $enrolment_value = array_merge($filteredEntry, $enrolment_record);
            } else {
                // Start a completely new enrollment - inherit step-5 from original if not saving step-5
                if ($step !== 5 && $existingActiveEnrolment) {
                    $previousEnrolmentValue = $existingActiveEnrolment->enrolment_value->toArray();
                    if (isset($previousEnrolmentValue['step-5'])) {
                        // Inherit step-5 from original enrollment
                        $enrolment_value['step-5'] = $previousEnrolmentValue['step-5'];
                    }
                }
            }

            // On re-enrollment, ensure step-5 is inherited from original enrollment (not overwritten)
            if ($step !== 5 && $existingActiveEnrolment && !isset($enrolment_value['step-5'])) {
                $previousEnrolmentValue = $existingActiveEnrolment->enrolment_value->toArray();
                if (isset($previousEnrolmentValue['step-5'])) {
                    $enrolment_value['step-5'] = $previousEnrolmentValue['step-5'];
                }
            }

            // When saving step-6, only remove old step-5 data if it contains agreement data (old format)
            // Keep step-5 if it contains PTR data (inherited from previous enrollment)
            if ($step === 6 && isset($enrolment_value['step-5'])) {
                // Check if step-5 contains old agreement data (backwards compatibility)
                // Old format: step-5 had 'agreement' and 'signed_on' fields
                // New format: step-5 has PTR data like 'ptr_excluded', 'quiz_completed', 'completed_at'
                if (isset($enrolment_value['step-5']['agreement']) ||
                    (isset($enrolment_value['step-5']['signed_on']) && !isset($enrolment_value['step-5']['ptr_excluded']) && !isset($enrolment_value['step-5']['quiz_completed']))) {
                    // This is old agreement data in step-5, remove it
                    unset($enrolment_value['step-5']);
                }
                // Otherwise, keep step-5 (it contains PTR data from previous enrollment)
            }

            // Use updateOrCreate to ensure we always work with the same record for all steps
            $enrolment = Enrolment::updateOrCreate(
                ['user_id' => $currentUser, 'enrolment_key' => $activeEnrolmentKey, 'is_active' => false],
                ['enrolment_value' => $enrolment_value, 'is_active' => false]
            );
        } else {
            // Normal enrollment (not re-enrollment) - work with active enrollment
            if ($isEditing) {
                // When editing, ALWAYS find and update the existing active enrollment
                // Never create a new enrollment when editing
                if (!$existingActiveEnrolment) {
                    // If not found by initial query, try to find it by any active enrollment key
                    $existingActiveEnrolment = Enrolment::where('user_id', $currentUser)
                        ->where(function($query) {
                            $query->where('enrolment_key', 'onboard')
                                  ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                        })
                        ->where('is_active', true)
                        ->first();
                }

                if ($existingActiveEnrolment) {
                    // Get all existing steps from the active enrollment
                    $filteredEntry = $existingActiveEnrolment->enrolment_value->toArray();
                    // Merge with new step data - this preserves all existing steps and updates the current step
                    $enrolment_value = array_merge($filteredEntry, $enrolment_record);

                    // Update the existing active enrollment with all steps in one JSON file
                    $existingActiveEnrolment->enrolment_value = $enrolment_value;
                    $existingActiveEnrolment->is_active = true;
                    $existingActiveEnrolment->save();
                    $enrolment = $existingActiveEnrolment;
                } else {
                    // Should not happen when editing, but if no active enrollment found, abort
                    abort(422, 'No active enrollment found to edit');
                }
            } else {
                // For new enrollments (not editing), check for existing enrollment
                $entry = Enrolment::select('enrolment_value', 'id')
                    ->where('user_id', '=', $currentUser)
                    ->where('enrolment_key', '=', $activeEnrolmentKey)
                    ->where('is_active', true)
                    ->get();

                if ($entry->isNotEmpty()) {
                    $filteredEntry = $entry->first()->enrolment_value->toArray();
                    $enrolment_value = array_merge($filteredEntry, $enrolment_record);
                }

                // Find existing active enrollment with this key
                $existingEnrolment = Enrolment::where('user_id', $currentUser)
                    ->where('enrolment_key', $activeEnrolmentKey)
                    ->where('is_active', true)
                    ->first();

                if ($existingEnrolment) {
                    // Update existing record
                    $existingEnrolment->enrolment_value = $enrolment_value;
                    $existingEnrolment->is_active = true;
                    $existingEnrolment->save();
                    $enrolment = $existingEnrolment;
                } else {
                    // Deactivate any other active enrollments with different keys for this user
                    Enrolment::where('user_id', $currentUser)
                        ->where('enrolment_key', '!=', $activeEnrolmentKey)
                        ->where(function($query) {
                            $query->where('enrolment_key', 'onboard')
                                  ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                        })
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    // Create new active enrollment (only for new enrollments, not when editing)
                    $enrolment = Enrolment::create([
                        'user_id' => $currentUser,
                        'enrolment_key' => $activeEnrolmentKey,
                        'enrolment_value' => $enrolment_value,
                        'is_active' => true
                    ]);
                }
            }
        }

        $user = User::find($currentUser);

        if (!empty($user->password_change_at)) {
            if (empty($user->detail->first_login)) {
                $user->detail->first_login = $enrolment->created_at;
            }
            if (empty($user->detail->last_logged_in)) {
                $user->detail->last_logged_in = $enrolment->created_at;
            }
            $user->detail->save();
        }

        if ($step === 1 && isset($enrolment_value['step-1']['residence_address'])) {
            $user_detail = UserDetail::where('user_id', $currentUser)->first();
            if (!empty($enrolment_value['step-1']['residence_address']) && $enrolment_value['step-1']['residence_address'] !== $user_detail->address) {
                $user_detail->address = $enrolment_value['step-1']['residence_address'].' '.($enrolment_value['step-1']['residence_address_postcode'] ?? '');
            }
            $user_detail->save();
        }

        if (session('edit-enrolment', false)) {
            if ($step === 4) {
                $step = 5;
            }
        }

        if ($step === 6) {
            $user_detail = UserDetail::where('user_id', $currentUser)->first();
            //            ddd($user_detail);
            $user_detail->status = 'ONBOARDED';
            $user_detail->onboard_at = Carbon::now();
            $user_detail->save();

            $this->activityService->setActivity([
                'user_id' => $currentUser,
                'activity_event' => 'ENROLMENT',
                'activity_details' => [
                    'user_id' => $currentUser,
                    'status' => 'ONBOARDED',
                    'edit-enrolment' => Carbon::now(Helper::getTimeZone()),
                    'by' => auth()->user()->id,
                ],
            ], auth()->user());

            $student = User::find($currentUser);
            $adminReportService = new AdminReportService($currentUser, null);
            $adminReportService->update($adminReportService->prepareData($student));

            // Check if this is a re-enrollment and handle archiving
            $originalEnrolmentData = session('original_enrolment_data');
            if (!empty($originalEnrolmentData)) {
                // Get the complete enrolment data for comparison
                $completeEnrolmentData = $enrolment->enrolment_value->toArray();

                // Detect if any fields changed
                $changedFields = $this->detectEnrolmentChanges($originalEnrolmentData, $completeEnrolmentData);

                // Always archive the old enrolment on re-enrollment completion
                // Archive the old active enrolment (mark as inactive, preserve its actual data)
                $oldActiveEnrolment = Enrolment::where('user_id', $currentUser)
                    ->where(function($query) {
                        $query->where('enrolment_key', 'onboard')
                              ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                    })
                    ->where('is_active', true)
                    ->where('id', '!=', $enrolment->id) // Exclude the current enrollment being saved
                    ->first();

                if ($oldActiveEnrolment) {
                    // Preserve the actual enrolment_value from the database (includes signed_on)
                    // Don't overwrite with session data - just mark as inactive
                    $oldActiveEnrolment->is_active = false;
                    $oldActiveEnrolment->save();
                }
                // Removed redundant else block that created a duplicate 'onboard' entry if $oldActiveEnrolment wasn't found.
                // The logic handles archiving the *existing* active enrolment. If one wasn't found (which shouldn't happen in a re-enrolment flow initiated from an active one),
                // we shouldn't arbitrarily create a new historical record, as that leads to duplicates.

                // Update current enrolment to use the incremented key and mark as active
                // Refresh the enrollment to ensure we have the latest data
                $enrolment->refresh();
                // Use the incremented enrolment key (e.g., 'onboard2', 'onboard3', etc.)
                $enrolment->enrolment_key = $activeEnrolmentKey;
                $enrolment->is_active = true;
                $enrolment->save();

                // Ensure no other enrollments are active for this user
                Enrolment::where('user_id', $currentUser)
                    ->where(function($query) {
                        $query->where('enrolment_key', 'onboard')
                              ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                    })
                    ->where('id', '!=', $enrolment->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                // Clear the re-enrollment key from session
                session()->forget('re_enrollment_key');
                session()->forget('original_enrolment_data');
            }

            // Create appropriate note for enrolment
            // Only create re-enrollment note if we have original data to compare (true re-enrollment)
            if (!empty($originalEnrolmentData)) {
                // Re-enrollment: create detailed note with change detection
                $completeEnrolmentData = $enrolment->enrolment_value->toArray();
                $this->createReEnrollmentNote($currentUser, $student, $completeEnrolmentData, $originalEnrolmentData);
            }

            // Generate Agreement PDF
            $studentName = $student->first_name.' '.$student->last_name;
            $Agreedate = Carbon::now(Helper::getTimeZone())->format('d-m-Y');
            $AgreedateFileName = Carbon::now(Helper::getTimeZone())->format('d/m/y');
            $viewAgreement = 'frontend.content.onboard.agreement.';
            $viewAgreement .= \Str::lower(env('SETTINGS_KEY', 'keyinstitute'));

            // Check if this is a re-enrollment by checking for existing agreement documents
            $isReEnrollment = Document::where('user_id', $currentUser)
                ->where('file_path', 'LIKE', '%/agreement/%')
                ->exists();

            $pdfHtml = view($viewAgreement, [
                'studentName' => $studentName,
                'date' => $Agreedate,
            ])->render();
            $pdf = PDF::loadHTML($pdfHtml);

            // Create unique filename with timestamp to avoid overwriting previous agreements
            $timestamp = Carbon::now(Helper::getTimeZone())->format('Y-m-d_His');
            $pdfPath = "public/user/{$currentUser}/agreement/agreement_{$timestamp}.pdf";
            $dir = dirname($pdfPath);
            Helper::ensureDirectoryWithPermissions($dir);
            $saved = Storage::put($pdfPath, $pdf->output());

            // Only create a Document record if the file was saved successfully
            if ($saved && Storage::exists($pdfPath)) {
                Document::create([
                    'user_id' => $currentUser,
                    'file_name' => ($isReEnrollment ? 'Agreement (Renewal)' : 'Agreement') . ' - ' . $AgreedateFileName,
                    'file_size' => Storage::size($pdfPath),
                    'file_path' => $pdfPath, // Use the same path as in Storage::put
                    'file_uuid' => \Str::uuid()->toString(),
                ]);

                // Add a note for the student
                $noteMessage = $isReEnrollment
                    ? "Student Re-signed and Agreed to T &amp; C  \"Applicant Declarations and Consent\" on {$Agreedate}"
                    : "Student Agreed to T &amp; C  \"Applicant Declarations and Consent\" on {$Agreedate}";

                $note = Note::create([
                    'user_id' => 0,
                    'subject_type' => User::class,
                    'subject_id' => $student->id,
                    'note_body' => "<p>{$noteMessage}</p>",
                    'data' => json_encode([
                        'type' => $isReEnrollment ? 'ONBOARD AGREEMENT RE-SIGNED' : 'ONBOARD AGREEMENT SIGNED',
                        'message' => $noteMessage,
                        'pdf_path' => $pdfPath,
                        'date' => $Agreedate,
                    ]),
                ]);
                if (!empty($note)) {
                    $this->activityService->setActivity([
                        'user_id' => $currentUser,
                        'activity_event' => 'AGREEMENT_PDF_GENERATED',
                        'activity_details' => [
                            'message' => 'Agreement PDF has been generated and stored.',
                            'pdf_path' => $pdfPath,
                            'date' => $Agreedate,
                        ],
                    ], auth()->user());
                }
            }
        }
        $request->session()->push('steps-completed', $step);
        $next = $this->nextStep($step);
        if (!session('edit-enrolment', false)) {
            if ($next === 0) {
                return redirect()->route('frontend.dashboard')->with('success', 'You have successfully enrolled.');
            }

            return redirect()->route('frontend.onboard.create', ['step' => $next, 'resumed' => $resumed]);
        }
        $next = intval($step) + 1;
        if (session('edit-enrolment', false)) {
            if ($next >= 4) {
                $next = 4;
            }
            //            dd($step);
            if ($step === 5) {
                $params = ['student' => $currentUser, 'step' => $next];

                //        dd($params);
                return redirect()->route('account_manager.students.show', $currentUser)
                    ->with('success', 'Enrolment updated successfully!');
            }
        }
        $params = ['student' => $currentUser, 'step' => $next];

        //        dd($params);
        return redirect()->route('account_manager.students.edit-enrolment', $params);
    }

    private function previousStep(int $step)
    {
        $previousStep = $step - 1;
        if ($previousStep < 1) {
            $previousStep = 1;
        }

        return $previousStep;
        //        ddd($previousStep);
        //        return redirect()->route('frontend.onboard.create', ['step' => $previousStep]);//->with('info', 'Continue Enrolment Process at Step#' . $previousStep);
    }

    private function nextStep(int $step)
    {
        if ($step > 6) {
            return 0;
            //            return redirect()->route('frontend.dashboard');//->with('success', 'You have successfully enrolled.');
        }
        $nextStep = $step + 1;

        // Skip step 5 on re-enrollment - go directly to step 6
        if ($nextStep === 5) {
            // Skip step 5 (PTR) for KnowledgeSpace branding
            if (env('SETTINGS_KEY') !== 'KeyInstitute') {
                $nextStep = 6; // Skip step 5, go to step 6
                return $nextStep;
            }

            $currentUserId = auth()->user()->id;

            // Check if this is a re-enrollment by looking for any completed enrollment (has step-6)
            // A re-enrollment means there's a COMPLETED enrollment (initial or previous re-enrollment)
            $isReEnrollment = false;
            $allEnrolments = Enrolment::where('user_id', $currentUserId)
                ->where(function($query) {
                    $query->where('enrolment_key', 'onboard')
                          ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
                })
                ->get();

            foreach ($allEnrolments as $enrol) {
                if ($enrol->enrolment_value) {
                    $enrolValue = $enrol->enrolment_value->toArray();
                    // If any enrollment has step-6, this is a re-enrollment
                    if (isset($enrolValue['step-6'])) {
                        $isReEnrollment = true;
                        break;
                    }
                }
            }

            if ($isReEnrollment) {
                $nextStep = 6; // Skip step 5, go to step 6
            }
        }

        return $nextStep;
        //        dump($nextStep);
        //        return redirect()->route('frontend.onboard.create', ['step' => $nextStep]);//->with('info', 'Continue Enrolment Process at Step#' . $nextStep);
    }

    private function nextQuestionStep(Quiz $quiz, $attempted_answers)
    {
        // Helper::debug([
        //     'quiz_id' => $quiz->id,
        //     'attempted_answers' => $attempted_answers,
        //     'attempted_answers_type' => gettype($attempted_answers)
        // ], 'dump', 'sharonw6');

        $questions = $quiz->questions()->orderBy('order')->get()->toArray();
        $result = 1;
        $qid = 0;
        if (empty($questions)) {
            return ['last' => 0, 'step' => 1, 'qid' => 0, 'last_question_id' => 0];
        }

        $last_question = end($questions);
        reset($questions);
        if (empty($attempted_answers)) {
            return ['last' => 0, 'step' => 1, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }
        $submittedAnswers = $attempted_answers->submitted_answers->toArray();
        if (empty($submittedAnswers)) {
            return ['last' => 0, 'step' => 1, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }

        $submittedAnswersKeys = array_keys($submittedAnswers);
        $questionIds = \Arr::pluck($questions, 'id');
        $remaining_questions = array_diff($questionIds, $submittedAnswersKeys);

        // Debug logging for PTR quiz navigation
        // Helper::debug(['PTR Quiz Debug - nextQuestionStep:' => [
        //     'user_id' => auth()->user()->id,
        //     'quiz_id' => $quiz->id,
        //     'total_questions' => count($questions),
        //     'submitted_answers_count' => count($submittedAnswers),
        //     'submitted_answers_keys' => $submittedAnswersKeys,
        //     'question_ids' => $questionIds,
        //     'remaining_questions' => $remaining_questions,
        //     'remaining_count' => count($remaining_questions),
        //     'condition_1' => count($remaining_questions) > 1,
        //     'condition_2' => (count($questions) - 1) === count($submittedAnswers),
        //     'condition_3' => count($questions) > count($submittedAnswers)
        // ]], 'dump', 'sharonw6');

        if (count($remaining_questions) > 1) {
            $keys_remaining_questions = array_keys($remaining_questions);
            $result = $keys_remaining_questions[1];
            // Helper::debug(['PTR Quiz Debug - Path 1: Multiple remaining questions' => ['result' => $result]], 'dump', 'sharonw6');
        } else {
            $result = $remaining_questions[end($questionIds)] ?? 0;
            // Helper::debug(['PTR Quiz Debug - Path 2: Single remaining question' => ['result' => $result]], 'dump', 'sharonw6');
        }

        if ((count($questions) - 1) === count($submittedAnswers)) {
            $question = end($questions);
            // For last question, step should be the last step number (1-based indexing)
            $lastStepIndex = count($questions);
            // Helper::debug(['PTR Quiz Debug - Path 3: Last question reached' => ['lastStepIndex' => $lastStepIndex]], 'dump', 'sharonw6');

            return ['last' => 1, 'step' => $lastStepIndex, 'qid' => $question['id'], 'last_question_id' => $last_question['id']];
        }

        if (count($questions) > count($submittedAnswers)) {
            // Bootstrap stepper uses 1-based indexing, so we need to add 1
            $nextStep = count($submittedAnswers) + 1; // This gives us 1-based step index
            $nextQid = $remaining_questions[$result] ?? 0;

            // Helper::debug(['PTR Quiz Debug - Path 4: Next step calculation' => [
            //     'next_step' => $nextStep,
            //     'next_qid' => $nextQid,
            //     'result' => $result,
            //     'submitted_count' => count($submittedAnswers),
            //     'total_questions' => count($questions)
            // ]], 'dump', 'sharonw6');

            $path4Result = ['last' => 0, 'step' => $nextStep, 'qid' => $nextQid, 'last_question_id' => end($questionIds)];

            // Helper::debug($path4Result, 'dump', 'sharonw6');
            return $path4Result;
        }

        // For the final case, use 1-based indexing
        $finalStepIndex = count($questions);
        // Helper::debug('PTR Quiz Debug - Path 5: Final case', ['finalStepIndex' => $finalStepIndex, 'result' => $result]);

        $finalResult = ['last' => 0, 'step' => $finalStepIndex, 'qid' => $remaining_questions[$result] ?? 0, 'last_question_id' => end($questionIds)];

        // Helper::debug($finalResult, 'dump', 'nextQuestionStep_final');
        return $finalResult;
    }

    private function nextStepProcess(Quiz $quiz, $attempted_answers)
    {
        $questions = $quiz->questions()->orderBy('order')->get()->toArray();
        $result = 1;
        $qid = 0;
        if (empty($questions)) {
            return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => count($questions)];
        }
        $last_question = end($questions);
        reset($questions);
        if (!$attempted_answers) {
            return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }
        $submittedAnswers = $attempted_answers->submitted_answers->toArray();
        if (empty($submittedAnswers)) {
            return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => $last_question['id']];
        }

        if ((count($questions) - 1) === count($submittedAnswers)) {
            $question = end($questions);

            return ['last' => 1, 'step' => count($questions), 'qid' => $question['id'], 'last_question_id' => $last_question['id']];
        }
        $index = 1;
        foreach ($questions as $question) {
            //            $index++;
            $qid = $question['id'];
            $submittedAnswersKeys = array_keys($submittedAnswers);
            $last_submitted = end($submittedAnswersKeys);
            reset($submittedAnswersKeys);

            //            dump($index, $last_submitted, $last_question['id'], $question['id'], $submittedAnswersKeys, in_array($question['id'], $submittedAnswersKeys));
            if (in_array($question['id'], $submittedAnswersKeys)) {
                if ($question['id'] !== $last_submitted) {
                    $index++;

                    continue;
                }
            }

            break;
        }
        $result = ++$index;

        //        dd($result);
        return ['last' => 0, 'step' => $result, 'qid' => $qid, 'last_question_id' => $last_question['id']];
    }

    private function validateStep1(Request $request)
    {
        //        $step = 1;
        //        if ($request->session()->get('step') !== $step) {
        //            return redirect()->route('frontend.onboard.create', ['step' => $step]);//->with('error', 'Invalid Request. Resubmit the form.');
        //        };
        $validated = $request->validate([
            'title' => 'required|alpha|max:55',
            'gender' => 'required',
            'dob' => 'required|date_format:Y-m-d|before:-15 years',
            'home_phone' => 'nullable|numeric',
            'mobile' => 'required|numeric',
            'birthplace' => ['required', 'regex:/^[\sa-zA-Z,]+/', 'max:255'],
            'emergency_contact_name' => ['required', 'regex:/^[\sa-zA-Z]+/', 'max:255'],
            'relationship_to_you' => 'required|alpha|max:255',
            'emergency_contact_number' => 'required|numeric',
            'residence_address' => 'required',
            'residence_address_postcode' => 'required',
            'postal_address' => 'required',
            'postal_address_postcode' => 'required',
            'country' => 'required|exists:countries,id',
            'language' => 'required',
            'language_other' => 'required_if:language,=,other',
            'english_proficiency' => 'required_if:language,=,other',
            'torres_island' => 'required',
            'has_disability' => 'required',
            'disabilities' => 'required_if:has_disability,=,yes',
            'need_assistance' => 'required_if:has_disability,=,yes',
            'industry1' => 'nullable|numeric',
            'industry2' => 'required_with:industry1|nullable|numeric',
            //            'industry2_other' => 'required_if:industry2,=,9',//other = 9
            'employment' => 'required|numeric',
        ], [
            'dob.before' => 'You must be 15 years or above',
        ]);
        $validated['residence_address'] = $request['residence_address'] ?? '';
        $validated['residence_address_postcode'] = $request['residence_address_postcode'] ?? '';
        $validated['postal_address'] = $request['postal_address'] ?? '';
        $validated['postal_address_postcode'] = $request['postal_address_postcode'] ?? '';
        $validated['dob'] = $request['dob'] ?? '';
        $validated['country'] = Country::where('id', intval($request['country']))->first()->name;
        $validated['language'] = ($request['language'] == 'en') ? 'English' : 'Other';
        $validated['torres_island'] = (!empty($request['torres_island']) ? config('onboarding.step1.torres_island')[$request['torres_island']] : '');
        $validated['disabilities'] = (!empty($request['disabilities']) ? config('onboarding.step1.disabilities')[$request['disabilities']] : '');
        $validated['need_assistance'] = (!empty($request['need_assistance']) ? $request['need_assistance'] : '');
        $validated['industry1'] = (!empty($request['industry1']) ? config('onboarding.step1.industry1')[$request['industry1']] : '');
        $validated['industry2'] = (!empty($request['industry2']) ? config('onboarding.step1.industry2')[$request['industry2']] : '');
        $validated['employment'] = (!empty($request['employment']) ? config('onboarding.step1.employment')[$request['employment']] : '');

        return $validated;
    }

    private function validateStep2(Request $request)
    {
        //        ddd($request);
        //        $step = 2;
        $validated = $request->validate([
            'school_level' => 'required|numeric',
            'secondary_level' => 'required|alpha',
            'additional_qualification' => ['required', 'alpha', new YesWithoutAll(
                'higher_degree',
                'advanced_diploma',
                'diploma',
                'certificate4',
                'certificate3',
                'certificate2',
                'certificate1',
                'certificate_any'
            )],
            'higher_degree' => 'nullable|numeric',
            'advanced_diploma' => 'nullable|numeric',
            'diploma' => 'nullable|numeric',
            'certificate4' => 'nullable|numeric',
            'certificate3' => 'nullable|numeric',
            'certificate2' => 'nullable|numeric',
            'certificate1' => 'nullable|numeric',
            'certificate_any' => 'nullable|numeric',
            'certificate_any_details' => 'required_if:certificate_any,>,1',
        ]);
        //        Helper::debug( $validated );

        $additional_qualification = $request['additional_qualification'] ?? 'no';

        $validated['school_level'] = (!empty($request['school_level']) ? config('onboarding.step2.school_level')[$request['school_level']] : '');
        $validated['secondary_level'] = (!empty($request['secondary_level']) ? config('onboarding.step2.secondary_level')[$request['secondary_level']] : '');
        $validated['additional_qualification'] = $additional_qualification;
        $validated['higher_degree'] = (!empty($request['higher_degree'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['higher_degree']] : '');
        $validated['advanced_diploma'] = (!empty($request['advanced_diploma'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['advanced_diploma']] : '');
        $validated['diploma'] = (!empty($request['diploma'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['diploma']] : '');
        $validated['certificate4'] = (!empty($request['certificate4'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['certificate4']] : '');
        $validated['certificate3'] = (!empty($request['certificate3'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['certificate3']] : '');
        $validated['certificate2'] = (!empty($request['certificate2'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['certificate2']] : '');
        $validated['certificate1'] = (!empty($request['certificate1'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['certificate1']] : '');
        $validated['certificate_any'] = (!empty($request['certificate_any'] && $additional_qualification !== 'no') ? config('onboarding.step2.education_from')[$request['certificate_any']] : '');
        $validated['certificate_any_details'] = (!empty($request['certificate_any_details'] && $additional_qualification !== 'no') ? $request['certificate_any_details'] : '');

        //        Helper::debug( $validated, 'dd' );

        return $validated;
    }

    private function validateStep3(Request $request)
    {
        //        $step = 3;
        $validated = $request->validate([
            'organization_name' => 'nullable|string|max:255',
            'your_position' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'street_address' => 'nullable|string',
            //            'suburb_locality' => 'nullable|regex:/[a-zA-Z\s]+/|max:255',
            //            'state_territory' => 'nullable|regex:/[a-zA-Z\s]+/|max:255',
            'postcode' => 'nullable|numeric',
            'telephone' => 'nullable|numeric',
            'fax' => 'nullable|numeric',
            'email' => 'nullable|email',
            'website' => 'nullable|url',
        ]);

        return $validated;
    }

    private function validateStep4(Request $request)
    {
        //        $step = 4;
        $validated = $request->validate([
            'study_reason' => 'required|numeric',
            'usi_number' => ['nullable', 'regex:/^[a-zA-Z0-9\s]+$/i'],
            'nominate_usi' => 'required|numeric',
            'document1_type' => 'required_if:usi_number,=,null|nullable',
            'document1' => 'required_if:usi_number,=,null|nullable|file|max:10000',
            'document2_type' => 'required_if:usi_number,=,null|nullable|different:document1_type',
            'document2' => 'required_if:usi_number,=,null|nullable|file|max:10000',
        ]);

        $validated['study_reason'] = (!empty($request['study_reason']) ? config('onboarding.step4.study_reason')[$request['study_reason']] : '');
        $validated['nominate_usi'] = (!empty($request['nominate_usi']) ? config('onboarding.step4.nominate_usi')[$request['nominate_usi']] : '');
        $validated['document1_type'] = (!empty($request['document1_type']) ? config('onboarding.step4.document_type')[$request['document1_type']] : '');
        $validated['document2_type'] = (!empty($request['document2_type']) ? config('onboarding.step4.document_type')[$request['document2_type']] : '');

        return $validated;
    }

    private function validateStep5(Request $request)
    {
        //        $step = 5;

        // Skip step 5 (PTR) validation for KnowledgeSpace branding
        if (env('SETTINGS_KEY') !== 'KeyInstitute') {
            return [
                'ptr_excluded' => true,
                'completed_at' => time()
            ];
        }

        // Check if PTR should be excluded based on course category
        $currentUserId = auth()->user()->id;
        $studentCourseEnrolment = \App\Models\StudentCourseEnrolment::with('course')
            ->where('user_id', $currentUserId)
            ->where('is_main_course', true) // Only check main course for PTR exclusion
            ->where('status', '!=', 'DELIST') // Exclude DELIST courses
            ->where('registered_on_create', 1) // Only students registered during onboarding
            ->first();

        $validated = [];
        $isValid = false;

        if ($studentCourseEnrolment && $studentCourseEnrolment->course) {
            // \Log::info('PTR Debug - validateStep5 - Course category:', [
            //     'course_id' => $studentCourseEnrolment->course->id,
            //     'course_category' => $studentCourseEnrolment->course->category,
            //     'excluded_categories' => config('ptr.excluded_categories')
            // ]);
            $ptrSkipped = \App\Helpers\Helper::isPTRExcluded($studentCourseEnrolment->course->category);
            // \Log::info('PTR Debug - validateStep5 - PTR exclusion result:', [
            //     'ptrSkipped' => $ptrSkipped
            // ]);
            if ($ptrSkipped) {
                // PTR is excluded for this course category, so step5 is automatically valid
                $isValid = true;
                $validated['ptr_excluded'] = true;
            }
        }

        // If PTR is not excluded, check if quiz has been completed for the student's main course
        // Reuse the $studentCourseEnrolment already fetched above
        if (!$isValid && $studentCourseEnrolment) {
            $ptrCourseId = $studentCourseEnrolment->course_id;
            $quiz_attempt = QuizAttempt::where('user_id', auth()->user()->id)
                                        ->where('quiz_id', config('ptr.quiz_id'))
                                        ->where('course_id', $ptrCourseId)
                                       ->where('system_result', '!=', 'INPROGRESS')
                                       ->where('status', '!=', 'ATTEMPTING')
                                       ->count();
            if (!empty($quiz_attempt) && $quiz_attempt > 0) {
                $isValid = true;
                $validated['quiz_completed'] = true;
            }
        }

        if (!$isValid) {
            abort(422, 'Pre-Training Review must be completed before proceeding.');
        }

        $validated['completed_at'] = time();
        return $validated;
    }

    private function validateStep6(Request $request)
    {
        //        $step = 6;
        $validated = $request->validate([
            'agreement' => 'required|alpha',
        ]);
        $validated['signed_on'] = Carbon::now(Helper::getTimeZone())->toIso8601String();

        return $validated;
    }

    private function uploadFile($currentUser, $type, UploadedFile $document)
    {
        $file_location = 'public/user/'.$currentUser.'/documents';
        Helper::ensureDirectoryWithPermissions($file_location);
        $path = $document->store($file_location);
        $newDocument = Document::create([
            'user_id' => $currentUser,
            'file_name' => $type,
            'file_size' => $document->getSize(),
            'file_path' => $path,
            'file_uuid' => \Str::uuid()->toString(),
        ]);

        return $newDocument->id;
    }

    private function createReEnrollmentNote($currentUser, User $student, array $newEnrolmentValue, ?array $originalEnrolmentData = null)
    {
        // Check if this is a re-enrollment - must have original data to compare
        if (empty($originalEnrolmentData)) {
            $originalEnrolmentData = session('original_enrolment_data');
        }

        // Only create note if we have original data to compare (true re-enrollment)
        if (empty($originalEnrolmentData)) {
            return;
        }

        // Compare old and new enrolment data to detect changes
        $changedFields = $this->detectEnrolmentChanges($originalEnrolmentData, $newEnrolmentValue);

        $studentName = $student->first_name . ' ' . $student->last_name;
        $reEnrollmentDate = Carbon::now(Helper::getTimeZone())->format('d/m/Y');

        // Only create "no changes" note if re-enrollment completed AND no changes detected
        if (empty($changedFields)) {
            // No changes made - only create note when re-enrollment is complete
            $noteMessage = "{$studentName} re-enrolled and made no changes to their application";

            Note::create([
                'user_id' => 0,
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'note_body' => "<p>{$noteMessage}</p>",
                'data' => json_encode([
                    'type' => 'RE-ENROLMENT',
                    'message' => $noteMessage,
                    'date' => $reEnrollmentDate,
                    'changed_fields' => [],
                ]),
            ]);
        } else {
            // Changes were made - create note with changed fields
            $count = count($changedFields);
            $changedFieldsList = implode(', ', $changedFields);
            $noteMessage = "{$studentName} re-enrolled and updated {$count} fields in the Enrolment- {$changedFieldsList}";

            Note::create([
                'user_id' => 0,
                'subject_type' => User::class,
                'subject_id' => $student->id,
                'note_body' => "<p>{$noteMessage}</p>",
                'data' => json_encode([
                    'type' => 'RE-ENROLMENT',
                    'message' => $noteMessage,
                    'date' => $reEnrollmentDate,
                    'changed_fields' => $changedFields,
                ]),
            ]);
        }

        // Clear the session data
        session()->forget('original_enrolment_data');
    }

    private function detectEnrolmentChanges(array $oldData, array $newData): array
    {
        $changedFields = [];
        $fieldLabels = $this->getFieldLabels();

        // Get all step keys from both old and new data
        $allStepKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        // Compare each step
        foreach ($allStepKeys as $stepKey) {
            $oldStepData = $oldData[$stepKey] ?? [];
            $newStepData = $newData[$stepKey] ?? [];

            if (!is_array($oldStepData) || !is_array($newStepData)) {
                continue;
            }

            // Get all field keys from both old and new step data
            $allFieldKeys = array_unique(array_merge(array_keys($oldStepData), array_keys($newStepData)));

            // Compare each field in the step
            foreach ($allFieldKeys as $fieldKey) {
                // Skip document IDs as they're handled separately
                // Skip procedural fields that change on every re-enrolment
                if (in_array($fieldKey, ['document1', 'document2', 'signed_on', 'agreement', 'completed_at', 'quiz_completed', 'ptr_excluded'])) {
                    continue;
                }

                $oldValue = $oldStepData[$fieldKey] ?? null;
                $newValue = $newStepData[$fieldKey] ?? null;

                // Check if field was added (didn't exist in old data)
                if (!isset($oldStepData[$fieldKey]) && isset($newStepData[$fieldKey])) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                    continue;
                }

                // Check if field was removed (existed in old but not in new)
                if (isset($oldStepData[$fieldKey]) && !isset($newStepData[$fieldKey])) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                    continue;
                }

                // Compare values (handle arrays and strings)
                if (is_array($oldValue) && is_array($newValue)) {
                    if (json_encode($oldValue) !== json_encode($newValue)) {
                        $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                        if (!in_array($fieldLabel, $changedFields)) {
                            $changedFields[] = $fieldLabel;
                        }
                    }
                } elseif ($oldValue !== $newValue) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                }
            }
        }

        return $changedFields;
    }

    private function getFieldLabels(): array
    {
        return [
            'step-1' => [
                'title' => 'Title',
                'gender' => 'Gender',
                'dob' => 'Date of Birth',
                'home_phone' => 'Home Phone',
                'mobile' => 'Mobile',
                'birthplace' => 'Birthplace',
                'emergency_contact_name' => 'Emergency Contact Name',
                'relationship_to_you' => 'Relationship',
                'emergency_contact_number' => 'Emergency Contact Number',
                'residence_address' => 'Residence Address',
                'residence_address_postcode' => 'Residence Postcode',
                'postal_address' => 'Postal Address',
                'postal_address_postcode' => 'Postal Postcode',
                'country' => 'Country',
                'language' => 'Language',
                'language_other' => 'Other Language',
                'english_proficiency' => 'English Proficiency',
                'torres_island' => 'Torres Strait Islander',
                'has_disability' => 'Has Disability',
                'disabilities' => 'Disabilities',
                'need_assistance' => 'Needs Assistance',
                'industry1' => 'Industry 1',
                'industry2' => 'Industry 2',
                'employment' => 'Employment Status',
            ],
            'step-2' => [
                'school_level' => 'School Level',
                'secondary_level' => 'Secondary Level',
                'additional_qualification' => 'Additional Qualification',
                'higher_degree' => 'Higher Degree',
                'advanced_diploma' => 'Advanced Diploma',
                'diploma' => 'Diploma',
                'certificate4' => 'Certificate IV',
                'certificate3' => 'Certificate III',
                'certificate2' => 'Certificate II',
                'certificate1' => 'Certificate I',
                'certificate_any' => 'Other Certificate',
                'certificate_any_details' => 'Certificate Details',
            ],
            'step-3' => [
                'organization_name' => 'Organization Name',
                'your_position' => 'Position',
                'supervisor_name' => 'Supervisor Name',
                'street_address' => 'Street Address',
                'postcode' => 'Postcode',
                'telephone' => 'Telephone',
                'fax' => 'Fax',
                'email' => 'Email',
                'website' => 'Website',
            ],
            'step-4' => [
                'study_reason' => 'Study Reason',
                'usi_number' => 'USI Number',
                'nominate_usi' => 'Nominate USI',
                'document1_type' => 'Document 1 Type',
                'document2_type' => 'Document 2 Type',
            ],
        ];
    }
}
