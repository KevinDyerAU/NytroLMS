<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">

    <form method="POST"
        action="{{ route('account_manager.students.update-enrolment', ['student' => $userId, 'step' => $step, 'resumed' => 1]) }}">
        @csrf
        <div class='row'>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Personal Details:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="title">Title</label>
                        <input tabindex="1" type="text" autofocus
                            class="form-control @error('title') is-invalid @enderror" id="title"
                            name="title" aria-label="Title" list="title_options"
                            value="{{ old('title') ?? ($enrolment['title'] ?? '') }}" />
                        <datalist id="title_options">
                            <option value="Mr">
                            <option value="Mrs">
                            <option value="Miss">
                            <option value="Mx">
                        </datalist>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="name" input-class="" label-class="required" placeholder="Name"
                            type="text" value="{{ $currentUser->name }}" disabled></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="gender">Select Gender</label>
                        <select data-placeholder="Select your Gender..." tabindex="2" class="select2 form-select"
                            data-class=" @error('gender') is-invalid @enderror" id="gender" name='gender'>
                            <option></option>
                            <option value="male"
                                {{ old('gender') == 'male' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['gender']) === 'male' ? 'selected="selected"' : '') }}>
                                Male
                            </option>
                            <option value="female"
                                {{ old('gender') == 'female' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['gender']) === 'female' ? 'selected="selected"' : '') }}>
                                Female
                            </option>
                            <option value="other"
                                {{ old('gender') == 'other' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['gender']) === 'other' ? 'selected="selected"' : '') }}>
                                Indeterminate/Intersex/Unspecified
                            </option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="dob" input-class="dobdate" label-class="required"
                            placeholder="Date of Birth" tabindex="3" type="text"
                            data-enrolmentdob="{{ !empty($enrolment['dob']) ? $enrolment['dob'] : '' }}"
                            value="{{ old('dob', !empty($enrolment['dob']) ? \Carbon\Carbon::parse($enrolment['dob'])->format('Y-m-d') : '') }}"></x-forms.input>

                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="home_phone" input-class="" label-class="" tabindex="5" type="text"
                            value="{{ old('home_phone') ?? ($enrolment['home_phone'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="mobile" input-class="" label-class="required" tabindex="6"
                            type="text" value="{{ old('mobile') ?? ($enrolment['mobile'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <label for="birthplace" class="required">Town/City of Birth</label>
                        <input name="birthplace" id="birthplace" tabindex="7" type="text"
                            class="form-control @error('birthplace') is-invalid @enderror"
                            value="{{ old('birthplace') ?? ($enrolment['birthplace'] ?? '') }}" />
                        @error('birthplace')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Emergency Contact:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="emergency_contact_name" input-class="" label-class="required"
                            tabindex="7" type="text"
                            value="{{ old('emergency_contact_name') ?? ($enrolment['emergency_contact_name'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="relationship_to_you" input-class="" label-class="required" tabindex="8"
                            type="text"
                            value="{{ old('relationship_to_you') ?? ($enrolment['relationship_to_you'] ?? '') }}"></x-forms.input>
                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="emergency_contact_number" input-class="" label-class="required"
                            tabindex="9" type="text"
                            value="{{ old('emergency_contact_number') ?? ($enrolment['emergency_contact_number'] ?? '') }}"></x-forms.input>
                    </div>
                </div>
            </div>
            <div class='col-lg-12 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Address:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="residence_address" input-class="autocomplete-address"
                            label-class="required" tabindex="10" placeholder="Address" type="text"
                            value="{{ old('residence_address') ?? ($enrolment['residence_address'] ?? ($currentUser->detail->address ?? '')) }}"></x-forms.input>
                        <x-forms.input name="residence_address_postcode" input-class="" label-class="required"
                            tabindex="10" placeholder="Postcode" type="text"
                            value="{{ old('residence_address_postcode') ?? ($enrolment['residence_address_postcode'] ?? ($currentUser->detail->address_postcode ?? '')) }}"></x-forms.input>

                    </div>
                    <div class="mb-1 col-md-6">
                        <x-forms.input name="postal_address" input-class="autocomplete-address"
                            label-class="required" tabindex="11" placeholder="Address" type="text"
                            value="{{ old('postal_address') ?? ($enrolment['postal_address'] ?? '') }}"></x-forms.input>
                        <x-forms.input name="postal_address_postcode" input-class="" label-class="required"
                            tabindex="11" placeholder="Postcode" type="text"
                            value="{{ old('postal_address_postcode') ?? ($enrolment['postal_address_postcode'] ?? '') }}"></x-forms.input>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="same_address">
                            <label class="form-check-label" for="agreement">Same as Residential Address</label>
                        </div>
                    </div>

                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Language and Cultural Diversity:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="country">In which country were you born?</label>
                        <select data-placeholder="Select your Country..." tabindex="12" class="select2 form-select"
                            data-class="@error('country') is-invalid @enderror" id="country" name='country'>
                            @foreach (App\Models\Country::all() as $country)
                                <option data-icon="{{ $country->flag ?? '' }}"
                                    data-cc="{{ is_string($country->calling_codes) ? $country->calling_codes : '' }}"
                                    data-code="{{ $country->code }}" value="{{ $country->id }}"
                                    {{ old('country') == $country->id
                                        ? 'selected="selected"'
                                        : (!empty($enrolment) && strtolower($enrolment['country']) === strtolower($country->name)
                                            ? 'selected="selected"'
                                            : '') }}>
                                    {{ ucwords($country->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror


                        <label class="form-label required" for="torres_island">Are you of Aboriginal or Torres Strait
                            Islander origin?</label>
                        <select data-placeholder="Select an Option ..." tabindex="13" class="select2 form-select"
                            data-class=" @error('torres_island') is-invalid @enderror" id="torres_island"
                            name='torres_island'>
                            <option></option>
                            @foreach (config('onboarding.step1.torres_island') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('torres_island') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['torres_island']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('torres_island')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="language">Do you speak a language other than English
                            at
                            home?</label>
                        <select data-placeholder="Select your Language..." tabindex="14" class="select2 form-select"
                            data-class=" @error('language') is-invalid @enderror" id="language" name='language'>
                            <option></option>
                            <option value="en"
                                {{ old('language') == 'en' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['language']) === 'english' ? 'selected="selected"' : '') }}>
                                No - English only
                            </option>
                            <option value="other"
                                {{ old('language') == 'other' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['language']) === 'other' ? 'selected="selected"' : '') }}>
                                Yes - Please specify
                            </option>
                        </select>
                        @error('language')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id='show_if_language_other' style='display: none'>
                            <x-forms.input name="language_other" input-class="language-autocomplete"
                                label-class="required" tabindex="15" autofocus type="text"
                                value="{{ old('language_other') ?? ($enrolment['language_other'] ?? '') }}"></x-forms.input>
                            @error('language_other')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <label class="form-label required" for="english_proficiency">Proficiency in Speaking
                                English</label>
                            <select data-placeholder="English Language Proficiency..." tabindex="16"
                                class="select2 form-select"
                                data-class=" @error('english_proficiency') is-invalid @enderror"
                                id="english_proficiency" name='english_proficiency'>
                                <option></option>
                                <option value="Very Well"
                                    {{ old('english_proficiency') == 'Very Well' ? 'selected="selected"' : (!empty($enrolment['english_proficiency']) && $enrolment['english_proficiency'] === 'Very Well' ? 'selected="selected"' : '') }}>
                                    Very Well
                                </option>
                                <option value="Well"
                                    {{ old('english_proficiency') == 'Well' ? 'selected="selected"' : (!empty($enrolment['english_proficiency']) && $enrolment['english_proficiency'] === 'Well' ? 'selected="selected"' : '') }}>
                                    Well
                                </option>
                                <option value="Not Well"
                                    {{ old('english_proficiency') == 'Not Well' ? 'selected="selected"' : (!empty($enrolment['english_proficiency']) && $enrolment['english_proficiency'] === 'Not Well' ? 'selected="selected"' : '') }}>
                                    Not Well
                                </option>
                                <option value="Not at All"
                                    {{ old('english_proficiency') == 'Not at All' ? 'selected="selected"' : (!empty($enrolment['english_proficiency']) && $enrolment['english_proficiency'] === 'Not at All' ? 'selected="selected"' : '') }}>
                                    Not at All
                                </option>
                            </select>
                            @error('english_proficiency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Disability:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="has_disability">Do you consider yourself to have a
                            disability, impairment or long-term condition?</label>
                        <select data-placeholder="Select an Option..." tabindex="16" class="select2 form-select"
                            data-class=" @error('has_disability') is-invalid @enderror" id="has_disability"
                            name='has_disability'>
                            <option></option>
                            <option value="no"
                                {{ old('has_disability') == 'no' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['has_disability']) === 'no' ? 'selected="selected"' : '') }}>
                                No
                            </option>
                            <option value="yes"
                                {{ old('has_disability') == 'yes' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['has_disability']) === 'yes' ? 'selected="selected"' : '') }}>
                                Yes
                            </option>
                        </select>
                        @error('has_disability')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6" id='show_if_has_disability' style='display: none'>
                        <label class="form-label required" for="disabilities">If you indicated the presence of a
                            disability, impairment or long-term condition, please select the area(s) in the following
                            list:</label>
                        <select data-placeholder="Select an Option..." tabindex="17" class="select2 form-select"
                            data-class=" @error('disabilities') is-invalid @enderror" id="disabilities"
                            name='disabilities'>
                            <option></option>
                            @foreach (config('onboarding.step1.disabilities') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('disabilities') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['disabilities']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('disabilities')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <label class="form-label required" for="need_assistance">If you answered YES to the above
                            question do you require any assistance to participate in this course?</label>
                        <select data-placeholder="Select an Option..." tabindex="18" class="select2 form-select"
                            data-class=" @error('need_assistance') is-invalid @enderror" id="need_assistance"
                            name='need_assistance'>
                            <option></option>
                            <option value="no"
                                {{ old('need_assistance') == 'no' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['need_assistance']) === 'no' ? 'selected="selected"' : '') }}>
                                No
                            </option>
                            <option value="yes"
                                {{ old('need_assistance') == 'yes' ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['need_assistance']) === 'yes' ? 'selected="selected"' : '') }}>
                                Yes (We'll arrange a meeting to discuss this with you)
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Industry:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="industry1">Which of the following classifications BEST
                            describes the Industry of your current or previous Employer? If unemployed, proceed to the
                            next section.</label>
                        <select data-placeholder="Select an Option..." tabindex="19" class="select2 form-select"
                            data-class=" @error('industry1') is-invalid @enderror" id="industry1" name='industry1'>
                            <option></option>
                            @foreach (config('onboarding.step1.industry1') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('industry1') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['industry1']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('industry1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" style='margin-bottom:20px;' for="industry2">Which of the
                            following classifications BEST
                            describes your current or recent occupation? If unemployed, go to the next section.</label>
                        <select data-placeholder="Select an Option..." tabindex="20" class="select2 form-select"
                            data-class=" @error('industry2') is-invalid @enderror" id="industry2" name='industry2'>
                            <option></option>
                            @foreach (config('onboarding.step1.industry2') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('industry2') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['industry2']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('industry2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id='show_if_industry2_other' style='display: none'>
                            <x-forms.input name="industry2_other" input-class="" label-class="required"
                                tabindex="15" autofocus type="text"
                                value="{{ old('industry2_other') ?? ($enrolment['industry2_other'] ?? '') }}"></x-forms.input>
                            @error('industry2_other')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Employment:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-12">
                        <label class="form-label required" for="employment">Of the following categories, which BEST
                            describes your current employment status?
                            For casual, seasonal, contract and shift work, use the current number of hours worked per
                            week to determine whether full time (35 hours or more per week) or part-time employed (less
                            than 35 hours per week).</label>
                        <select data-placeholder="Select an Option..." tabindex="21" class="select2 form-select"
                            data-class=" @error('employment') is-invalid @enderror" id="employment"
                            name='employment'>
                            <option></option>
                            @foreach (config('onboarding.step1.employment') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('employment') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['employment']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('employment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            <span class="divider-text text-dark">Validate and Proceed to next step</span>
        </div>
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-next waves-effect waves-float waves-light" type='submit'
                id='proceed_next'>
                <span class="align-middle d-sm-inline-block d-none">Next</span>
                <i data-lucide="arrow-right" class="align-middle ms-sm-25 ms-0"></i>
            </button>
        </div>
    </form>
</div>
