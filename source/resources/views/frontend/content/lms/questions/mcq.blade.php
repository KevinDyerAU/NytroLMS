<div class="row">
    <div class="mb-1 col-12">
        {{--
            Display the question content, but remove any inline style attributes for security/consistency.
            The question content may contain HTML.
        --}}
        <p class="question-content">
            {!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}
        </p>
        <hr />
        {{--
            Show feedback from the last attempt, if any.
            If the last attempt was correct, show a green success alert.
            If the last attempt was incorrect, show a red danger alert.
            Also display any evaluation comment if present.
        --}}
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

        {{--
            If the user hasn't attempted this question yet, prompt them to select options.
        --}}
        @if (empty($last_attempt[$question->id]))
            <h5 class="text-muted">Select one or more options from below:</h5>
        @endif

        {{--
            Decode the correct answers for this question, if available.
            $correctAnswers will be an associative array of correct MCQ option IDs.
        --}}
        @if (!empty($correct_answers[$question->id]))
            @php $correctAnswers = json_decode($correct_answers[$question->id], true) @endphp
        @endif

        {{--
            Render all MCQ options for this question as checkboxes.
            - The checkbox is checked if the user has attempted this option (either in the current attempt or last attempt).
            - The checkbox is disabled if the last attempt was correct (to prevent further changes).
            - The checkbox background is colored green/red if the last attempt matches the correct/incorrect answer.
            - Various data attributes are set for JS or backend processing.
        --}}
        <div class='d-flex flex-row justify-content-evenly mt-3 mb-5'>
            @foreach ($question->options['mcq'] as $mcqId => $value)
                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input
                           {{--
                               If this option was attempted in the last attempt, and is a correct answer, add bg-success.
                               If attempted but incorrect, add bg-danger.
                           --}}
                           @if (
                               !empty($correctAnswers[$mcqId]) &&
                                   isset($last_attempt[$question->id][$mcqId]) &&
                                   intval($mcqId) === intval($last_attempt[$question->id][$mcqId])) @if (intval($last_attempt[$question->id][$mcqId]) === intval($correctAnswers[$mcqId]))
                                       bg-success
                                   @else
                                       bg-danger @endif
                           @endif
                           "
                        type="checkbox" name="answer[{{ $question->id }}]" value="{{ $mcqId }}"
                        data-key='{{ $mcqId . '-' . $value }}' id="answer_{{ $question->id . '_' . $loop->index }}"
                        {{--
                            If the user has attempted this option in the current attempt, check the box.
                            Otherwise, if not attempted in this attempt but was checked in the last attempt, check the box.
                        --}}
                        @if (isset($attempted_answers[$question->id][$mcqId]) &&
                                intval($attempted_answers[$question->id][$mcqId]) === intval($mcqId)) data-attempted_answers="{{ isset($attempted_answers[$question->id][$mcqId]) ? intval($attempted_answers[$question->id][$mcqId]) : '' }}"
                            {{ 'checked=checked' }}
                        @elseif(
                            !isset($attempted_answers[$question->id]) &&
                                (isset($last_attempt[$question->id][$mcqId]) &&
                                    intval($last_attempt[$question->id][$mcqId]) === intval($mcqId)))
                            data-last_attempt="{{ isset($last_attempt[$question->id][$mcqId]) ? intval($last_attempt[$question->id][$mcqId]) : '' }}"
                            {{ 'checked=checked' }} @endif
                        {{--
                            If the last attempt was correct, disable all checkboxes to prevent further changes.
                        --}} @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct') {{ 'disabled' }} @endif
                        {{--
                            Additional data attributes for JS or backend use.
                        --}} data-last="{{ $last_attempt[$question->id][$mcqId] ?? '' }}"
                        data-attempt="{{ $attempted_answers[$question->id][$mcqId] ?? '' }}"
                        data-mcqId="{{ $mcqId }}" data-qId="{{ $question->id }}" />
                    <label class="form-check-label"
                        for="answer_{{ $question->id . '_' . $loop->index }}">{{ $value }}</label>
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
