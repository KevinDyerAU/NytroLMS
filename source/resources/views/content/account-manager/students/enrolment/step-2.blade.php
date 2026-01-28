<div id="{{ $step_detail['slug'] }}" class="content active dstepper-block" role="tabpanel"
    aria-labelledby="{{ $step_detail['slug'] }}-trigger">
    <form method="POST"
        action="{{ route('account_manager.students.update-enrolment', ['student' => $userId, 'step' => $step, 'resumed' => 1]) }}">
        @csrf
        <div class="row">
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark">Schooling Details:</span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-md-12">
                        <label class="form-label required" for="school_level">What is your highest COMPLETED school
                            level? If you are currently enrolled in secondary education, the Highest school level
                            completed refers to the highest school level you have actually completed and not the level
                            you are currently undertaking. For example, if you are currently in Year 10 the Highest
                            school level completed is Year 9.</label>
                        <select data-placeholder="Select an Option..." tabindex="1" class="select2 form-select"
                            data-class=" @error('school_level') is-invalid @enderror" id="school_level"
                            name='school_level'>
                            <option></option>
                            @foreach (config('onboarding.step2.school_level') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('school_level') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['school_level']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('school_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-12">
                        <label class="form-label required" for="secondary_level">Are you still enrolled in secondary or
                            senior secondary education?</label>
                        <select data-placeholder="Select an Option..." tabindex="2" class="select2 form-select"
                            data-class=" @error('secondary_level') is-invalid @enderror" id="secondary_level"
                            name='secondary_level'>
                            <option></option>
                            @foreach (config('onboarding.step2.secondary_level') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('secondary_level') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['secondary_level']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('secondary_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class='col-lg-6 col-12'>
                <div class="content-header">
                    <div class="clearfix divider divider-secondary ">
                        <span class="divider-text text-dark">
                            Have you SUCCESSFULLY completed ANY courses or qualifications?
                            (If Yes, please select from the list below):
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-1 col-12">
                        <select data-placeholder="Select an Option..." tabindex="3" class="select2 form-select"
                            data-class=" @error('additional_qualification') is-invalid @enderror"
                            id="additional_qualification" name='additional_qualification'>
                            <option></option>
                            <option value="no"
                                {{ old('additional_qualification') == 'no' ? 'selected="selected"' : (isset($enrolment['additional_qualification']) && strtolower($enrolment['additional_qualification']) === 'no' ? 'selected="selected"' : '') }}>
                                No
                            </option>
                            <option value="yes"
                                {{ old('additional_qualification') == 'yes' ? 'selected="selected"' : (isset($enrolment['additional_qualification']) && strtolower($enrolment['additional_qualification']) === 'yes' ? 'selected="selected"' : '') }}>
                                Yes
                            </option>
                        </select>
                        @error('additional_qualification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row" id='show_if_additional_qualification' style='display: none'>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="higher_degree">Bachelor Degree or Higher Degree</label>
                        <select data-placeholder="Select an Option..." tabindex="3" class="select2 form-select"
                            data-class=" @error('higher_degree') is-invalid @enderror" id="higher_degree"
                            name='higher_degree'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('higher_degree') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['higher_degree']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('higher_degree')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="advanced_diploma">Advanced Diploma or Associate
                            Degree</label>
                        <select data-placeholder="Select an Option..." tabindex="4" class="select2 form-select"
                            data-class=" @error('advanced_diploma') is-invalid @enderror" id="advanced_diploma"
                            name='advanced_diploma'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('advanced_diploma') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['advanced_diploma']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('advanced_diploma')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="diploma">Diploma (or Associate Diploma)</label>
                        <select data-placeholder="Select an Option..." tabindex="5" class="select2 form-select"
                            data-class=" @error('diploma') is-invalid @enderror" id="diploma" name='diploma'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('diploma') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['diploma']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('diploma')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="certificate4">Certificate IV (or Advanced
                            Certificate/Technician)</label>
                        <select data-placeholder="Select an Option..." tabindex="6" class="select2 form-select"
                            data-class=" @error('certificate4') is-invalid @enderror" id="certificate4"
                            name='certificate4'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('certificate4') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['certificate4']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('certificate4')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="certificate3">Certificate III (or Trade
                            Certificate)</label>
                        <select data-placeholder="Select an Option..." tabindex="7" class="select2 form-select"
                            data-class=" @error('certificate3') is-invalid @enderror" id="certificate3"
                            name='certificate3'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('certificate3') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['certificate3']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('certificate3')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="certificate2">Certificate II</label>
                        <select data-placeholder="Select an Option..." tabindex="8" class="select2 form-select"
                            data-class=" @error('certificate2') is-invalid @enderror" id="certificate2"
                            name='certificate2'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('certificate2') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['certificate2']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('certificate2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="certificate1">Certificate I</label>
                        <select data-placeholder="Select an Option..." tabindex="9" class="select2 form-select"
                            data-class=" @error('certificate1') is-invalid @enderror" id="certificate1"
                            name='certificate1'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('certificate1') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['certificate1']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('certificate1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-1 col-md-6">
                        <label class="form-label" for="certificate_any">Certificates other than the
                            above</label>
                        <select data-placeholder="Select an Option..." tabindex="10" class="select2 form-select"
                            data-class=" @error('certificate_any') is-invalid @enderror" id="certificate_any"
                            name='certificate_any'>
                            <option></option>
                            @foreach (config('onboarding.step2.education_from') as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('certificate_any') == $key ? 'selected="selected"' : (!empty($enrolment) && strtolower($enrolment['certificate_any']) === strtolower($value) ? 'selected="selected"' : '') }}>
                                    {{ $value }}</option>
                            @endforeach
                        </select>
                        @error('certificate_any')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id='show_certificate_any_details' style='display: none'>
                            <x-forms.input name="certificate_any_details" input-class="" label-class="required"
                                tabindex="11" autofocus type="text"
                                value="{{ old('certificate_any_details') ?? ($enrolment['certificate_any_details'] ?? '') }}"></x-forms.input>
                            @error('certificate_any_details')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix divider divider-primary divider-end ">
            <span class="divider-text text-dark">Validate and Proceed to next step</span>
        </div>
        <div class="d-flex justify-content-between">
            <a class="btn btn-primary btn-prev waves-effect waves-float waves-light"
                href='{{ route('account_manager.students.edit-enrolment', ['student' => $userId, 'step' => 1]) }}'
                id='goto_previous' data-step='{{ $step - 1 }}' data-current='{{ $step }}'>
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
