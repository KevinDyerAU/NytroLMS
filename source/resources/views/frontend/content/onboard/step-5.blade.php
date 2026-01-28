<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">
    <form method="POST" action="{{ route('frontend.onboard.store', $step) }}">
        @csrf
        <div class="row">
            <div class='col-lg-12 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Pre-Training Review Assessment:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-12">
                        @if (!empty($quiz) && !$quiz['already_submitted'])
                            @includeWhen(
                                !empty($quiz) && !$quiz['already_submitted'],
                                'frontend.content.lms.quiz-content',
                                [
                                    'post' => $quiz['data'],
                                    'related' => $quiz['related'],
                                    'is_ptr' => $quiz['is_ptr'],
                                    'submitted_answers' => $quiz['submitted_answers'],
                                    'next_step' => $next_step
                                ])
                        @endif
                    </div>
                </div>
                @if (!empty($quiz) && $quiz['already_submitted'])
                    <div class="row">
                        <div class="mb-1 col-md-12">
                            <p>Pre-Training Review submitted. Please proceed to next step.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            @if (!empty($quiz) && !$quiz['already_submitted'])
                <span class="divider-text text-dark">Complete Pre-training assessment to proceed to next step</span>
            @else
                <span class="divider-text text-dark">Click next to proceed</span>
            @endif
        </div>
        <div class="d-flex justify-content-between">
            <a class="btn btn-primary btn-prev waves-effect waves-float waves-light"
                href='{{ route('frontend.onboard.create', ['step' => $step - 1, 'resumed' => 1]) }}' id='goto_previous'
                data-step='{{ $step - 1 }}' data-current='{{ $step }}'>
                <i data-lucide="arrow-left" class="align-middle ms-sm-25 ms-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Previous</span>
            </a>
            @if (!empty($quiz) && $quiz['already_submitted'])
                <button class="btn btn-primary btn-next waves-effect waves-float waves-light" type='submit'
                    id='proceed_next'>
                    <span class="align-middle d-sm-inline-block d-none">Next</span>
                    <i data-lucide="arrow-right" class="align-middle ms-sm-25 ms-0"></i>
                </button>
            @endif
        </div>
    </form>
</div>
