<div class="row">
    @if (!empty($last_attempt[$question->id]))
        @if ($last_attempt_evaluation[$question->id]['status'] === 'correct')
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
    <div class="mb-1 col-12">
        <p>{!! \Helper::populateInput(
            $question->id,
            $question->content,
            $attempted_answers[$question->id] ?? ($last_attempt[$question->id] ?? null),
            !empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct'
                ? true
                : false,
        ) !!}</p>
    </div>
</div>

<style>
    .question-content img {
        max-width: 600px !important;
        height: auto;
    }
</style>
