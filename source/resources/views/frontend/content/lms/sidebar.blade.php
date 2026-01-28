{{--
    LMS Course Sidebar Component

    This component displays course progress and lesson/topic navigation for students.
    It shows:
    1. Overall course progress percentage
    2. Expandable lesson list with topics
    3. Progress tracking for quizzes and submissions
    4. Navigation links to continue learning

    Dependencies:
    - courseProgress session data (set by TopicController)
    - Lesson and Topic models
    - Bootstrap accordion components
--}}
<div class="blog-sidebar my-2 my-lg-0 pb-3">
    {{-- Get course progress data from session --}}
    @php $courseProgress = session('courseProgress', null); @endphp

    @if ($courseProgress)
        {{-- Course Progress Bar Section --}}
        <div class="course-progress">
            <h6 class="section-label">Course Progress</h6>
            {{-- Dynamic progress bar with color coding based on completion percentage --}}
            <div
                class="progress progress-bar-{{ $courseProgress->percentage >= 100 ? 'success' : ($courseProgress->percentage === 0 ? 'secondary' : 'primary') }}">
                <div class="progress-bar" role="progressbar" aria-valuenow="{{ $courseProgress->percentage }}"
                    aria-valuemin="0" aria-valuemax="100"
                    style="width: {{ $courseProgress->percentage >= 15 ? $courseProgress->percentage : 10 + $courseProgress->percentage }}%">
                    {{ $courseProgress->percentage }}%
                </div>
            </div>
        </div>
        {{-- Lesson Navigation Section --}}
        <div class="blog-recent-posts mt-3">
            <h6 class="section-label">Track Course Progress</h6>
            <div class="mt-1">
                {{-- Main accordion container for lessons --}}
                <div class="accordion accordion-margin" id="accordionLessons">
                    @if ($courseProgress->details['lessons']['list'])
                        {{-- Loop through each lesson in the course --}}
                        @foreach ($courseProgress->details['lessons']['list'] as $lesson_id => $lesson)
                            <div class="accordion-item">
                                {{-- Lesson header with collapsible button --}}
                                <h2 class="accordion-header" id="headingLesson{{ $lesson_id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#accordionLesson{{ $lesson_id }}" aria-expanded="false"
                                        aria-controls="accordionLesson{{ $lesson_id }}">
                                        <span class=' text-truncate width-200'> {{ $lesson['data']['title'] }}</span>
                                    </button>
                                </h2>
                                {{-- Lesson content area --}}
                                <div id="accordionLesson{{ $lesson_id }}" class="accordion-collapse collapse"
                                    aria-labelledby="headingLesson{{ $lesson_id }}"
                                    data-bs-parent="#accordionLessons">
                                    <div class="accordion-body">
                                        {{-- Get current and previous lesson models for access control --}}
                                        @php
                                            $currentLesson = !empty($lesson_id)
                                                ? \App\Models\Lesson::where('id', $lesson_id)->first()
                                                : null;
                                            $previousLesson = !empty($lesson['previous'])
                                                ? \App\Models\Lesson::where('id', $lesson['previous'])->first()
                                                : null;
                                        @endphp
                                        {{-- Check if lesson is accessible (completed, submitted, or first lesson) --}}
                                        @if (
                                            (!empty($currentLesson) && ($currentLesson->isComplete() || $currentLesson->isSubmitted())) ||
                                                $lesson['previous'] === 0 ||
                                                (!empty($previousLesson) && ($previousLesson->isComplete() || $previousLesson->isSubmitted())))
                                            {{-- Topics accordion within lesson --}}
                                            <div class="accordion accordion-margin" id="accordionTopics">
                                                @if ($lesson['topics']['list'])
                                                    {{-- Loop through topics within the lesson --}}
                                                    @foreach ($lesson['topics']['list'] as $topic_id => $topic)
                                                        {{-- Individual topic accordion item --}}
                                                        <div class="accordion-item">
                                                            {{-- Topic header with collapsible button --}}
                                                            <h2 class="accordion-header"
                                                                id="headingTopic{{ $topic_id }}">
                                                                <button class="accordion-button collapsed"
                                                                    type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#accordionTopic{{ $topic_id }}"
                                                                    aria-expanded="false"
                                                                    aria-controls="accordionTopic{{ $topic_id }}">
                                                                    <span class=' text-truncate width-200'>
                                                                        {{ !empty($topic['data']) ? $topic['data']['title'] : '' }}</span>
                                                                </button>
                                                            </h2>
                                                            {{-- Topic content area --}}
                                                            <div id="accordionTopic{{ $topic_id }}"
                                                                class="accordion-collapse collapse"
                                                                aria-labelledby="headingTopic{{ $topic_id }}"
                                                                data-bs-parent="#accordionTopics">
                                                                <div class="accordion-body">
                                                                    {{-- Check if previous topic is completed before allowing access --}}
                                                                    @if (
                                                                        $topic['previous'] > 0 &&
                                                                            isset($lesson['topics']['list'][$topic['previous']]) &&
                                                                            !$lesson['topics']['list'][$topic['previous']]['submitted'] &&
                                                                            !$lesson['topics']['list'][$topic['previous']]['completed']
                                                                    )
                                                                        <small class='text-muted'> Previous topic is not
                                                                            submitted yet.</small>
                                                                    @else
                                                                        {{-- Display quiz progress and navigation --}}
                                                                        @if ($topic['quizzes']['count'] > 0)
                                                                            {{-- Show quiz submission progress --}}
                                                                            <p>{!! "Progress: Submitted <strong>{$topic['quizzes']['submitted']}</strong> out of <strong>{$topic['quizzes']['count']}</strong>. " !!} </p>
                                                                            {{-- Show continue button if not all quizzes are submitted --}}
                                                                            @if ($topic['quizzes']['submitted'] !== $topic['quizzes']['count'])
                                                                                <hr />
                                                                                <div
                                                                                    class='d-flex justify-content-start'>
                                                                                    <a href='{{ route('frontend.lms.topics.show', [$topic['data']['id'], $topic['data']['slug']]) }}'
                                                                                        class="btn btn-primary btn-sm me-1 waves-effect waves-float waves-light">Continue</a>
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            {{-- Topic has no quizzes - marked as passed --}}
                                                                            <p class='text-muted'>This topic is marked
                                                                                passed.</p>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    {{-- No topics available for this lesson --}}
                                                    <div class="d-flex justify-content-start align-items-center mb-75">
                                                        <p class='text-dark'>No Related Topics Available.</p>
                                                    </div>
                                                @endif
                                                {{-- Show proceed button if lesson is completed/submitted --}}
                                                @if ($lesson['completed'] || $lesson['submitted'])
                                                    <hr />
                                                    <div class='d-flex justify-content-start'>
                                                        <a href='{{ route('frontend.lms.lessons.show', [$lesson['data']['id'], $lesson['data']['slug']]) }}'
                                                            class="btn btn-primary btn-sm me-1 waves-effect waves-float waves-light">Proceed</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            {{-- Lesson is not accessible - previous lesson not completed --}}
                                            <small class='text-muted'> Previous lesson is not submitted yet.</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- No lessons available for this course --}}
                        <div class="d-flex justify-content-start align-items-center mb-75">
                            <p class='text-dark'>No Related Lessons Available.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    {{-- End of sidebar component --}}
</div>
