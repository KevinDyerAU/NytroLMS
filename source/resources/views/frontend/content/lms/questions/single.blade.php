<div class="row">
    <div class="mb-1 col-12">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
        <hr />
        @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct')
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <p class="col-lg-6 col-12">Previously marked as "Correct Answer"</p>
                    @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                        <p class="col-lg-6 col-12"> Comment:
                            {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                    @endif
                </div>
            </div>
        @else
            @if (!empty($last_attempt[$question->id]))
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-center">
                        <p class="col-lg-6 col-12">Previously marked as "Incorrect Answer"</p>
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12"> Comment:
                                {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>
            @endif

        @endif
        <input class="form-control" type="text" name="answer[{{ $question->id }}]"
            value='{{ $attempted_answers[$question->id] ?? ($last_attempt[$question->id] ?? '') }}'
            id="answer_{{ $question->id }}"
            @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct') disabled
                @else
                {{ $question->required ? 'required=required' : '' }} @endif />
    </div>
</div>

<style>
    .question-content img {
        max-width: 600px !important;
        height: auto;
    }
</style>
