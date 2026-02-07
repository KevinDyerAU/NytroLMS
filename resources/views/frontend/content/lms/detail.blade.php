@extends('frontend/layouts/detachedLayoutMaster')

@section('title', $title)

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet" type="text/css"
        href="{{ asset('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-blog.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/laraberg/css/laraberg.css') }}">
@endsection

@section('content-sidebar')
    {{--    @include('frontend.content.lms.sidebar') --}}
@endsection

@section('content')
    {{-- Main content container --}}
    <div class="blog-detail-wrapper col-12 col-md-10 col-lg-8 mx-auto px-10">
        <div class="row">
            {{-- Main post/lesson card --}}
            <div class="card" id="post-detail">
                {{-- Featured image or fallback --}}
                <img class="card-img-top img-fluid height-400 pt-1" style="object-fit: contain;"
                    src="{{ !empty($post->featuredImage()) ? Storage::url($post->featuredImage()->file_path) : asset('images/banner/banner-1.jpg') }}"
                    alt="Featured Image" />
                <div class="card-body px-1 px-sm-3 px-md-4 px-lg-5 py-2">
                    <div class="content-wrapper">
                        {{-- Render main content --}}
                        {!! $post->lb_content !!}
                    </div>
                </div>
            </div>

            {{-- Related content section --}}
            {{-- If there is related content to show --}}
            @if ($hasRelated)
                <div class="col-12 mt-1" id="content__{{ $type . '-' . $post->id }}">
                    {{-- Section header and progress --}}
                    <h6 class="section-label mt-2 d-flex justify-content-between align-items-center px-1">
                        <span class="text-primary">{{ $related['title'] }}</span>
                        {{-- If there is progress data (passed/total/submitted), show it --}}
                        @if (isset($related['passed']) && $related['total'])
                            <span class="text-info">
                                Passed: {{ $related['passed'] }}
                                <span class="text-muted"> / Submitted: {{ $related['submitted'] }}</span>
                                <span class="text-dark"> / Total: {{ $related['total'] }}</span>
                            </span>
                        @endif
                    </h6>
                    <div class="card">
                        <div class="card-body py-2">
                            <div class="accordion accordion-margin" id="accordionRelatedContent">
                                @foreach ($related['data'] as $data)
                                    {{-- If this is a quiz and it has no questions, skip rendering this item --}}
                                    @if (isset($related['type']) && $related['type'] == 'quiz')
                                        @if ($data->questions->count() === 0)
                                            @continue
                                        @endif
                                    @endif

                                    {{-- Light divider between items --}}
                                    @if (!$loop->first)
                                        <hr class="border-light">
                                    @endif

                                    {{-- List item --}}
                                    <div class="list-group-item border-0 p-0" data-index="{{ $loop->index }}"
                                        data-is_pre_course-lesson="{{ $is_pre_course_lesson ?? '' }}"
                                        data-precourse_satisfactory="{{ $pre_course_satisfactory ?? '' }}"
                                        data-match_pre_course_lesson_title="{{ $match_pre_course_lesson_title ?? '' }}"
                                        data-old_pre_course_satisfactory="{{ $old_pre_course_satisfactory ?? '' }}">

                                        {{-- Item header with title and badges --}}
                                        <div class="d-flex flex-row justify-content-between align-items-start">
                                            <div id="lesson-info"
                                                class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                                                {{-- Show status badges based on completion/submission/quiz status --}}
                                                {{-- If this is not a quiz and the item is completed, show Completed badge --}}
                                                {{-- Logic: Show "Completed" badge for non-quiz items that are completed --}}
                                                @if (isset($related['type']) && $related['type'] !== 'quiz' && ($data->is_completed || $data->isComplete()))
                                                    <span class="badge bg-{{ config('lms.status.COMPLETED.class') }}">
                                                        Completed
                                                    </span>
                                                    {{-- If this is not a quiz and the item is submitted, show Submitted badge --}}
                                                    {{-- Logic: Show "Submitted" badge for non-quiz items that are submitted --}}
                                                @elseif(isset($related['type']) && $related['type'] !== 'quiz' && ($data->is_submitted || $data->isSubmitted()))
                                                    <span class="badge bg-{{ config('lms.status.SUBMITTED.class') }}">
                                                        Submitted
                                                    </span>
                                                    {{-- If this is a quiz and there is a last attempt, show quiz status badge --}}
                                                    {{-- Logic: For quizzes, show badge based on last attempt's system_result and status --}}
                                                @elseif(isset($related['type']) && $related['type'] === 'quiz' && $data->lastAttempt())
                                                    {{-- If last attempt is evaluated, show badge with status --}}
                                                    @if ($data->lastAttempt()->system_result === 'EVALUATED')
                                                        <span
                                                            class="badge bg-{{ config('lms.status.' . $data->lastAttempt()->status . '.class', 'dark') }}"
                                                            data-system-result="{{ $data->lastAttempt()->system_result }}"
                                                            data-status="{{ $data->lastAttempt()->status }}">
                                                            {{ in_array($data->lastAttempt()->status, ['FAIL', 'RETURNED']) ? 'NOT SATISFACTORY' : \Str::title($data->lastAttempt()->status) }}
                                                        </span>
                                                        {{-- If last attempt is completed or marked, show badge with status --}}
                                                    @elseif($data->lastAttempt()->system_result === 'COMPLETED' || $data->lastAttempt()->system_result === 'MARKED')
                                                        <span
                                                            class="badge bg-{{ config('lms.status.' . (in_array($data->lastAttempt()->status, ['FAIL', 'RETURNED']) ? 'FAIL' : 'SUBMITTED') . '.class') }}"
                                                            data-system-result="{{ $data->lastAttempt()->system_result }}"
                                                            data-status="{{ $data->lastAttempt()->status }}">
                                                            {{ in_array($data->lastAttempt()->status, ['FAIL', 'RETURNED']) ? 'NOT SATISFACTORY' : \Str::title($data->lastAttempt()->status) }}
                                                        </span>
                                                        {{-- If last attempt is in progress, show Attempting badge --}}
                                                    @elseif($data->lastAttempt()->system_result === 'INPROGRESS')
                                                        <span class="badge bg-dark"
                                                            data-system-result="{{ $data->lastAttempt()->system_result }}"
                                                            data-status="{{ $data->lastAttempt()->status }}">
                                                            Attempting
                                                        </span>
                                                        {{-- If quiz is already submitted, show Submitted badge --}}
                                                    @elseif($data->isAlreadySubmitted())
                                                        <span class="badge bg-{{ config('lms.status.SUBMITTED.class') }}"
                                                            data-system-result="{{ $data->lastAttempt()->system_result }}"
                                                            data-status="{{ $data->lastAttempt()->status }}">
                                                            Submitted
                                                        </span>
                                                    @endif
                                                    {{-- If this is the first item and it's a pre-course lesson that is completed, show Completed badge --}}
                                                    {{-- Logic: Show "Completed" badge for pre-course lesson if satisfied --}}
                                                @elseif(
                                                    $loop->index === 0 &&
                                                        $data->match_pre_course_lesson_title &&
                                                        (isset($data->is_pre_course_lesson) &&
                                                            $data->is_pre_course_lesson &&
                                                            ($pre_course_satisfactory || $old_pre_course_satisfactory)))
                                                    <span
                                                        class="pre_course_lesson badge bg-{{ config('lms.status.COMPLETED.class') }}">
                                                        Completed
                                                    </span>
                                                    {{-- If this is the first item, not matching pre-course lesson title, but is a pre-course lesson and satisfied, show Completed badge --}}
                                                @elseif(
                                                    $loop->index === 0 &&
                                                        (isset($data->match_pre_course_lesson_title) && !$data->match_pre_course_lesson_title) &&
                                                        (isset($data->is_pre_course_lesson) &&
                                                            $data->is_pre_course_lesson &&
                                                            ($pre_course_satisfactory || $old_pre_course_satisfactory)))
                                                    <span
                                                        class="pre_course_lesson badge bg-{{ config('lms.status.COMPLETED.class') }}">
                                                        Completed
                                                    </span>
                                                @endif

                                                {{-- Content title --}}
                                                <h5 id="content-title" class="mb-0 fw-bold" data-id="{{ $data->id }}">
                                                    {{ $data->title }}
                                                </h5>
                                            </div>

                                            {{-- Actions and lesson info (header right) --}}
                                            <div id="actions-info" class="d-flex flex-column align-items-end flex-shrink-0">
                                                {{-- Action buttons (moved from body to header position) --}}
                                                <div class="d-flex flex-wrap gap-2">
                                                    {{-- Quiz action buttons --}}
                                                    @if (isset($related['type']) && $related['type'] == 'quiz')
                                                        @if (!($old_pre_course_satisfactory && $is_pre_course_lesson))
                                                            @if ($data->isAllowed())
                                                                <a href="{{ route($related['route'], [$data['id'], $data['slug']]) }}"
                                                                    class="btn btn-primary btn-sm waves-effect waves-float waves-light">
                                                                    Proceed
                                                                </a>
                                                            @elseif($data->isAllowed() && in_array($related['data'][$loop->index - 1]->lastAttempt()?->status, ['SUBMITTED']))
                                                                <a href="{{ route($related['route'], [$data['id'], $data['slug']]) }}"
                                                                    class="btn btn-primary btn-sm waves-effect waves-float waves-light">
                                                                    Proceed
                                                                </a>
                                                            @endif
                                                            @if (
                                                                !empty($data->lastAttempt()) &&
                                                                    in_array($data->lastAttempt()->status, ['FAIL', 'RETURNED', 'SATISFACTORY', 'SUBMITTED']))
                                                                <a href="{{ route('frontend.lms.quizzes.attempt', [$data, $data->lastAttempt()->id]) }}"
                                                                    class="btn btn-secondary btn-sm waves-effect waves-float waves-light"
                                                                    target="_blank">
                                                                    View Last Attempt
                                                                </a>
                                                            @endif
                                                        @endif
                                                        {{-- Non-quiz action buttons --}}
                                                    @else
                                                        @if ($loop->index === 0)
                                                            <a href="{{ route($related['route'], [$data['id'], $data['slug']]) }}"
                                                                class="btn btn-primary btn-sm waves-effect waves-float waves-light">
                                                                Proceed
                                                            </a>
                                                        @elseif(isset($related['type']) && $related['type'] === 'lesson')
                                                            @if (!(isset($isMainCourse) && $isMainCourse && !$pre_course_satisfactory && !empty($pre_course_assessment)))
                                                                @if (
                                                                    ($pre_course_satisfactory && $loop->index - 1 === 0) ||
                                                                        ($related['data'][$loop->index - 1]->isComplete() || $related['data'][$loop->index - 1]->isSubmitted()))
                                                                    @if ($data->is_allowed || $data->is_unlocked)
                                                                        <a href="{{ route($related['route'], [$data['id'], $data['slug']]) }}"
                                                                            class="btn btn-primary btn-sm waves-effect waves-float waves-light">
                                                                            Proceed
                                                                        </a>
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @elseif(
                                                            $related['data'][$loop->index - 1]['is_completed'] ||
                                                                $related['data'][$loop->index - 1]['is_submitted'] ||
                                                                $related['data'][$loop->index - 1]->isComplete() ||
                                                                $related['data'][$loop->index - 1]->isSubmitted())
                                                            <a href="{{ route($related['route'], [$data['id'], $data['slug']]) }}"
                                                                class="btn btn-primary btn-sm waves-effect waves-float waves-light">
                                                                Proceed
                                                            </a>
                                                        @endif
                                                    @endif
                                                </div>
                                                {{-- If this is a lesson, show work placement and release info --}}
                                                @if (isset($related['type']) && $related['type'] === 'lesson')
                                                    {{-- If this lesson has work placement, show its status --}}
                                                    {{-- Logic: Show "Work Placement Completed" or "Pending Work Placement" based on attachment --}}
                                                    @if (intval($data->has_work_placement ?? 0) === 1)
                                                        @php
                                                            // Check if student completed work placement
                                                            $attachment = \App\Models\StudentLMSAttachables::forEvent(
                                                                'WORK_PLACEMENT',
                                                            )
                                                                ->forAttachable(\App\Models\Lesson::class, $data->id)
                                                                ->where('student_id', auth()->user()->id)
                                                                ?->first();
                                                        @endphp
                                                        @if (!empty($attachment))
                                                            <small class="text-success mt-1">
                                                                Work Placement Completed
                                                            </small>
                                                        @else
                                                            <small class="text-muted">
                                                                Pending Work Placement
                                                            </small>
                                                        @endif
                                                    @endif
                                                    {{-- If lesson is scheduled for future release, show release date --}}
                                                    {{-- Logic: Show release date if not immediate --}}
                                                    @if (
                                                        $data->release_key !== 'IMMEDIATE' &&
                                                            !empty($data->release_plan) &&
                                                            \Carbon\Carbon::parse($data->release_plan)->isFuture())
                                                        <small class="text-muted d-flex align-items-center gap-1">
                                                            <i data-lucide="calendar"></i>
                                                            {{ \Carbon\Carbon::parse($data->release_plan)->format('d/m/Y') }}
                                                        </small>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions and content --}}
                                        @php
                                            $hasActionsContent = false;

                                            // Check if there's quiz content to show
if (isset($related['type']) && $related['type'] == 'quiz') {
    if (!($old_pre_course_satisfactory && $is_pre_course_lesson)) {
        if (
            !empty($data->lastAttempt()) &&
            in_array($data->lastAttempt()->status, [
                'FAIL',
                'RETURNED',
                'SATISFACTORY',
                'SUBMITTED',
            ])
        ) {
            $hasActionsContent = true;
        }
    }
    if (
        isset($related['type']) &&
        $related['type'] == 'topic' &&
        $data->lastAttempt()
    ) {
        $hasActionsContent = true;
    }
} else {
    // Check if there's non-quiz content to show
                                                if ($loop->index === 0) {
                                                    if (
                                                        isset($related['type']) &&
                                                        $related['type'] === 'lesson' &&
                                                        isset($isMainCourse) &&
                                                        $isMainCourse &&
                                                        !$pre_course_satisfactory &&
                                                        !empty($pre_course_assessment)
                                                    ) {
                                                        $hasActionsContent = true;
                                                    }
                                                } elseif (isset($related['type']) && $related['type'] === 'lesson') {
                                                    if (
                                                        isset($isMainCourse) &&
                                                        $isMainCourse &&
                                                        !$pre_course_satisfactory &&
                                                        !empty($pre_course_assessment)
                                                    ) {
                                                        $hasActionsContent = true;
                                                    } elseif (
                                                        ($pre_course_satisfactory && $loop->index - 1 === 0) ||
                                                        ($related['data'][$loop->index - 1]->isComplete() ||
                                                            $related['data'][$loop->index - 1]->isSubmitted())
                                                    ) {
                                                        if (!empty($data->release_plan) && !$data->isAllowed()) {
                                                            $hasActionsContent = true;
                                                        }
                                                    } else {
                                                        $hasActionsContent = true; // Previous lesson not completed message
                                                    }
                                                } elseif (
                                                    $related['data'][$loop->index - 1]['is_completed'] ||
                                                    $related['data'][$loop->index - 1]['is_submitted'] ||
                                                    $related['data'][$loop->index - 1]->isComplete() ||
                                                    $related['data'][$loop->index - 1]->isSubmitted()
                                                ) {
                                                    // Button displayed in header - no content needed
                                                } else {
                                                    $hasActionsContent = true; // Previous item not completed message
                                                }
                                            }
                                        @endphp

                                        @if ($hasActionsContent)
                                            <div id="actions-and-content" class="col mt-1">
                                                {{-- Quiz actions --}}
                                                {{-- If this is a quiz --}}
                                                @if (isset($related['type']) && $related['type'] == 'quiz')
                                                    {{-- If this is a topic with a quiz attempt, show feedback and attempt info --}}
                                                    {{-- Logic: Show feedback and attempt count for topic quizzes --}}
                                                    @if (isset($related['type']) && $related['type'] == 'topic' && $data->lastAttempt())
                                                        <div
                                                            class="alert alert-{{ config('lms.status.' . $data->lastAttempt()->status . '.class') }}">
                                                            <p class="alert-heading mb-1">
                                                                {{ config('lms.status.' . $data->lastAttempt()?->status . '.message') }}
                                                            </p>
                                                            {{-- Instructor feedback --}}
                                                            @php
                                                                $lastFeedback = $data
                                                                    ->lastAttempt()
                                                                    ->quiz->feedbacks()
                                                                    ->orderBy('id', 'DESC')
                                                                    ->first()?->body;
                                                            @endphp
                                                            @if (!empty($lastFeedback))
                                                                <div class="alert-body">
                                                                    {!! $lastFeedback['message'] !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        {{-- If quiz is locked (not allowed), show attempt count --}}
                                                        @if (!$data->isAllowed())
                                                            <p class="small text-muted mb-0">
                                                                {{ \Str::plural('Attempt', $data->lastAttempt()->attempt) . ': ' . $data->lastAttempt()->attempt . ' out of ' . $data->allowed_attempts }}
                                                            </p>
                                                        @endif
                                                    @endif
                                                    {{-- Non-quiz actions --}}
                                                @else
                                                    {{-- If this is the first item (pre-course assessment), show confirmation and Proceed button --}}
                                                    {{-- Logic: Always allow proceeding for first item, and show special message if pre-course assessment is pending --}}
                                                    @if ($loop->index === 0)
                                                        {{-- If this is a lesson in the main course and pre-course assessment is not yet satisfactory, show waiting message --}}
                                                        @if (isset($related['type']) &&
                                                                $related['type'] === 'lesson' &&
                                                                (isset($isMainCourse) && $isMainCourse) &&
                                                                (!$pre_course_satisfactory && !empty($pre_course_assessment)))
                                                            <p>
                                                                Thank you for submitting
                                                                {{ $pre_course_assessment->quiz->title }}, it will be
                                                                reviewed
                                                                by your trainer.<br>
                                                                Your results will be emailed to you then you can continue
                                                                with
                                                                the course.
                                                            </p>
                                                        @endif
                                                        {{-- button displayed in header --}}
                                                        {{-- If this is a lesson (not first), handle LLN assessment and prerequisites --}}
                                                    @elseif(isset($related['type']) && $related['type'] === 'lesson')
                                                        {{-- If in main course and pre-course assessment is not yet satisfactory, show waiting message --}}
                                                        {{-- Logic: Block proceeding until LLN assessment is complete --}}
                                                        @if (isset($isMainCourse) && $isMainCourse && !$pre_course_satisfactory && !empty($pre_course_assessment))
                                                            <p id="wait-for-lln-assessment" class="mb-0">
                                                                Please wait for language, literacy and numeracy assessment
                                                                results.
                                                            </p>
                                                            {{-- If pre-course is satisfactory and this is the second item, or previous lesson is complete/submitted, allow proceeding --}}
                                                            {{-- Logic: Allow proceeding if prerequisites are met --}}
                                                        @elseif(
                                                            ($pre_course_satisfactory && $loop->index - 1 === 0) ||
                                                                ($related['data'][$loop->index - 1]->isComplete() || $related['data'][$loop->index - 1]->isSubmitted()))
                                                            @if (
                                                                !empty($data->release_plan) &&
                                                                    !$data->isAllowed() &&
                                                                    \Carbon\Carbon::now()->lessThan(\Carbon\Carbon::parse($data->release_plan)))
                                                                <p id="lesson-available-on1">
                                                                    Lesson available on
                                                                    {{ \Carbon\Carbon::parse($data->release_plan)->format('d/m/Y') }}
                                                                </p>
                                                            @endif
                                                            {{-- If previous lesson is not complete, show message and release date if applicable --}}
                                                        @else
                                                            <p id="previous-lesson-not-completed">Previous lesson not
                                                                completed
                                                                yet.</p>
                                                            @if (
                                                                $data->release_key !== 'IMMEDIATE' &&
                                                                    !empty($data->release_plan) &&
                                                                    !$data->isAllowed() &&
                                                                    \Carbon\Carbon::now()->lessThan(\Carbon\Carbon::parse($data->release_plan)))
                                                                <p id="lesson-available-on2">
                                                                    Lesson available on
                                                                    {{ \Carbon\Carbon::parse($data->release_plan)->format('d/m/Y') }}
                                                                </p>
                                                            @endif
                                                        @endif
                                                        {{-- For other types, check if previous item is completed or submitted --}}
                                                        {{-- Logic: Allow proceeding if previous item is completed/submitted, else show not completed message --}}
                                                    @elseif(
                                                        $related['data'][$loop->index - 1]['is_completed'] ||
                                                            $related['data'][$loop->index - 1]['is_submitted'] ||
                                                            $related['data'][$loop->index - 1]->isComplete() ||
                                                            $related['data'][$loop->index - 1]->isSubmitted())
                                                        {{-- button displayed in header --}}
                                                        {{-- If previous item is not completed, show message --}}
                                                    @else
                                                        <p id="previous-item-not-completed">
                                                            Previous {{ $related['type'] }} not completed yet.
                                                        </p>
                                                    @endif
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- If there is no related content --}}
            @else
                {{-- If the post has content, show mark complete button or already completed message --}}
                {{-- Logic: Show "Mark Complete" button if not already completed, else show completed message --}}
                @if ($post->hasContent())
                    @if (
                        (!$is_pre_course_lesson && isset($match_pre_course_lesson_title) && !$match_pre_course_lesson_title) ||
                            (isset($isMainCourse) && !$isMainCourse))
                        @if (isset($type) && $type !== 'quiz' && !$post->isComplete())
                            <div class="d-flex justify-content-center mt-2">
                                <a class="btn btn-primary"
                                    href="{{ route('frontend.lms.' . $type . 's.complete', $post->id) }}"
                                    title="Mark Complete" data-lesson_order="{{ $post->lesson->order ?? '' }}"
                                    data-topic_order="{{ $post->order ?? '' }}"
                                    data-is_pre-course-lesson="{{ $is_pre_course_lesson ?? '' }}"
                                    data-match_pre_course_lesson_title="{{ $match_pre_course_lesson_title ?? '' }}">
                                    {{ \Str::title("Mark {$type} complete") }}
                                </a>
                            </div>
                        @else
                            <div class="d-flex justify-content-center mt-2">
                                <div class="alert alert-success">
                                    <p class="alert-heading mb-0">Already Marked Completed</p>
                                </div>
                            </div>
                        @endif
                    @endif


                    {{-- If there is no content, and pre-course is not satisfactory and this is the main course, show contact message --}}
                    {{-- Logic: Show contact message if no content and pre-course not satisfied --}}
                @else
                    @if (!$pre_course_satisfactory && (isset($isMainCourse) && $isMainCourse))
                        <div class="d-flex justify-content-center mt-2">
                            <div class="alert alert-secondary">
                                <p class="alert-heading mb-0">
                                    Please contact {{ env('APP_NAME') }} to proceed forward with this course.
                                </p>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            {{-- Navigation buttons --}}
            {{-- Only show navigation if not a pre-course lesson --}}
            @if (!$is_pre_course_lesson && (isset($match_pre_course_lesson_title) && !$match_pre_course_lesson_title))
                <div class='d-flex justify-content-between align-items-start mt-3 gap-2'>
                    {{-- Previous Button --}}
                    <div class="d-flex flex-column align-items-start">
                        @if (!empty($previous))
                            <a href='{{ $previous['link'] }}' class='btn btn-flat-primary waves-effect'
                                title='Go to previous {{ $previous['type'] }}'>
                                <i data-lucide="chevron-left" class="me-1"></i>
                                Previous {{ ucfirst($previous['type']) }}
                            </a>
                        @endif
                    </div>

                    {{-- Next Button --}}
                    @if (!empty($next))
                        @php
                            // Determine if the next button should be enabled
                            $canProceed = false;
                            $disabledReason = '';
                            $nextButtonText = 'Next ' . ucfirst($next['type']);

                            if ($type === 'lesson') {
                                // For lesson navigation
                                if (!empty($next['data']) && $next['type'] === 'lesson') {
                                    // Navigating to another lesson
                                    if (
                                        isset($isMainCourse) &&
                                        $isMainCourse &&
                                        !$pre_course_satisfactory &&
                                        !empty($pre_course_assessment)
                                    ) {
                                        $disabledReason =
                                            'Please wait for language, literacy and numeracy assessment results';
                                    } elseif (
                                        ($pre_course_satisfactory && $post->order === 0) ||
                                        ($post->isComplete() || $post->isAttempted())
                                    ) {
                                        if (isset($next['is_allowed']) && $next['is_allowed']) {
                                            $canProceed = true;
                                        } else {
                                            $disabledReason =
                                                'Available on ' .
                                                \Carbon\Carbon::parse($next['release_plan'])->format('d/m/Y');
                                        }
                                    } else {
                                        if (
                                            isset($next['release_key']) &&
                                            $next['release_key'] !== 'IMMEDIATE' &&
                                            isset($next['is_allowed']) &&
                                            !$next['is_allowed']
                                        ) {
                                            $disabledReason =
                                                'Available on ' .
                                                \Carbon\Carbon::parse($next['release_plan'])->format('d/m/Y');
                                        } else {
                                            $disabledReason = 'Complete all topics first';
                                        }
                                    }
                                }
                            } elseif ($type === 'topic') {
                                // For topic navigation
                                if ($next['type'] === 'topic') {
                                    // Navigating to another topic
                                    if ($post->isComplete() || $post->isSubmitted()) {
                                        $canProceed = true;
                                    } else {
                                        $disabledReason = 'Complete this topic first';
                                    }
                                } elseif ($next['type'] === 'lesson') {
                                    // Navigating back to lesson (all topics completed)
                                    if ($post->isComplete() || $post->isSubmitted()) {
                                        $canProceed = true;
                                        $nextButtonText = 'Back to Lesson';
                                    } else {
                                        $disabledReason = 'Complete this topic first';
                                        $nextButtonText = 'Back to Lesson';
                                    }
                                }
                            }
                        @endphp

                        <div class="d-flex flex-column align-items-end">
                            @if ($canProceed)
                                <a href='{{ $next['link'] }}'
                                    class='btn btn-flat-primary waves-effect waves-float waves-light'
                                    title='Go to {{ $next['type'] }}'>
                                    {{ $nextButtonText }}
                                    <i data-lucide="chevron-right" class="ms-1"></i>
                                </a>
                            @else
                                <button type='button' class='btn btn-flat-primary waves-effect waves-float waves-light'
                                    disabled title='{{ $disabledReason }}'>
                                    {{ $nextButtonText }}
                                    <i data-lucide="chevron-right" class="ms-1"></i>
                                </button>
                                @if ($disabledReason)
                                    <small class="text-muted mt-1">{{ $disabledReason }}</small>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/components/components-accordion.js')) }}"></script>

    <script src="{{ asset(mix('js/scripts/_my/lms.js')) }}"></script>
    <script>
        $(function() {
            // If continueAttempt element exists, log to console
            // Logic: Check for #continueAttempt and log if present
            if ($('#continueAttempt').length > 0) {
                console.log('continueAttempt found');
                //Sidebar.expendParentAccordion($('#continueAttempt'));
            }
        });
    </script>
@endsection
