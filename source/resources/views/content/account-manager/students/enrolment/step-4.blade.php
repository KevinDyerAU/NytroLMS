<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">
    <form method="POST"
        action="{{ route('account_manager.students.update-enrolment', ['student' => $userId, 'step' => $step, 'resumed' => 1]) }}"
        enctype='multipart/form-data'>
        @csrf
        <div class="row">
            <div class='col-lg-12 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Study Reason:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-12">
                        <label class="form-label required" for="study_reason">Study Reason - Of the following
                            categories, which BEST describes your main reason for undertaking this course / traineeship
                            /apprenticeship? </label>
                        <select data-placeholder="Select an Option..." tabindex="1" autofocus
                            class="select2 form-select" data-class=" @error('study_reason') is-invalid @enderror"
                            id="study_reason" name='study_reason'>
                            <option></option>
                            @foreach (config('onboarding.step4.study_reason') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('study_reason') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['study_reason']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('study_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class='col-lg-12 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">USI Required Information:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-12">
                        <p>From January 2015, all students participating in study with a Registered Training
                            Organisation must have a valid USI to be awarded qualification.
                            If you have not yet obtained a USI you can apply for it directly at <a
                                href='https://www.usi.gov.au/students/create-your-usi' class="fw-bolder"
                                target="_blank">https://www.usi.gov.au/students/create-your-usi</a>. </p>
                        <p>A USI is a ten-digit combination of letters and numbers that is unique to each student in
                            Australia. Once you have USI, enter it below.</p>
                        <p>Please contact our office on <span
                                class="fw-bolder">{{ config('settings.site.institute_phone', '1300 471 660') }}</span>
                            or via <span
                                class="fw-bolder">{{ config('settings.site.institute_email', 'admin@keycompany.com.au') }}</span>
                            if you need any support.</p>
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="usi_number">
                            <span class="fw-bolder">USI Number:</span><br>
                            <span id="us_number_label_hint">(Having trouble? Type in 'TBA', and our team will discuss
                                with you during onboarding)</span>
                        </label>
                        <input tabindex="2" type="text"
                            class="form-control @error('usi_number') is-invalid @enderror" id="usi_number"
                            name="usi_number" aria-label="USI Number"
                            value='{{ old('usi_number') ?? ($enrolment['usi_number'] ?? '') }}' />
                        @error('usi_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label required" for="nominate_usi">Nominate your preferred method of contact
                            by the USI Office - for notification of your USI Number &amp; for access to your
                            account:</label>
                        <select data-placeholder="Select an Option..." tabindex="3" class="select2 form-select"
                            data-class=" @error('nominate_usi') is-invalid @enderror" id="nominate_usi"
                            name='nominate_usi'>
                            <option></option>
                            @foreach (config('onboarding.step4.nominate_usi') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('nominate_usi') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['nominate_usi']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('nominate_usi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mb-1 col-md-12">
                    <div class="content-header">
                        <div class="clearfix divider divider-information divider-start-center ">
                            <span class="divider-text text-dark">USI Optional Information:</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-1 col-12">
                            <p>Providing your identification documents below will signify your consent for Key Institute
                                to search for or create a USI (Unique Student Identifier) on your behalf.
                                If you would like us to assist with the USI process, please provide us with options of
                                your identification.</p>
                        </div>
                        <div class="mb-1 col-md-6">
                            <label class="form-label " for="document1_type">Identification Document#1:</label>
                            <select data-placeholder="Select an Option..." tabindex="4" class="select2 form-select"
                                data-class=" @error('document1_type') is-invalid @enderror" id="document1_type"
                                name='document1_type'>
                                <option></option>
                                @foreach (config('onboarding.step4.document_type') as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('document1_type') == $key ? 'selected="selected"' : (!empty($enrolment) && intval($enrolment['document1_type']) === $key ? 'selected="selected"' : '') }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                            @error('document1_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-1 col-md-6" id='show_document1' style='display: none'>
                            <x-forms.input name="document1" input-class="" label-class="" tabindex="5" type="file"
                                value="{{ old('document1') ?? (!empty($enrolment['document1']) ? (is_array($enrolment['document1']) ? json_encode($enrolment['document1']) : $enrolment['document1']) : '') }}"></x-forms.input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-1 col-md-6">
                            <label class="form-label" for="document2_type">Identification Document#2:</label>
                            <select data-placeholder="Select an Option..." tabindex="6" class="select2 form-select"
                                data-class=" @error('document2_type') is-invalid @enderror" id="document2_type"
                                name='document2_type'>
                                <option></option>
                                @foreach (config('onboarding.step4.document_type') as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('document2_type') == $key ? 'selected="selected"' : (!empty($enrolment) && intval($enrolment['document2_type']) === $key ? 'selected="selected"' : '') }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                            @error('document2_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="invalid_document2_type" class='invalid-feedback'>
                                This should be different Identification Document.
                            </div>
                        </div>
                        <div class="mb-1 col-md-6" id='show_document2' style='display: none'>
                            <x-forms.input name="document2" input-class="" label-class="" tabindex="7"
                                type="file"
                                value="{{ old('document2') ?? (!empty($enrolment['document2']) ? (is_array($enrolment['document2']) ? json_encode($enrolment['document2']) : $enrolment['document2']) : '') }}"></x-forms.input>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-1 col-12">
                            <p class="fw-bolder">Please note that while Key Institute (our trading name) searches
                                and/or creates a USI on your behalf, you will receive a notification from the USI office
                                that Industrial Resolution Australia Pty Ltd (our legal name) has accessed your
                                information.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            <span class="divider-text text-dark">Validate and Complete Enrolment</span>
        </div>
        <div class="d-flex justify-content-between">
            <a class="btn btn-primary btn-prev waves-effect waves-float waves-light"
                href='{{ route('account_manager.students.edit-enrolment', ['student' => $userId, 'step' => 3]) }}'
                id='goto_previous' data-step='{{ $step - 1 }}' data-current='{{ $step }}'>
                <i data-lucide="arrow-left" class="align-middle ms-sm-25 ms-0"></i>
                <span class="align-middle d-sm-inline-block d-none">Previous</span>
            </a>
            <button class="btn btn-primary btn-next waves-effect waves-float waves-light" type='submit'>
                <span class="align-middle d-sm-inline-block d-none">Finish Edit</span>
                <i data-lucide="log-in" class="align-middle ms-sm-25 ms-0"></i>
            </button>
        </div>
    </form>
</div>
