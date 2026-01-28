<div class='row'>
    <div class='col-12 mx-auto'>

        <div class="accordion accordion-margin mt-2" id="competency-details">
            <div class="card accordion-item">
                <h2 class="accordion-header" id="headingLessonCompetency">
                    <button
                        class="accordion-button collapsed"
                        data-bs-toggle="collapse"
                        role="button"
                        data-bs-target="#lessonDetails"
                        aria-expanded="false"
                        aria-controls="lessonDetails"
                    >
                        <span class="d-flex justify-content-between flex-grow-1">
                            <span class="flex-grow-1">Lesson: {{ $competency->lesson->title }} </span>
                            <small
                                class="text-muted width-250">{{ 'Start Date: '. \Carbon\Carbon::parse($competency->lesson_start)->timezone( Helper::getTimeZone() )->format('d-m-Y') .' - End Date: '. \Carbon\Carbon::parse($competency->lesson_end)->format('d-m-Y') }}</small>
                        </span>
                    </button>
                </h2>
                <div
                    id="lessonDetails"
                    class="collapse accordion-collapse"
                    aria-labelledby="headingLessonCompetency"
                    data-bs-parent="#competency-details"
                >
                    <div class="accordion-body">
                        @foreach( $topics as $topic_id => $topic)
                            <div class="card accordion-item">
                                <h2 class="accordion-header" id="headingTopicCompetency{{ $topic_id }}">
                                    <button
                                        class="accordion-button collapsed"
                                        data-bs-toggle="collapse"
                                        role="button"
                                        data-bs-target="#topicDetails{{ $topic_id }}"
                                        aria-expanded="false"
                                        aria-controls="topicDetails{{ $topic_id }}"
                                    >
                                    <span class="d-flex justify-content-between flex-grow-1">
                                        <span class="flex-grow-1">Topic: {{ $topic['data']['title'] }} </span>
                                    </span>
                                    </button>
                                </h2>
                                <div
                                    id="topicDetails{{ $topic_id }}"
                                    class="collapse accordion-collapse"
                                    aria-labelledby="headingTopicCompetency{{ $topic_id }}"
                                    data-bs-parent="#lessonDetails"
                                >
                                    <div class="accordion-body">
                                        @if(!empty($topic['quizzes']['list']) && $topic['quizzes']['count'] > 0)
                                            @foreach( $topic['quizzes']['list'] as $quiz_id => $quiz)
                                                <div class="card accordion-item">
                                                    <h2 class="accordion-header"
                                                        id="headingQuizCompetency{{ $quiz_id }}">
                                                        <button
                                                            class="accordion-button collapsed"
                                                            data-bs-toggle="collapse"
                                                            role="button"
                                                            data-bs-target="#quizDetails{{ $quiz_id }}"
                                                            aria-expanded="false"
                                                            aria-controls="quizDetails{{ $quiz_id }}"
                                                        >
                                                    <span class="d-flex justify-content-between flex-grow-1">
                                                        <span
                                                            class="flex-grow-1">Quiz: {{ $quiz['data']['title'] }} </span>
                                                    </span>
                                                        </button>
                                                    </h2>
                                                    <div
                                                        id="quizDetails{{ $quiz_id }}"
                                                        class="collapse accordion-collapse"
                                                        aria-labelledby="headingQuizCompetency{{ $quiz_id }}"
                                                        data-bs-parent="#topicDetails{{ $topic_id }}"
                                                    >
                                                        <div class="accordion-body">
                                                        </div>
                                                    </div>
                                                </div>

                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>

                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @include('content.competency.form')
    </div>
</div>
