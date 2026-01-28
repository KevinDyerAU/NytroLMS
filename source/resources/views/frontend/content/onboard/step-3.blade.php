<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">
    <form method="POST" action="{{ route('frontend.onboard.store', ['step' => $step, 'resumed' => 1]) }}">
        @csrf
        <div class="row">
            <div class='col-lg-12 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Enter your current employment information (where
                            applicable):</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="organization_name" input-class="" label-class="" tabindex="1" autofocus
                            type="text"
                            value="{{ old('organization_name') ?? ($enrolment['organization_name'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="your_position" input-class="" label-class="" tabindex="1" autofocus
                            type="text"
                            value="{{ old('your_position') ?? ($enrolment['your_position'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="supervisor_name" input-class="" label-class="" tabindex="1" autofocus
                            type="text"
                            value="{{ old('supervisor_name') ?? ($enrolment['supervisor_name'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="street_address" input-class="autocomplete-address" label-class=""
                            tabindex="10" placeholder="Address" type="text"
                            value="{{ old('street_address') ?? ($enrolment['street_address'] ?? '') }}"></x-forms.input>
                    </div>

                    <div class="mb-1 col-md-6">
                        <x-forms.input name="postcode" input-class="" label-class="" tabindex="1" autofocus
                            type="number" value="{{ old('postcode') ?? ($enrolment['postcode'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="telephone" input-class="" label-class="" tabindex="1" autofocus
                            type="text" value="{{ old('telephone') ?? ($enrolment['telephone'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="fax" input-class="" label-class="" tabindex="1" autofocus
                            type="text" value="{{ old('fax') ?? ($enrolment['fax'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="email" input-class="" label-class="" tabindex="1" autofocus
                            type="text" value="{{ old('email') ?? ($enrolment['email'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="website" input-class="" label-class="" tabindex="1" autofocus
                            type="text" value="{{ old('website') ?? ($enrolment['website'] ?? '') }}"></x-forms.input>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            <span class="divider-text text-dark">Validate and Proceed to next step</span>
        </div>
        <div class="d-flex justify-content-between">
            <a class="btn btn-primary btn-prev waves-effect waves-float waves-light"
                href='{{ route('frontend.onboard.create', ['step' => $step - 1, 'resumed' => 1]) }}' id='goto_previous'
                data-step='{{ $step - 1 }}' data-current='{{ $step }}'>
                <i data-lucide="arrow-left" class="align-middle ms-sm-25 ms-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Previous</span>
            </a>
            <button class="btn btn-primary btn-next waves-effect waves-float waves-light" type='submit'
                id='proceed_next'>
                <span class="align-middle d-sm-inline-block d-none">Next</span>
                <i data-lucide="arrow-right" class="align-middle ms-sm-25 ms-0"></i>
            </button>
        </div>
    </form>
</div>
