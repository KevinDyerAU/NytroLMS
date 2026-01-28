<section class="vertical-wizard">
    <style>
        @media (min-width: 768px) {

            /* Keep step list as a single vertical column and align with content */
            .vertical-wizard .bs-stepper {
                display: flex;
            }

            .vertical-wizard .bs-stepper-header {
                display: flex;
                flex-direction: column !important;
                flex-wrap: nowrap !important;

                /* step column width */
                max-height: 800px;
                overflow-y: auto;
                overflow-x: hidden;
                align-self: flex-start;
                /* don't stretch taller than content */
            }

            .vertical-wizard .bs-stepper-content {
                flex: 1 1 auto;
                /* max-height: 800px; */
                overflow-y: auto;
            }

            /* Keep header height constrained to content height */
            .vertical-wizard .bs-stepper {
                display: flex;
            }

            .vertical-wizard .bs-stepper-header {
                align-self: flex-start;
                /* do not stretch to full height */
                max-height: 800px;
                /* respect overall cap if any */
                overflow-y: auto;
            }

            .vertical-wizard .bs-stepper-content {
                flex: 1 1 auto;
                max-height: 800px;
                overflow-y: auto;
            }
        }
    </style>
    @if (!empty($is_ptr))
        <div class="alert alert-info mb-2 p-1">This is a Pre-Training Review (PTR) assessment. Please complete all
            questions to proceed with onboarding.</div>
    @elseif(!empty($is_ptr_quiz) && !empty($ptr_course_title))
        <div class="alert alert-info mb-2 p-1">
            <strong>Course: {{ $ptr_course_title }}</strong>
        </div>
    @endif
    @if (!empty($related['data']))
        <div class="bs-stepper vertical vertical-wizard-wrapper" id='lms-quiz'
            data-last-question="{{ $next_step['step'] }}" data-ptr-quiz-id="{{ config('ptr.quiz_id') }}"
            data-quiz-id="{{ $post->id }}">
            <div class="bs-stepper-header">
                @foreach ($related['data'] as $question)
                    <div class="step questionStep {{ in_array($question->id, $submitted_answers ?? []) ? 'submitted' : '' }}"
                        data-index="{{ $loop->index . ',' . $loop->count }}" data-order="{{ $question->order }}"
                        data-qid="{{ $question->id }}" data-target="#q{{ $question->slug . '' . $question->id }}"
                        role="tab" id="q{{ $question->slug . '' . $question->id }}-trigger">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-box">{{ $loop->index + 1 }}</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">{{ $question->title }}</span>
                            </span>
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="bs-stepper-content">
                <form id="quizHolder">
                    @foreach ($related['data'] as $question)
                        <div id="q{{ $question->slug . '' . $question->id }}" class="content" role="tabpanel"
                            aria-labelledby="q{{ $question->slug . '' . $question->id }}-trigger">
                            <div class="content-header">
                                <h5 class="mb-0">
                                    {{ $question->title }}
                                    @if ($question->required)
                                        <small class="required-before text-danger">(Answer Required)</small>
                                    @endif
                                </h5>
                            </div>
                            <div class='blockUI'>
                                @switch($question->answer_type)
                                    @case('SCQ')
                                        @include('frontend.content.lms.questions.scq')
                                    @break

                                    @case('MCQ')
                                        @include('frontend.content.lms.questions.mcq')
                                    @break

                                    @case('SORT')
                                        @include('frontend.content.lms.questions.sort', [
                                            'loopIndex' =>
                                                !empty($question->sort) && $question->sort > 0
                                                    ? $question->sort
                                                    : $loop->index + 1,
                                        ])
                                    @break

                                    @case('MATRIX')
                                        @include('frontend.content.lms.questions.matrix')
                                    @break

                                    @case('FILE')
                                        @include('frontend.content.lms.questions.file')
                                    @break

                                    @case('SINGLE')
                                        @include('frontend.content.lms.questions.single')
                                    @break

                                    @case('BLANKS')
                                        @include('frontend.content.lms.questions.blanks')
                                    @break

                                    @case('ASSESSMENT')
                                        @include('frontend.content.lms.questions.assessment')
                                    @break

                                    @case('TABLE')
                                        @include('frontend.content.lms.questions.table')
                                    @break

                                    @default
                                        @include('frontend.content.lms.questions.essay')
                                @endswitch
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary btn-prev"
                                    onclick='Quiz.previous({{ $loop->index }})'
                                    {{ $loop->index === 0 ? 'disabled="disabled"' : '' }}>
                                    <i data-lucide="arrow-left" class="align-middle me-sm-25 me-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none"> Previous Question</span>
                                </button>
                                <button class="btn btn-primary btn-next" id='step_action{{ $question['order'] }}'
                                    onclick='Quiz.validate{{ $question->answer_type }}({{ '"content_' .
                                        $question->id .
                                        '","' .
                                        csrf_token() .
                                        '",' .
                                        $question->id .
                                        ',' .
                                        $question->quiz_id .
                                        ',' .
                                        auth()->user()->id .
                                        ',"' .
                                        ($next_step['last_question_id'] === $question->id
                                            ? (!empty($post)
                                                ? ($post->id === config('ptr.quiz_id')
                                                    ? route('frontend.dashboard')
                                                    : ($post->id === config('constants.precourse_quiz_id', 0)
                                                        ? route('frontend.onboard.create', ['step' => 5, 'resumed' => 1])
                                                        : route('frontend.lms.topics.show', [$post->topic->id, $post->topic->slug])))
                                                : '')
                                            : '') .
                                        '"' }})'
                                    data-questionID="{{ intval($question->id) }}"
                                    data-lastQuestion="{{ intval($next_step['last_question_id']) }}">
                                    <span id="submit-question" class="align-middle d-sm-inline-block d-none">
                                        Submit Question
                                    </span>
                                    <i data-lucide="arrow-right" class="align-middle ms-sm-25 ms-0"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </form>
            </div>
        </div>
    @else
        <p>No questions</p>
    @endif
</section>
