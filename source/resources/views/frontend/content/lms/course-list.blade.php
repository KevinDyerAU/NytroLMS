<div class="d-flex align-items-center">
    <div class="col-lg-8 col-md-10 col-12 mx-auto">
        <div class="row g-3">
            @if ($registeredCourses->count())
                @foreach ($registeredCourses as $regCourse)
                    @if (count($regCourse->course->lessons) < 1)
                        {{-- NO LESSONS FOUND --}}
                        <div class="alert alert-secondary" role="alert">
                            <div class="alert-body">
                                <h6 class="p-2 m-0">No course assigned yet! Please contact your assigned leader,
                                    <strong>{{ auth()->user()->leaders()->first()?->name }}
                                        {{ auth()->user()->leaders()->first()?->surname }}</strong>, to get you
                                    enrolled.
                                </h6>
                            </div>
                        </div>
                        @continue
                    @endif
                    @php
                        \App\Services\CourseProgressService::initProgressSession(
                            $regCourse->user_id,
                            $regCourse->course_id,
                        );
                    @endphp
                    <div class="col-lg-6 col-md-8 col-10 col-12">
                        <div class="card shadow-sm">
                            <div class="position-relative">
                                <img class="card-img-top img-fluid"
                                    src="{{ !empty($regCourse->course->featuredImage()) ? Storage::url($regCourse->course->featuredImage()->file_path) : asset('images/banner/banner-1.jpg') }}"
                                    alt="{{ $regCourse->course->title }} Banner image" />
                            </div>
                            <div class="card-body d-flex flex-column pb-0">
                                <h4 class="card-title mb-0">
                                    {{ $regCourse->course->title }}
                                </h4>
                                <hr />
                                @php
                                    $courseProgress = \App\Services\CourseProgressService::getProgress(
                                        $regCourse->user_id,
                                        $regCourse->course_id,
                                    );
                                    // Use StudentTrainingPlanService for LLND-adjusted progress calculation
                                    $studentTrainingPlanService = new \App\Services\StudentTrainingPlanService(
                                        $regCourse->user_id,
                                    );
                                    $totalCounts = $studentTrainingPlanService->getTotalCounts(
                                        $courseProgress->details->toArray(),
                                        $regCourse->course_id,
                                    );
                                    // Use the same percentage calculation logic as admin profile
                                    $percentage = \App\Services\CourseProgressService::calculatePercentage(
                                        $totalCounts,
                                        $regCourse->user_id,
                                        $regCourse->course_id,
                                    );
                                    $expectedPercentage = \App\Services\CourseProgressService::expectedPercentage(
                                        $regCourse->user_id,
                                        $regCourse->course_id,
                                        $courseProgress->percentage,
                                    );
                                    $actualCompletion = round($percentage);
                                    $courseStatus = Str::title($regCourse->enrolmentStats->course_status);
                                    $courseStatusColor =
                                        $courseStatus === 'Completed'
                                            ? 'bg-success'
                                            : ($courseStatus === 'On Schedule'
                                                ? 'bg-primary'
                                                : 'bg-warning');
                                @endphp
                                {{-- COURSE PROGRESS --}}
                                <div class="row text-center">
                                    <div class="col-6 mb-2">
                                        <small class="d-block">Start Date:</small>
                                        <span class="fw-medium">{{ $regCourse->course_start_at }}</span>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <small class="d-block">End Date:</small>
                                        <span class="fw-medium">{{ $regCourse->course_ends_at }}</span>
                                    </div>
                                    {{-- <div class="col-4 mb-2">
                                        <small class="d-block">Lessons:</small>
                                        @php
                                        $progressDetails = json_decode( $regCourse->progress->details );
                                        @endphp
                                        <span class="fw-medium">
                                            {{ $progressDetails->lessons->passed ?? 0 }}/
                                            {{ $progressDetails->lessons->count ?? 0 }}
                                        </span>
                                    </div> --}}
                                </div>

                                {{-- PROGRESS BAR --}}
                                <div class='col-11 mx-auto'>
                                    <div class="progress position-relative" aria-valuemin="0" aria-valuemax="100"
                                        style="height: 80%;">
                                        <div class="bar-step"
                                            style="width: {{ $expectedPercentage < 10 ? '90px' : ($expectedPercentage < 15 ? '95px' : '120px') }};
                                                                                left: calc({{ $expectedPercentage > 60
                                                                                    ? $expectedPercentage - 5
                                                                                    : ($expectedPercentage < 8
                                                                                        ? '22'
                                                                                        : ($expectedPercentage < 15
                                                                                            ? '20'
                                                                                            : ($expectedPercentage >= 50 && $expectedPercentage < 60
                                                                                                ? $expectedPercentage
                                                                                                : $expectedPercentage + 5))) }}% - 96px)">
                                        </div>
                                        <div class="progress-bar progress-bar-striped {{ $courseStatusColor }}"
                                            role="progressbar" aria-valuenow="{{ $courseProgress->percentage }}"
                                            aria-valuemin="0" aria-valuemax="100"
                                            style="font-weight: bold; width: {{ $courseProgress->percentage > 8 ? $courseProgress->percentage : 8 }}%">
                                            {{ $actualCompletion }}%
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center gap-2 my-1">
                                    <div class="label-txt">🎯Target:
                                        {{ round($expectedPercentage) }}% </div>
                                    <div class="label-txt">✅Completed: {{ $actualCompletion }}%</div>
                                </div>

                                {{-- RETURNED Assessments --}}
                                @if (!empty($regCourse->returned_assessments) && $regCourse->returned_assessments->count() > 0)
                                    <div class="col-11 px-2 mt-2">
                                        <p class="fw-medium mb-0">Assessments Returned:</p>
                                        <ul class="list-group list-group-flush">
                                            @foreach ($regCourse->returned_assessments as $assessment)
                                                <a class="text-danger text-decoration-none" title="Not Satisfactory"
                                                    href="{{ route('frontend.lms.quizzes.attempt', [$assessment->quiz, $assessment]) }}">
                                                    <li
                                                        class="list-group-item list-group-item-warning list-group-item-action px-1">
                                                        ❗ {{ $assessment->quiz->title }}
                                                    </li>
                                                </a>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                {{-- FOOTER --}}
                                {{-- LLND Notice for disabled courses --}}
                                @if (isset($llnStatus) && $llnStatus['has_submitted'] && !$llnStatus['is_satisfactory'])
                                    @php
                                        $isSubmittedPending = in_array($llnStatus['status'], [
                                            'SUBMITTED',
                                            'REVIEWING',
                                        ]);
                                    @endphp
                                    <div class="alert alert-warning alert-sm mb-2">
                                        <div class="alert-body p-2">
                                            <small class="mb-0">
                                                <i data-lucide="{{ $isSubmittedPending ? 'clock' : 'lock' }}"
                                                    class="me-1" style="width: 14px; height: 14px;"></i>
                                                @if ($isSubmittedPending)
                                                    LLND assessment is being evaluated. Please wait for results.
                                                @else
                                                    LLND assessment must be passed before accessing this course.
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <div class="text-center align-self-center">
                                        <span
                                            class="badge {{ $courseStatusColor }} rounded-pill">{{ $courseStatus }}</span>
                                    </div>

                                    @if (isset($llnStatus) && $llnStatus['has_submitted'] && !$llnStatus['is_satisfactory'])
                                        @php
                                            $isSubmittedPending = in_array($llnStatus['status'], [
                                                'SUBMITTED',
                                                'REVIEWING',
                                            ]);
                                        @endphp
                                        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-float waves-light" disabled
                                            title="{{ $isSubmittedPending ? 'LLND assessment is being evaluated' : 'Complete LLND assessment first' }}">
                                            <i data-lucide="{{ $isSubmittedPending ? 'clock' : 'lock' }}"
                                                class="me-1"></i>
                                            View Course
                                        </button>
                                    @else
                                        <a href="{{ route('frontend.lms.courses.show', [$regCourse->course->id, $regCourse->course->slug]) }}"
                                            class="btn btn-sm btn-primary waves-effect waves-float waves-light">
                                            View Course
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                {{-- NO COURSE ASSIGNED --}}
                <div class="alert alert-secondary" role="alert">
                    <div class="alert-body">
                        <h6 class="p-2">No course assigned yet! Please contact your assigned leader,
                            <strong>{{ auth()->user()->leaders()->first()?->name }}</strong>, to get you enrolled.
                        </h6>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Force divider to avoid inconsistent renders */
    hr {
        border: 0;
        border-top: 1px solid #ccc;
        margin: 1rem 0;
        opacity: 1;
    }

    .dark-layout hr {
        border-color: #3b4253;
    }
</style>
