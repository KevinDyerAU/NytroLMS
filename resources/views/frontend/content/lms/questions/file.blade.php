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
        <input class="form-control " name="file[{{ $question->id }}]"
            @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct') @if (isset($attempted_answers[$question->id]) && Storage::exists($attempted_answers[$question->id]))
                       value="{{ Storage::url($attempted_answers[$question->id]) }}" type="hidden" data-type="attempt"
                   @elseif(isset($last_attempt[$question->id]) && Storage::exists($last_attempt[$question->id]))
                       value="{{ Storage::url($last_attempt[$question->id]) }}" type="hidden" data-type="last"
                   @else
                       type="file" @endif
        @else type="file" @endif
        data-format="{{ !empty($question->options['file']['types_allowed']) ? \Str::replace(',', '|', $question->options['file']['types_allowed']) : 'pdf|doc|docx|zip|jpg|jpeg|xls|xlsx|ppt|pptx|png' }}"
        accept="{{ !empty($question->options['file']['types_allowed'])? collect(explode(',', $question->options['file']['types_allowed']))->map(function ($item) {return '.' . $item;})->join(','): '.pdf,.doc,.docx,.zip,.jpg,.jpeg,.xls,.xlsx,.ppt,.pptx,.png' }}"
        id="file_{{ $question->id }}" {{ $question->required ? "required='required'" : '' }} />
        <p class='text-muted my-2'>Allowed File Types: {{ $question->options['file']['types_allowed'] ?? '' }}</p>
        @if (isset($attempted_answers[$question->id]) && Storage::exists($attempted_answers[$question->id]))
            <p>Already uploaded file:</p>
            <a href="{{ Storage::url($attempted_answers[$question->id]) }}" data-type="attempted" target="_blank">View
                File</a>
        @elseif(isset($last_attempt[$question->id]) && Storage::exists($last_attempt[$question->id]))
            <p>Already uploaded file:</p>
            <a href="{{ Storage::url($last_attempt[$question->id]) }}" data-type="last" target="_blank">View File</a>
        @endif
    </div>
</div>

<style>
    .question-content img {
        max-width: 600px !important;
        height: auto;
    }
</style>
