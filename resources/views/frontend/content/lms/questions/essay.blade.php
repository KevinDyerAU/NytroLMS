<div class="row">
    <div class="mb-1 col-12">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
        {{-- Show previous attempt and evaluation if available --}}
        @if (!empty($last_attempt[$question->id]))
            {{-- If last attempt was marked correct --}}
            @if ($last_attempt_evaluation[$question->id]['status'] === 'correct')
                <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-stretch">
                        <p class="col-lg-6 col-12">Previously marked as "Correct Answer"</p>
                        {{-- Show evaluator's comment if present --}}
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12"> Comment:
                                {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>

                {{-- Display the answer(s) from last attempt --}}
                <p>Answer:</p>
                @if (is_iterable($last_attempt[$question->id]))
                    <ul>
                        @foreach ($last_attempt[$question->id] as $key => $value)
                            <li>{!! $value !!}</li>
                        @endforeach
                    </ul>
                @else
                    {!! $last_attempt[$question->id] !!}
                @endif
            @else
                {{-- If last attempt was marked incorrect --}}
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-stretch">
                        <p class="col-lg-6 col-12">Previously marked as "Incorrect Answer"</p>
                        {{-- Show evaluator's comment if present --}}
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12"> Comment:
                                {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        {{-- Essay answer textarea, hidden if already correct --}}
        <textarea class="form-control content-tinymce" style="height: 500px;" rows="4"
            @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct') hidden @endif" name="answer[{{ $question->id }}]" id="content_{{ $question->id }}"
            tabindex="1" {{ $question->required ? "required='required'" : '' }}>
            {{-- Prefill with attempted answers if available --}}
            @if (!empty($attempted_answers[$question->id]))
@if (is_iterable($attempted_answers[$question->id]))
{{-- If answer is iterable, show as list --}}
                    <ul>
                    @foreach ($attempted_answers[$question->id] as $key => $value)
<li>{{ $value }}</li>
@endforeach
                    </ul>
@else
{{ $attempted_answers[$question->id] }}
@endif
            {{-- Otherwise, prefill with last attempt if available --}}
@elseif(!empty($last_attempt[$question->id]))
@if (is_iterable($last_attempt[$question->id]))
{{-- If last attempt is iterable, show as list --}}
                    <ul>
                    @foreach ($last_attempt[$question->id] as $key => $value)
<li>{{ $value }}</li>
@endforeach
                    @foreach ($last_attempt[$question->id] as $key => $value)
<li>{{ $value }}</li>
@endforeach
                    </ul>
@else
{{ $last_attempt[$question->id] }}
@endif
@endif
        </textarea>
    </div>
</div>

<style>
    .tox-tinymce {
        min-height: 150px;
    }

    .question-content img {
        max-width: 600px !important;
        height: auto;
    }
</style>
