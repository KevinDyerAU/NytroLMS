@extends('layouts/contentLayoutMaster')

@section('title', \Str::title($student->name))

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/calendars/fullcalendar.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/file-uploaders/dropzone.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/app-calendar.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-file-uploader.css')) }}">
@endsection

@section('content')
    @if (auth()->user()->can('manage students'))
        @if ($student->isActive())
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Student: {{ $student->name }} is set Active.&nbsp;
                    <a href="{{ route('account_manager.students.deactivate', $student) }}" class="text-danger"> Click
                        here
                        to Deactivate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @else
            <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Student: {{ $student->name }} is set Inactive.&nbsp;
                    <a href="{{ route('account_manager.students.activate', $student) }}" class="text-success"> Click
                        here to
                        Activate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    @if (!empty($student->detail->onboard_at))
        @php
            $usi = $student->onboardDetailsArray('step-4.usi_number');
            $cleanUsi = !empty($usi) ? str_replace(' ', '', $usi) : '';
            $isTba = !empty($cleanUsi) && strtoupper($cleanUsi) === 'TBA';
            $isValidUsi = true;
            if (!empty($cleanUsi) && !$isTba) {
                $isValidLength = strlen($cleanUsi) === 10;
                $isValidUsi = $isValidLength;
            }
        @endphp
        @if($isTba)
            <div class="alert alert-warning alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <i data-lucide="alert-triangle" class="me-2"></i>
                    <span>USI is required. Please follow up</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @elseif(!$isValidUsi && !empty($cleanUsi))
            <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <i data-lucide="alert-triangle" class="me-2"></i>
                    <span>USI verification required: Please verify with the student.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    @if ($student->leaders()->count() < 1)
        <div class="alert alert-warning d-print-none" role="alert">
            <div class="alert-body">
                <span class="fw-bolder">Incomplete Profile</span>. Kindly assign <strong>Leader</strong>
                to this student profile, in order to assign course(s).
                <a href="{{ route('account_manager.students.edit', $student) }}">Click here</a>
            </div>
        </div>
    @endif

    <div class="row d-print-none">
        <div class="col-12">
            <div class="card mb-1">
                <div class="card-body">
                    <ul class="nav nav-pills nav-fill">
                        <li class="nav-item">
                            <a class="nav-link active" id="student-overview" data-bs-toggle="pill"
                                href="#student-overview-tab" aria-expanded="true">Overview</a>
                        </li>
                        @if (!auth()->user()->hasRole('Leader'))
                            <li class="nav-item">
                                <a class="nav-link" id="student-enrolment" data-bs-toggle="pill"
                                    onclick="Student.enrolment('{{ $student->id }}')" href="#student-enrolment-tab"
                                    aria-expanded="false"><span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    Enrolment</a>
                            </li>
                        @endif
                        @if (auth()->user()->can('view work placements'))
                            <li class="nav-item">
                                <a class="nav-link" id="student-work-placements" data-bs-toggle="pill"
                                    onclick="WorkPlacement.show('{{ $student->id }}')" href="#student-work-placements-tab"
                                    aria-expanded="false"><span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    Work Placements</a>
                            </li>
                        @endif
                        @if (auth()->user()->can('view documents'))
                            <li class="nav-item">
                                <a class="nav-link" id="student-documents" data-bs-toggle="pill"
                                    onclick="Student.showDocuments('{{ $student->id }}', false, {{ (bool)auth()->user()->can('delete documents') }})"
                                    href="#student-documents-tab" aria-expanded="false"><span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    Documents</a>
                            </li>
                        @endif
                        @if (auth()->user()->can('view notes'))
                            <li class="nav-item">
                                <a class="nav-link" id="student-notes" data-bs-toggle="pill"
                                    onclick="Tabs.showNotes('student','{{ $student->id }}')" href="#student-notes-tab"
                                    aria-expanded="false"><span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    Notes</a>
                            </li>
                        @endif
                        @if (auth()->user()->can('view student activities'))
                            <li class="nav-item">
                                <a class="nav-link" id="student-activities" data-bs-toggle="pill"
                                    onclick="Student.activities('{{ $student->id }}')" href="#student-activities-tab"
                                    aria-expanded="false">
                                    <span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    Activities</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link" id="student-assessments" data-bs-toggle="pill"
                                onclick="Student.assessments('{{ $student->id }}')" href="#student-assessments-tab"
                                aria-expanded="false">
                                <span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                Assessments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="student-history" data-bs-toggle="pill"
                                onclick="Student.history('{{ $student->id }}')" href="#student-history-tab"
                                aria-expanded="false"><span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="student-training-plan" data-bs-toggle="pill"
                                onclick="Student.trainingPlan('{{ $student->id }}')" href="#student-training-plan-tab"
                                aria-expanded="false"><span class="spinner-border"
                                    style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                Training Plan</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card mb-1">
                <div class="card-body p-1">
                    <div class="d-flex justify-content-between">
                        <div class="px-2">
                            @can('pin notes')
                                @if ($data['hasPinnedNotes'])
                                    <div class="alert alert-info p-50 m-0" id="pinned-notes-alert" role="alert">
                                        <i data-lucide='bookmark' class="me-50"></i>
                                        Important notes.
                                    </div>
                                @endif
                            @endcan
                        </div>
                        <div class="">
                            <div class="btn-group btn-group-sm" role="group" aria-label="page actions">
                                <button type="button" class="btn btn-outline-primary waves-effect waves-float waves-light"
                                    onclick="window.print();return false;">
                                    <i data-lucide='printer'></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-content'>
        <div class='tab-pane active' id='student-overview-tab' role='tabpanel' aria-labelledby='student-overview'
            aria-expanded='true'>
            <div class='row'>
                <div class='col-md-4 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>{{ \Str::title($student->name) }}</h2>
                        </div>
                        <div class='card-body'>
                            <div class="clearfix divider divider-secondary divider-start-center ">
                                <span class="divider-text text-dark"> Student</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Status:</span>
                                <span class='col col-sm-8'>{!! '<span class="text-' .
        ($student->is_active ? 'success' : 'danger') .
        '">' .
        ($student->is_active ? 'Active' : 'Inactive') .
        '</span>' !!}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Preferred Name:</span>
                                <span class='col col-sm-8 fw-bold'>{{ $student->detail->preferred_name ?? '' }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Username:</span>
                                <span class='col col-sm-8'>{{ $student->username }}</span>
                            </div>
                            @if (!empty($student->detail->onboard_at))
                                <div class='row mb-2'>
                                    <span class='fw-bolder col col-sm-4 text-end'>USI:</span>
                                    @php
                                        $usi = $student->onboardDetailsArray('step-4.usi_number');
                                        $cleanUsi = !empty($usi) ? str_replace(' ', '', $usi) : '';
                                        $isTba = !empty($cleanUsi) && strtoupper($cleanUsi) === 'TBA';
                                        $isValidUsi = true;
                                        if (!empty($cleanUsi) && !$isTba) {
                                            $isValidLength = strlen($cleanUsi) === 10;
                                            $isValidUsi = $isValidLength;
                                        }
                                    @endphp
                                    @if($isTba)
                                        <span class='col-sm-6 text-warning fw-bold'>
                                            {{ $usi }}
                                            <br>
                                            <small class="text-warning">
                                                <i data-lucide="alert-triangle"></i>
                                                Please follow up and verify USI
                                            </small>
                                        </span>
                                    @elseif(!$isValidUsi && !empty($cleanUsi))
                                        <span class='col-sm-6 text-warning fw-bold'>
                                            {{ $usi }}
                                            <br>
                                            <small class="text-warning">
                                                <i data-lucide="alert-triangle"></i>
                                                USI must be 10 characters long.
                                            </small>
                                        </span>
                                    @else
                                        <span class='col-sm-6'>{{ $usi }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Email:</span>
                                <span class='col col-sm-8'>{{ $student->email }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Purchase Order Number:</span>
                                <span class='col col-sm-8'>{{ $student->detail->purchase_order }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Phone:</span>
                                <span class='col col-sm-8'>{{ $student->detail->phone }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Address:</span>
                                <span class='col col-sm-8'>{{ $student->detail->address }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Language:</span>
                                <span class='col col-sm-8'>{{ $student->detail->language }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Preferred Language:</span>
                                <span class='col col-sm-8'>{{ $student->detail->preferred_language }}</span>
                            </div>
                            {{-- <div class='row mb-2'> --}}
                                {{-- <span class='fw-bolder col col-sm-4 text-end'>Country:</span> --}}
                                {{-- <span class='col col-sm-8'>{{ $student->detail->country->name }}</span> --}}
                                {{-- </div> --}}
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Timezone:</span>
                                <span class='col col-sm-8'>{{ $student->detail->timezone }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Last Sign In:</span>
                                <span class='col col-sm-8'>{{ $student->detail->last_logged_in }}</span>
                            </div>
                            <div class='row mb-2'>
                                <span class='fw-bolder col col-sm-4 text-end'>Created At:</span>
                                <span class='col col-sm-8'>{{ $student->created_at }}</span>
                            </div>
                            @if (!empty($student->study_type))
                                <div class='row mb-2'>
                                    <span class='fw-bolder col col-sm-4 text-end'>Study Type:</span>
                                    <span class='col col-sm-8'>{{ $student->study_type }}</span>
                                </div>
                            @endif
                            @if (intval($student->is_active) === 0)
                                <div class='row mb-2'>
                                    <span class='fw-bolder col col-sm-4 text-end'>Deactivated By</span>
                                    <span class='col col-sm-8'>{{ $activity['by'] ?? '' }}</span>
                                </div>
                                <div class='row mb-2'>
                                    <span class='fw-bolder col col-sm-4 text-end'>Deactivated On</span>
                                    <span class='col col-sm-8'>{{ $activity['on'] ?? '' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @can('view students related')
                                    <div class="card">
                                        <div class="card-header">
                                            <h2 class='fw-bolder text-primary mx-auto'>More Details</h2>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $registered_by = \App\Models\User::find(intval($student->detail->registered_by));
                                            @endphp
                                            @if (!empty($registered_by))
                                                <div class='row mb-2'>
                                                    <span class='fw-bolder col col-sm-4 text-end'>Registered By:</span>
                                                    <span class='col col-sm-8'>{{ $registered_by->name }}</span>
                                                </div>
                                            @endif
                                            <div class='row mb-2'>
                                              <span
                                                    class='fw-bolder col col-sm-4 text-end'>{{ \Str::plural('Trainer', $data['trainers']->count())}}:</span>
                                                <span class='col col-sm-8'>
                                                    @if ($data['trainers']->isNotEmpty())
                                                                                {!! $data['trainers']->map(function ($user) {
                                                            return '<a href="' .
                                                                route('account_manager.trainers.show', $user->id) .
                                                                '">' .
                                                                $user->first_name .
                                                                ' ' .
                                                                $user->last_name .
                                                                '</a>';
                                                        })->implode(', ') !!}
                                                    @endif
                                                </span>
                                            </div>
                                            <div class='row mb-2'>
                                                <span
                                                    class='fw-bolder col col-sm-4 text-end'>{{ \Str::plural('Leader', $data['leaders']->count()) }}:</span>
                                                <span class='col col-sm-8'>
                                                    {!! $data['leaders']->map(function ($user) {
                            return '<a href="' .
                                route('account_manager.leaders.show', $user->id) .
                                '">' .
                                $user->first_name .
                                ' ' .
                                $user->last_name .
                                ($user->is_active ? '' : ' (Inactive)') .
                                '</a>';
                        })->implode(', ') !!}
                                                </span>
                                            </div>
                                            @if (isset($data['company']))
                                                <div class='row mb-2'>
                                                    <span class='fw-bolder col col-sm-4 text-end'>Company/Site:</span>
                                                    <span class='col col-sm-8'>
                                                        <a
                                                            href='{{ route('account_manager.companies.show', $data['company']?->id) }}'>{{ $data['company']?->name }}</a>
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                    @endcan
                </div>
                <div class="col-md-8 col-12">

                    {{-- {{ Widget::run('addNote',['input_id' => 'note_body', 'student_id' => $student->id]) }} --}}

                    <div class="card">
                        <div class="card-header">
                            <h2 class='fw-bolder text-primary mx-auto'>Course(s) Enrolled</h2>
                            @if ($student->leaders()->count() > 0 && !auth()->user()->isLeader())
                                @if (count($courses) > 0)
                                    <button class="btn btn-success  waves-effect waves-float waves-light end" data-bs-toggle="modal"
                                        type='button' data-bs-target="#assign-course-sidebar">
                                        Assign Course
                                    </button>
                                @else
                                    <a href="{{ route('lms.courses.create') }}"
                                        class="btn btn-info  waves-effect waves-float waves-light end">
                                        Create New Course
                                    </a>
                                @endif
                            @endif
                        </div>
                        <h6 class="card-body">
                            @if (count($registeredCourses) > 0)
                                @foreach ($registeredCourses as $course)
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-start'>Course Title:</span>
                                        <span class='col col-sm-8' data-id="{{ $course->course_id }}">
                                            {{ $course->course?->title }}
                                            {{-- @can('manage course version') --}}
                                            {{-- {!! (!empty($course->course->version))? "<span
                                                class='badge rounded-pill badge-light-primary px-1 pb-50'><small>v</small>{$course->course->version}</span>":""
                                            !!} --}}
                                            {{-- @endcan --}}
                                        </span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-start'>Course Starts On:</span>
                                        <span class='col col-sm-8'> {{ $course->course_start_at }}</span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-start'>Course Ends On:</span>
                                        <span class='col col-sm-8'> {{ $course->course_ends_at }}</span>
                                    </div>
                                    @can('show course expiry')
                                        @if (!empty($course->course_expiry))
                                            <div class='row mb-2'>
                                                <span class='fw-bolder col col-sm-4 text-start'>Course Expiry Date:</span>
                                                <span class='col col-sm-8'>
                                                    {{ \Carbon\Carbon::parse($course->course_expiry)->format('j F, Y') }}</span>
                                            </div>
                                        @endif
                                    @endcan
                                    @if (
                                            !empty($course->deferred) &&
                                            $course->deferred &&
                                        \Carbon\Carbon::parse($course->getRawOriginal('course_ends_at'))->greaterThan(\Carbon\Carbon::now()))
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start'>Deferred:</span>
                                            <span class='col col-sm-8'> Yes</span>
                                        </div>
                                    @endif
                                    @if (env('SETTINGS_KEY') === 'KeyInstitute' && $course->is_main_course)
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start'>Semester 1 Only:</span>
                                            <span class='col col-sm-8'>
                                                {{ $course->allowed_to_next_course ? 'No' : 'Yes' }}</span>
                                        </div>
                                    @endif
                                    @if (!empty($course->registration_date) && $course->registration_date && false)
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start'>Registered On:</span>
                                            <span class='col col-sm-8'> {{ $course->registration_date }}</span>
                                        </div>
                                    @endif
                                    @if (!empty($course) && $course->is_chargeable)
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start'>Generate Invoice:</span>
                                            <span class='col col-sm-8'> Yes</span>
                                        </div>
                                    @endif
                                    @if (!empty($course) && $course->is_locked)
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start text-danger'>Locked
                                                Enrollment</span>
                                            <span class='col col-sm-8 text-danger'> Yes</span>
                                        </div>
                                    @endif
                                    @if ($course->is_main_course && !empty($course->enrolmentStats->pre_course_assisted))
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-start'>Pre-course Assistance
                                                Required:</span>
                                            <span class='col col-sm-8'>Yes</span>
                                        </div>
                                    @endif
                                    @if (auth()->user()->email === 'mohsin@inceptionsol.com' && strtolower(env('APP_ENV')) === 'local')
                                        <button id="reset_course_progress" class="btn btn-sm btn-danger mb-2"
                                            onclick="Student.resetProgress({{ $student->id . ',' . $course->course?->id }})">
                                            Reset Progress
                                        </button>
                                    @endif
                                    <hr />
                                @endforeach
                            @else
                                <div class='row mb-2'>
                                    <p>No Course assigned to this student yet</p>
                                </div>
                            @endif
                            @if (auth()->user()->isRoot())
                                <a class="btn btn-outline-secondary btn-sm"
                                    href="{{ route('account_manager.students.clean', $student->id) }}">Clean Invalid
                                    Notifications</a>
                            @endif
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="fw-bolder text-primary mx-auto">Student Progress</h2>

                        </div>
                        <div class="card-body">
                            @foreach ($registeredCourses as $regCourse)
                                @php $courseStats = $regCourse->enrolmentStats?->course_stats; @endphp
                                <div class="clearfix divider divider-secondary">
                                    <span class="divider-text text-dark"> {{ $regCourse->course?->title }}
                                        @can('manage course version')
                                            @if (!empty($regCourse->course->version))
                                                (<small>version # </small>{{ $regCourse->course?->version }})
                                            @endif
                                        @endcan
                                    </span>
                                </div>
                                @can('update student progress')
                                    <div class='row mb-2'>
                                        <a href="javascript:void(0);" class="clearfix btn btn-sm btn-outline-secondary"
                                            data-student="{{ $regCourse->user_id }}" data-course="{{ $regCourse->course_id }}"
                                            onclick="Student.reEvaluateCourseProgress({{ $regCourse->user_id . ',' . $regCourse->course_id }})">Re-evaluate
                                            Course Progress</a>
                                    </div>
                                @endcan
                                @if (empty($courseStats))
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>No progress made so far.</span>
                                    </div>
                                @else
                                    <div class='row mb-2'>
                                        @php
        $courseStatus = $regCourse->enrolmentStats->course_status;
                                        @endphp
                                        <span class='fw-bolder col col-sm-4 text-end'>Course Status:</span>
                                        <span data-status="{{ $courseStatus }}"
                                            class='col col-sm-8 fw-bolder text-{{ config('constants.status.color.' . $courseStatus, 'primary') }}'>{{ $courseStatus }}</span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>Course Start Date:</span>
                                        <span class='col col-sm-8'>{{ $regCourse->course_start_at }}</span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>Course End Date:</span>
                                        <span class='col col-sm-8'>{{ $regCourse->course_ends_at }}</span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>Course Progress:</span>
                                        <span class='col col-sm-8'>
                                            Current Percentage: {{ $courseStats['current_course_progress'] }}%
                                            <br />
                                            Expected Percentage: {{ $courseStats['expected_course_progress'] }}%
                                        </span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>Time Progress:</span>
                                        <span class='col col-sm-8'>
                                            Total Time:
                                            {{ isset($courseStats['hours_details']['actual']) ? $courseStats['hours_details']['actual']['time'] : '0:0' }}
                                            <br />
                                            Time Spent:
                                            {{ isset($courseStats['hours_details']['reported']) ? $courseStats['hours_details']['reported']['time'] : '0:0' }}
                                            <br />
                                            Time Spent (Last Week):
                                            {{ isset($courseStats['hours_details']['last_week']) ? $courseStats['hours_details']['last_week']['time'] : '0:0' }}
                                        </span>
                                    </div>
                                    <div class='row mb-2'>
                                        <span class='fw-bolder col col-sm-4 text-end'>Assessment Progress:</span>
                                        <span class='col col-sm-8'>
                                            Total: {{ $courseStats['total_assignments'] }}
                                            <br />
                                            Pending: {{ $courseStats['total_assignments_remaining'] }}
                                            <br />
                                            Submitted: {{ $courseStats['total_assignments_submitted'] }}
                                            <br />
                                            Satisfactory: {{ $courseStats['total_assignments_satisfactory'] }}
                                            <br />
                                            Not Satisfactory: {{ $courseStats['total_assignments_not_satisfactory'] }}
                                        </span>
                                    </div>
                                    @if (!empty($regCourse->course_completed_at))
                                        <div class='row mb-2'>
                                            <span class='fw-bolder col col-sm-4 text-end'>Completed On:</span>
                                            <span
                                                class='col col-sm-8'>{{ \Carbon\Carbon::parse($regCourse->course_completed_at)->format('j F, Y') }}</span>
                                        </div>
                                    @endif
                                    @if (boolval($regCourse->is_main_course))
                                        <div class='row mb-2'>
                                            @if (intval($regCourse->cert_issued) === 1)
                                                <span class='fw-bolder col col-sm-4 text-end'>Issue Certificate/SOA:</span>
                                                <span class='col col-sm-8'>
                                                    <strong class="text-success">Issued on
                                                        {{ $regCourse->cert_issued_on }} by
                                                        {{ $regCourse->cert_issued_by }}</strong>
                                                </span>
                                            @elseif(auth()->user()->can('issue certificate'))
                                                @if (!empty($regCourse->enrolmentStats->can_issue_cert))
                                                    <span class='fw-bolder col col-sm-4 text-end'>Issue
                                                        Certificate/SOA:</span>
                                                    <span class='col col-sm-8'>
                                                        @if (empty($regCourse->cert_issued))
                                                            <button class="btn btn-sm btn-info waves-effect waves-float waves-light mb-2"
                                                                data-bs-toggle="modal" type='button'
                                                                data-bs-target="#issue-certificate-modal-{{ $regCourse->id }}">
                                                                Issue Certificate/SOA
                                                            </button>

                                                            <div class="modal modal-slide-in fade" id="issue-certificate-modal-{{ $regCourse->id }}"
                                                                aria-hidden="true">
                                                                <div class="modal-dialog sidebar-xlg">
                                                                    <div class="modal-content p-0">
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                            aria-label="Close">×</button>
                                                                        <div class="modal-header mb-1">
                                                                            <h5 class="modal-title">
                                                                                <span class="align-middle">Issue
                                                                                    Certificate/SOA:</span>
                                                                            </h5>
                                                                        </div>
                                                                        <div class="modal-body flex-grow-1 blockUI">
                                                                            <div class="row">
                                                                                <div class="col-12 mb-1">
                                                                                    <label class="form-label required" for="cert_issued_on">Choose
                                                                                        date
                                                                                        for certificate issuance:</label>
                                                                                    <input name="cert_issued_on" id="cert_issued_on"
                                                                                        class="form-control date-picker" type="text"
                                                                                        required="required" />
                                                                                </div>
                                                                                <div class="d-flex flex-wrap mb-3">
                                                                                    <button id="issue_certificate_action"
                                                                                        class="btn btn-primary me-1"
                                                                                        onclick="Student.issueCertificate({{ $student->id }}, {{ $regCourse->course_id }}, {{ $regCourse->enrolmentStats->next_course_id }})">
                                                                                        Confirm
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="fw-bolder text-primary mx-auto">Enrolment Details</h2>
                        </div>
                        @if (!empty($data['enrolment']) && isset($data['enrolment']['basic']))
                            <div class="card-body">
                                <div class="clearfix divider divider-secondary divider-start-center ">
                                    <span class="divider-text text-dark"> Basic</span>
                                </div>
                                @foreach ($data['enrolment']['basic'] as $key => $value)
                                    <div class='row mb-2'>
                                        <span
                                            class='fw-bolder col col-sm-4 text-end'>{{ \Str::title(\Str::replace('_', ' ', $key)) }}:</span>
                                        <span class='col col-sm-8'>{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class='tab-pane' id='student-enrolment-tab' role='tabpanel' aria-labelledby='student-enrolment'
            aria-expanded='false'>
        </div>
        <div class='tab-pane' id='student-documents-tab' role='tabpanel' aria-labelledby='student-documents'
            aria-expanded='false'>
            @if (auth()->user()->can('upload documents'))
                <div id="upload_documents_wrapper" class="d-print-none">
                    <div class='col-12'>
                        <div class='card'>
                            <div class='card-body'>
                                <form method="post" action="/api/v1/documents/{{ $student->id }}" class="dropzone dropzone-area" id="dpz-single-file">
                                    @csrf
                                    <div class="previews"></div>
                                    <div class="dz-message">Drop files here or click to upload.</div>
                                    <small id="document-upload-instructions" class="text-muted d-block text-center" style="position: absolute; bottom: 1rem; left: 0; right: 0; padding: 0 1rem;">
                                        Accepted file types: PDF, DOC, DOCX, ZIP, JPG/JPEG, PNG, GIF, WEBP, XLS, XLSX, RTF, TXT, PPT, PPTX. Maximum file size: 25MB per
                                        file.
                                    </small>
                                    <div class="fallback">
                                        <input name="file" type="file" multiple />
                                    </div>
                                    {{-- <label>Input File Name:</label> --}}
                                    {{-- <input type="text" name="title" id="documentTitle" class="fileName" /> --}}
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="content-documents"></div>
        </div>
        <div class='tab-pane' id='student-notes-tab' role='tabpanel' aria-labelledby='student-notes' aria-expanded='false'>
            @if (auth()->user()->can('create notes'))
                <div id="note_input_wrapper" class="d-print-none">
                    {{ Widget::run('addNote', ['input_id' => 'note_body2', 'subject_type' => 'student', 'subject_id' => $student->id]) }}
                </div>
            @endif
            <div class="content-notes"></div>
        </div>
        <div class='tab-pane' id='student-work-placements-tab' role='tabpanel' aria-labelledby='student-work-placements'
            aria-expanded='false'>
            <button class="btn btn-success waves-effect waves-float waves-light mb-1" type='button'
                onclick="WorkPlacement.showModal()">
                Add Work Placement
            </button>
            <div class="content-work-placements"></div>
        </div>
        @if (auth()->user()->can('view student activities'))
            <div class='tab-pane' id='student-activities-tab' role='tabpanel' aria-labelledby='student-activities'
                aria-expanded='false'>
            </div>
        @endif
        <div class='tab-pane' id='student-assessments-tab' role='tabpanel' aria-labelledby='student-assessments'
            aria-expanded='false'>
        </div>
        <div class='tab-pane' id='student-history-tab' role='tabpanel' aria-labelledby='student-history'
            aria-expanded='false'>
            <!-- Calendar -->
            <div class="col position-relative">
                <div class="card shadow-none border-0 mb-0 rounded-0">
                    <div class="card-body pb-0">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
            <!-- /Calendar -->
        </div>
        <div class='tab-pane' id='student-training-plan-tab' role='tabpanel' aria-labelledby='student-training-plan'
            aria-expanded='false'>
        </div>
    </div>
    <div class="modal modal-slide-in fade" id="competencies-details" aria-hidden="true">
        <div class="modal-dialog sidebar-xlg">
            <div class="modal-content p-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">
                        <span class="align-middle">Competency Details</span>
                    </h5>
                </div>
                <div class="modal-body flex-grow-1 blockUI">
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade text-start" id="documentFileModal" tabindex="-1" aria-labelledby="documentFileModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel1">Input File Name</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="documentFileName" id="documentFileName" value="" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Submit</button>
                </div>
            </div>
        </div>
    </div>
    @if (!empty($student))
        @include('content.account-manager.students.modal-assign-course')
        @if (auth()->user()->can('view work placements'))
            @include('content.account-manager.students.modal-work-placement')
        @endif
    @endif

    <!-- Resend Password Modal -->
    <div class="modal fade text-start" id="resend-password-modal" tabindex="-1" aria-labelledby="resendPasswordModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="resendPasswordModal">
                        <i data-lucide="alert-triangle" class="me-1"></i>Resend Password Email
                    </h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to resend a password email to <strong class="student-email"></strong>? This will reset their password.</p>
                    <p class="text-muted small">
                        <strong>Note:</strong> After the user logs in with this new password, The user will not be prompted to create a new password.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-resend-password">
                        <i data-lucide="mail" class="me-1"></i>
                        Resend
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/calendar/fullcalendar.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset('vendors/js/forms/repeater/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/ckeditor/ckeditor.js') }}"></script>
    {{-- TinyMCE for notes (replaces CKEditor for spell checking support) --}}
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.7.0/tinymce.min.js"></script>
    <script src="{{ asset(mix('js/scripts/_my/tinymce-init.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>

    <script src="{{ asset(mix('vendors/js/tables/datatable/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.bootstrap5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.responsive.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/responsive.bootstrap4.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.checkboxes.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.buttons.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/jszip.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/pdfmake.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/vfs_fonts.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.html5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.print.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/datatables/buttons.server-side.js')) }}"></script>

    <script src="{{ asset(mix('vendors/js/file-uploaders/dropzone.min.js')) }}"></script>
@endsection

@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/assign_course.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/calendar.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/tabs.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/lms.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/work_placement.js')) }}"></script>
    <script>
        Dropzone.autoDiscover = false;

        const url = new URL(window.location.href);
        const canDelete = "{{ (bool)auth()->user()->can('delete documents') }}";

        $(function () {
            // Wait for TinyMCE to be loaded before initializing
            function initializeTinyMCE() {
                if (typeof tinymce === 'undefined') {
                    // TinyMCE not loaded yet, wait a bit and try again
                    setTimeout(initializeTinyMCE, 100);
                    return;
                }

                // Initialize TinyMCE for notes (replaces CKEditor for spell checking support)
                if (typeof initTinyMCE === 'function') {
                    initTinyMCE('content-tinymce', {
                        plugins: 'lists wordcount link code',
                        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                        height: 365,
                        menubar: false,
                        branding: false,
                        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                        browser_spellcheck: true,
                        contextmenu: false, // Disable TinyMCE context menu to allow browser's native context menu with spell check suggestions
                        gecko_spellcheck: true, // Enable spell check for Firefox
                    });
                } else {
                    // Fallback if initTinyMCE is not available
                    tinymce.init({
                        selector: '.content-tinymce',
                        plugins: 'lists wordcount link code',
                        toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                        height: 365,
                        menubar: false,
                        branding: false,
                        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                        browser_spellcheck: true,
                        contextmenu: false, // Disable TinyMCE context menu to allow browser's native context menu with spell check suggestions
                        gecko_spellcheck: true, // Enable spell check for Firefox
                    });
                }
            }

            // Start initialization
            initializeTinyMCE();

            let hash = window.location.hash;
            if (hash) {
                $(hash).tab('show');
                $(hash).trigger('click');
            }

            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            myDataTable.initDefaults(defaults);

            $(document).bind('refreshStudentActivity', function (event) {
                console.log('refresh Student Activity Triggered');
                $('#student-activities-table').DataTable().ajax.reload();
            });
            if ($("#cert_issued_on").length) {
                $("#cert_issued_on").flatpickr({
                    // dateFormat: "YYYY-MM-DD",
                    maxDate: "today",
                    // minDate: new Date().fp_incr(-180),
                });
            }
            $('#dpz-single-file').dropzone({
                uploadMultiple: true,
                parallelUploads: 3,
                maxFiles: 100,
                addRemoveLinks: true,
                // maxFiles: 1,
                // paramName: $('#documentTitle').val(),
                acceptedFiles: "image/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.txt,text/plain,.rtf",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="token"]').attr('content')
                },
                success: function (file, response) {
                    // console.log(file, response);
                    toastr['success'](response.message ? response.message :
                        'Document uploaded successfully.',
                        'Success', {
                        closeButton: true,
                        tapToDismiss: true
                    });
                    Student.showDocuments({{ $student->id }}, true, canDelete);
                    this.removeFile(file);
                    // return console.log(response);
                },
                error: function (file, response) {
                    let errorMessage = 'Unable to upload document';
                    if (response && response.errors) {
                        const errors = response.errors;
                        if (errors.file && Array.isArray(errors.file)) {
                            errorMessage = errors.file[0];
                        } else if (errors['file.0']) {
                            errorMessage = errors['file.0'][0];
                        } else if (typeof errors === 'string') {
                            errorMessage = errors;
                        }
                    } else if (response && response.message) {
                        errorMessage = response.message;
                    }
                    toastr['error'](errorMessage, 'Upload Error', {
                        closeButton: true,
                        tapToDismiss: true,
                        timeOut: 5000
                    });
                    // return console.log(response);
                }
            });
        });

        $(document).ready(function () {
            WorkPlacement.init();
            // Work Placement Course Selection
            $('#work-placement-form #course_id').on('select2:select', function () {
                const selectedOption = $(this).find('option:selected');
                const startDate = selectedOption.data('start-date');
                const endDate = selectedOption.data('end-date');

                if (startDate && endDate) {
                    // Populate spans
                    $('#selected_course .start_date').html("<strong>Start Date:</strong> " + startDate);
                    $('#selected_course .end_date').html(" <strong>End Date:</strong> " + endDate);

                    // Populate hidden inputs
                    $('input[name="course_start_date"]').val(startDate);
                    $('input[name="course_end_date"]').val(endDate);

                    // Show the container
                    $('#selected_course').show();
                } else {
                    // Hide the container and clear values if no valid option is selected
                    $('#selected_course').hide();
                    $('#selected_course .start_date, #selected_course .end_date').text('');
                    $('input[name="course_start_date"], input[name="course_end_date"]').val('');
                }
            });
        });

        // Handle Resend Password button click
        $(document).on('click', '.resend-password-btn', function(e) {
            e.preventDefault();

            const studentId = $(this).data('student-id');
            const studentEmail = $(this).data('student-email');

            // Set the email in the modal
            $('#resend-password-modal .student-email').text(studentEmail);

            // Store student ID on the confirm button
            $('#confirm-resend-password').data('student-id', studentId);

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('resend-password-modal'));
            modal.show();
        });

        // Handle confirm resend password
        $(document).on('click', '#confirm-resend-password', function(e) {
            e.preventDefault();

            const studentId = $(this).data('student-id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

            // Create and submit form
            const form = $('<form>', {
                'method': 'POST',
                'action': `/account-manager/students/${studentId}/resend-password`
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': csrfToken
            }));

            $('body').append(form);
            form.submit();
        });

        // Handle Skip LLND button click
        $(document).on('click', '.skip-llnd-btn', function(e) {
            e.preventDefault();

            const studentId = $(this).data('student-id');
            const studentName = $(this).data('student-name');

            console.log('Skip LLND button clicked:', {
                studentId,
                studentName
            });

            Swal.fire({
                title: 'Warning',
                text: `This will insert a completed LLND quiz for ${studentName}. This should only be done to speed up the creation of test accounts. Removal will require removal from the DB.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Skip LLND',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('User confirmed, showing loading toast...');

                    // Show loading toast
                    toastr.info('Processing LLND skip request...', 'Please wait', {
                        closeButton: true,
                        tapToDismiss: false
                    });

                    console.log('Redirecting to:', `/account-manager/students/${studentId}/skip-llnd`);

                    // Redirect to skip LLND route
                    window.location.href = `/account-manager/students/${studentId}/skip-llnd`;
                }
            });
        });
    </script>
@endsection
