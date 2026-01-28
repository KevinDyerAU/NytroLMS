<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">
    <form method="POST" action="{{ route('frontend.onboard.store', $step) }}" id="step-6-form">
        @csrf
        <div class="row">
            @if (env('SETTINGS_KEY') === 'KeyInstitute')
                @include('frontend.content.onboard.agreement.keyinstitute')
            @else
                @include('frontend.content.onboard.agreement.knowlegespace')
            @endif
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            <span class="divider-text text-dark">Validate and Complete</span>
        </div>
        <div class="d-flex justify-content-between">
            <a class="btn btn-primary btn-prev waves-effect waves-float waves-light"
                href='{{ route('frontend.onboard.create', ['step' => $step - 1, 'resumed' => 1]) }}' id='goto_previous'
                data-step='{{ $step - 1 }}' data-current='{{ $step }}'>
                <i data-lucide="arrow-left" class="align-middle ms-sm-25 ms-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Previous</span>
            </a>
            <button class="btn btn-primary btn-next waves-effect waves-float waves-light" type='submit' id="step-6-submit-btn">
                <span class="align-middle d-sm-inline-block d-none">Finish</span>
                <i data-lucide="log-in" class="align-middle ms-sm-25 ms-0"></i>
            </button>
        </div>
    </form>
</div>

<script>
    (function() {
        const form = document.getElementById('step-6-form');
        const submitBtn = document.getElementById('step-6-submit-btn');

        if (form && submitBtn) {
            let isSubmitting = false;

            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }

                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="align-middle d-sm-inline-block d-none">Processing...</span><i data-lucide="loader" class="align-middle ms-sm-25 ms-0"></i>';

                // Re-enable after 10 seconds as fallback (in case of error)
                setTimeout(function() {
                    if (isSubmitting) {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span class="align-middle d-sm-inline-block d-none">Finish</span><i data-lucide="log-in" class="align-middle ms-sm-25 ms-0"></i>';
                    }
                }, 10000);
            });
        }
    })();
</script>
