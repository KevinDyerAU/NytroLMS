<div class="row">
    <div class="mb-1 col-12">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
        <hr />
        @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct')
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <p class="col-lg-6 col-12">Previously marked as "Correct Answer"</p>
                    @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                        <p class="col-lg-6 col-12">
                            Comment: {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                    @endif
                </div>
            </div>
        @else
            @if (!empty($last_attempt[$question->id]))
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-center">
                        <p class="col-lg-6 col-12">Previously marked as "Incorrect Answer"</p>
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12">
                                Comment: {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>
            @endif
        @endif
        @if (empty($last_attempt[$question->id]))
            <h5 class='text-muted'>Select one option from below:</h5>
        @endif
        <div class='d-flex flex-row justify-content-evenly mt-3 mb-5'>
            @foreach ($question->options['scq'] as $scqId => $value)
                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input
                           @if (
                               !empty($correct_answers[$question->id]) &&
                                   isset($last_attempt[$question->id]) &&
                                   intval($scqId) === intval($last_attempt[$question->id])) @if (intval($last_attempt[$question->id]) === intval($correct_answers[$question->id])) bg-success @else bg-danger @endif
                           @endif
                       "
                        type="radio" name="answer[{{ $question->id }}]" value="{{ $scqId }}"
                        data-key='{{ $scqId . '-' . $value }}' id="answer_{{ $question->id . '_' . $loop->index }}"
                        @if (isset($attempted_answers[$question->id]) && intval($attempted_answers[$question->id]) === intval($scqId)) data-attempted_answers="{{ isset($attempted_answers[$question->id]) ? intval($attempted_answers[$question->id]) : '' }}"
                        {{ 'checked=checked' }}
                        @elseif(
                            !isset($attempted_answers[$question->id]) &&
                                (isset($last_attempt[$question->id]) && intval($last_attempt[$question->id]) === intval($scqId)))
                            data-last_attempt="{{ isset($last_attempt[$question->id]) ? intval($last_attempt[$question->id]) : '' }}"
                        {{ 'checked=checked' }} @endif
                        @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct') {{ 'disabled' }} @endif data-scqId="{{ $scqId }}"
                        data-qId="{{ $question->id }}" />
                    <label class="form-check-label" for="answer[{{ $question->id }}]">{{ $value }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .question-content img {
        max-width: 600px !important;
        height: auto;
    }
</style>
