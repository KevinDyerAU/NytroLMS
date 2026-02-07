<section id='questions'>
    <form method='POST' action='{{ route('lms.questions.update', $post['content']) }}' class="form form-vertical">
        @if (strtolower($action['name']) === 'edit')
            @method('PUT')
            <input type='hidden' value='{{ md5($post['content']->id) }}' name='v'>
            <input type='hidden' value='{{ $post['content']?->id }}' name='x'>
        @endif
        @csrf
        @if ($errors->any())
            <div class="col-12 alert alert-danger p-2">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row blockUI">
            <div class="col-lg-3 col-md-4 col-sm-12">
                <div class="d-flex justify-content-between flex-column mb-2 mb-md-0 ">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-tag bg-light-primary me-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="feather feather-package font-medium-4">
                                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                                </path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <div>
                            <h4 class="mb-0">Questions</h4>
                        </div>
                    </div>
                    <button type='button' class='btn btn-outline-primary mt-3' onclick='Question.Add()'>Add New
                        Question
                    </button>
                    <ul class="nav nav-pills nav-left flex-column question-navigation mt-2" role="tablist">
                        @if ($questions || old('question'))
                            @php
                                $questionnaire = old('question') ?? $related['lvl1'];
                            @endphp
                            @if ($questionnaire)
                                @foreach ($questionnaire as $question)
                                    @php
                                        $question = is_array($question) ? $question : $question->toArray();
                                        $question['id'] = $question['id'] ?? $loop->index + 1;
                                    @endphp
                                    <li class="nav-item" id='item-{{ $question['id'] }}'>
                                        {{--                                        <span class="handle p-1 cursor-move"><i data-lucide="plus"></i></span> --}}
                                        <a class="nav-link  flex-grow-1 {{ $loop->last ? 'active' : '' }}"
                                            id="questionNo{{ $question['id'] }}" data-bs-toggle="pill"
                                            href="#question-{{ $question['id'] }}"
                                            aria-expanded="{{ $loop->last ? 'true' : 'false' }}" role="tab">
                                            <span class="fw-bold">Question #{{ $loop->index + 1 }}</span>
                                            <input type='hidden' name='question[{{ $question['id'] }}][order]'
                                                value='{{ $question['order'] ?? $loop->index + 1 }}'>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        @endif
                    </ul>
                </div>
            </div>
            <div class="col-lg-9 col-md-8 col-sm-12">
                <div class="tab-content question-content">
                    @if ($questions || old('question'))
                        @php
                            $questionnaire = old('question') ?? $related['lvl1'];
                        @endphp
                        @if ($questionnaire)
                            @foreach ($questionnaire as $question)
                                @php
                                    $question = is_array($question) ? $question : $question->toArray();
                                    $question['id'] = $question['id'] ?? $loop->index + 1;
                                    $question['index'] = $loop->index + 1;
                                @endphp
                                <div role="tabpanel" class="tab-pane {{ $loop->last ? 'active' : '' }}"
                                    id="question-{{ $question['id'] }}" aria-labelledby="question"
                                    aria-expanded="{{ $loop->last ? 'true' : 'false' }}">
                                    @include('content.lms.partials.setup.question', [
                                        'question' => $question,
                                    ])
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
            <div class='col-12 mb-5'>
                <button type="submit" id='saveQuiz'
                    style='display: {{ old('question') || ($related && count($related['lvl1']) > 0) ? 'block' : 'none' }};'
                    class="btn btn-primary me-1 waves-effect waves-float waves-light">Save Questions
                </button>
            </div>
        </div>
    </form>
</section>
