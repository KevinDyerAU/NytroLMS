{{-- Student Information Form --}}
<div class="row g-1 g-md-2 p-1" data-client-validate>
    {{-- Existing Student Select (combobox) - Only show on create --}}
    @if (strtolower($action['name']) === 'create' && !empty($all_students) && auth()->user()->isLeader())
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label" for="existing_student">Select Existing Student</label>
                <select class="form-select select2" id="existing_student" name="existing_student"
                    data-placeholder="Select a student...">
                    <option></option>
                    @foreach ($all_students as $s)
                        <option value="{{ $s->id }}"
                            data-is-inactive="{{ ($s->detail && $s->detail->status === 'INACTIVE') || !$s->is_active ? '1' : '0' }}"
                            {{ old('existing_student') == $s->id ? 'selected' : '' }}>
                           {{ $s->id }} - {{ $s->first_name }} {{ $s->last_name }} ({{ $s->email }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <hr class="my-2" />
    @endif


    {{-- First Name input (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="first_name">First Name</label>
            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                name="first_name" placeholder="First Name"
                value="{{ old('first_name') ?? ($student->first_name ?? '') }}" required
                aria-describedby="first_name_help">
            @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Preferred Name input (optional) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label" for="preferred_name">Preferred Name</label>
            <input type="text" class="form-control @error('preferred_name') is-invalid @enderror" id="preferred_name"
                name="preferred_name" placeholder="Preferred Name"
                value="{{ old('preferred_name') ?? ($student->detail->preferred_name ?? '') }}"
                aria-describedby="preferred_name_help">
            @error('preferred_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Last Name input (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="last_name">Last Name</label>
            <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                name="last_name" placeholder="Last Name" value="{{ old('last_name') ?? ($student->last_name ?? '') }}"
                required aria-describedby="last_name_help">
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>


    {{-- Preferred Language input --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label" for="preferred_language">Preferred Language</label>
            <input type="text" class="form-control @error('preferred_language') is-invalid @enderror"
                id="preferred_language" name="preferred_language" placeholder="Preferred Language"
                value="{{ old('preferred_language') ?? ($student->detail->preferred_language ?? '') }}"
                aria-describedby="preferred_language_help">
            @error('preferred_language')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    {{-- Email input (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="email">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                name="email" placeholder="Email Address" value="{{ old('email') ?? ($student->email ?? '') }}"
                required autocomplete="email" aria-describedby="email_help">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Phone input (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="phone">Phone Number</label>
            <input type="text" class="form-control phone-input @error('phone') is-invalid @enderror" id="phone"
                name="phone" placeholder="Phone Number" value="{{ old('phone') ?? ($student->detail->phone ?? '') }}"
                required inputmode="numeric" autocomplete="tel" aria-describedby="phone_help">
            <div id="phone_help" class="form-text">
                <small class="text-muted">We'll call within 1 business day to welcome the student and answer
                    any
                    questions</small>
            </div>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Address input (only for non-leaders) --}}
    @if (!auth()->user()->isLeader())
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label" for="address">Address</label>
                <input type="text" class="form-control autocomplete-address @error('address') is-invalid @enderror"
                    id="address" name="address" placeholder="Address"
                    value="{{ old('address') ?? ($student->detail->address ?? '') }}" autocomplete="street-address"
                    aria-describedby="address_help">
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif


    {{-- Purchase Order input (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="purchase_order">Purchase Order Number</label>
            <input type="text" class="form-control @error('purchase_order') is-invalid @enderror"
                id="purchase_order" name="purchase_order" placeholder="Select TBA or Enter Purchase Order Number"
                value="{{ old('purchase_order') ?? ($student->detail->purchase_order ?? '') }}" required
                list="purchase_order_options" aria-describedby="purchase_order_help">
            <datalist id="purchase_order_options">
                <option value="TBA">
            </datalist>
            <div id="purchase_order_help" class="form-text">
                <small class="text-muted">If unknown, enter 'TBA' and source it later. Invoices will be
                    sent
                    within 1 week.</small>
            </div>
            @error('purchase_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Hidden language field (default to English) --}}
    <input type="hidden" name="language" value="en" />

    {{-- Learning Schedule select (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="schedule">Learning Schedule</label>
            <select data-placeholder="Choose learning schedule..." class="select2 form-select"
                data-class=" @error('schedule') is-invalid @enderror" id="schedule" name="schedule" required>
                <option></option>
                @foreach ($schedule as $item)
                    <option value="{{ $item }}"
                        data-selection="{{ !empty($enrolments) && isset($enrolments['basic']) ? $enrolments['basic']['schedule'] : '' }}"
                        {{ $item == old('schedule')
                            ? 'selected'
                            : (!empty($enrolments) && isset($enrolments['basic']) && $item == $enrolments['basic']['schedule']
                                ? 'selected'
                                : '') }}>
                        {{ \Str::title($item) }}
                    </option>
                @endforeach
            </select>
            @error('schedule')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Employment Service select (required) --}}
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label required" for="employment_service">Employment Service</label>
            <select data-placeholder="Choose employment service..." class="select2 form-select"
                data-class=" @error('employment_service') is-invalid @enderror" id="employment_service"
                name="employment_service" required>
                <option></option>
                @foreach ($employment_service as $item)
                    <option value="{{ $item }}"
                        data-selection="{{ !empty($enrolments) && isset($enrolments['basic']) ? $enrolments['basic']['employment_service'] : '' }}"
                        {{ $item == old('employment_service')
                            ? 'selected'
                            : (!empty($enrolments) && isset($enrolments['basic']) && $item == $enrolments['basic']['employment_service']
                                ? 'selected'
                                : '') }}>
                        {{ $item }}
                    </option>
                @endforeach
            </select>
            @error('employment_service')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Site select (if user can assign companies) --}}
    @can('assign companies')
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label required" for="company">Company/Site</label>
                <select data-placeholder="Choose site..." class="select2 form-select"
                    data-class=" @error('company') is-invalid @enderror" id="company" name="company" required>
                    <option></option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ $company->id == old('company')
                                ? 'selected'
                                : (!empty($student) && count($student->companies) > 0 && $company->id == $student->companies->first()->id
                                    ? 'selected'
                                    : '') }}>
                            {{ \Str::title($company->name) }}
                        </option>
                    @endforeach
                </select>
                @error('company')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endcan

    {{-- Attach Leader select (if user can assign leaders) --}}
    @can('assign leaders')
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label required" for="leaders">Leader</label>
                <select data-placeholder="Choose leader..." class="select2 form-select"
                    data-class=" @error('leaders') is-invalid @enderror" id="leaders" name="leaders" required>
                    <option></option>
                    @if (!empty($leaders))
                        @foreach ($leaders as $leader)
                            <option value="{{ $leader->id }}"
                                {{ !empty(old('leaders')) && $leader->id == old('leaders')
                                    ? 'selected'
                                    : (!empty($student) && in_array($leader->id, $student->leaders->pluck('id')->toArray())
                                        ? 'selected'
                                        : '') }}>
                                {{ \Str::title($leader->name) }} ({{ $leader->email }})
                            </option>
                        @endforeach
                    @endif
                </select>
                <div id="leaders-loading" class="d-none d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></div>
                    <span>Loading leaders...</span>
                </div>
                @error('leaders')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endcan

    {{-- Trainer select (if user can assign trainers) --}}
    @can('assign trainers')
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label" for="trainers">Trainer</label>
                <select data-placeholder="Select a Trainer..." class="select2 form-select"
                    data-class=" @error('trainers') is-invalid @enderror" id="trainers" name='trainers'>
                    <option></option>
                    @if (!empty($trainers))
                        @foreach ($trainers as $trainer)
                            <option value="{{ $trainer->id }}"
                                {{ !empty(old('trainers')) && $trainer->id == old('trainers')
                                    ? 'selected=selected'
                                    : (!empty($student) && in_array($trainer->id, $student->trainers->pluck('id')->toArray())
                                        ? 'selected=selected'
                                        : '') }}>
                                {{ \Str::title($trainer->name) . " <{$trainer->email}>" }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('trainers')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endcan

    {{-- Password fields (Edit mode only.) EVERYONE WITH EDIT PERMISSIONS WHO ISNT A LEADER CAN CHANGE PASSWORDS --}}
    @if (!auth()->user()->isLeader() && strtolower($action['name']) === 'edit')
        {{-- Password input --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label @if (strtolower($action['name']) === 'create') required @endif"
                    for="password">Password</label>

                <div class="input-group form-password-toggle input-group-merge">
                    <input type="password" id="account-new-password" name="password" class="form-control"
                        placeholder="Enter new password" />
                    <div class="input-group-text cursor-pointer">
                        <i data-lucide="eye"></i>
                    </div>
                </div>

                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Password confirmation input --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label @if (strtolower($action['name']) === 'create') required @endif"
                    for="password_confirmation">Confirm Password</label>
                <div class="input-group form-password-toggle input-group-merge">
                    <input type="password" class="form-control" id="account-retype-new-password"
                        name="password_confirmation" placeholder="Confirm your new password" />
                    <div class="input-group-text cursor-pointer"><i data-lucide="eye"></i></div>
                </div>
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif

    {{-- Course Enrollment Section (only for create action) --}}
    @if (strtolower($action['name']) === 'create')
        {{-- Course select with optgroups --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="mb-1 mb-0 mb-md-1">
                <label class="form-label required" for="course">Course</label>
                <select data-placeholder="Choose course..." class="select2 form-select"
                    data-class=" @error('course') is-invalid @enderror" id="course" name="course" required>
                    <option></option>
                    @php $category = '' @endphp
                    @foreach ($courses as $course)
                        @if ($category !== $course->category)
                            @if ($category !== '')
                                </optgroup>
                            @endif
                            <optgroup
                                label="{{ config('lms.course_category')[!empty($course->category) ? $course->category : 'uncategorized'] }}">
                        @endif
                        <option data-length="{{ $course->course_length_days }}" value="{{ $course->id }}"
                            {{ old('course') == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                        @php $category = $course->category @endphp
                    @endforeach
                    </optgroup>
                </select>
                @error('course')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Course start/end date fields (hidden for leaders) --}}
        @if (auth()->user()->isLeader())
            <div style="display:none;">
                <input name="course_start_at" id="course_start_at" type="hidden"
                    value="{{ old('course_start_at') ?? \Carbon\Carbon::today(Helper::getTimeZone())->format('Y-m-d') }}" />
                <input name="course_ends_at" id="course_ends_at" class="date-picker-end-length" type="hidden"
                    value="{{ old('course_ends_at') }}" />
            </div>
        @else
            <div class="col-lg-2 col-md-6 col-12">
                <div class="mb-1 mb-0 mb-md-1">
                    <label class="form-label required" for="course_start_at">Course Start Date</label>
                    <input type="date"
                        class="form-control date-picker @error('course_start_at') is-invalid @enderror"
                        id="course_start_at" name="course_start_at" value="{{ old('course_start_at') }}"
                        data-backdate="{{ !!auth()->user()->can('course backdate reg') }}" required
                        aria-describedby="course_start_help" placeholder="DD-MM-YYYY">
                    @error('course_start_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-12">
                <div class="mb-1 mb-0 mb-md-1">
                    <label class="form-label required" for="course_ends_at">Course End Date</label>
                    <input type="date"
                        class="form-control date-picker @error('course_ends_at') is-invalid @enderror"
                        id="course_ends_at" name="course_ends_at" value="{{ old('course_ends_at') }}" required
                        aria-describedby="course_end_help" placeholder="DD-MM-YYYY">
                    @error('course_ends_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Only Semester 1 toggle (if allowed and KeyInstitute) --}}
        @if (env('SETTINGS_KEY') === 'KeyInstitute')
            @can('allow semester only')
                <div class="col-lg-2 col-md-12 col-12 d-flex align-items-start">
                    <div class="form-check form-switch form-check-primary my-auto">
                        <input type="checkbox" class="form-check-input" name="allowed_to_next_course"
                            id="allowed_to_next_course" value="0" aria-describedby="semester_help">
                        <label class="form-check-label fw-semibold" for="allowed_to_next_course">
                            Restrict to Semester 1 Only
                        </label>
                    </div>
                    @error('allowed_to_next_course')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endcan
        @endif
</div>
@endif

{{-- Study Type field (shown for both create and edit) --}}
@if (strtolower($action['name']) === 'edit' &&
        auth()->user()->hasRole(['Admin', 'Root']))
    <div class="col-lg-4 col-md-6 col-12">
        <div class="mb-1 mb-0 mb-md-1">
            <label class="form-label" for="study_type">Study Type</label>
            <select class="form-select @error('study_type') is-invalid @enderror" id="study_type" name="study_type">
                <option value="">None</option>
                @foreach (config('constants.study_type') as $type)
                    <option value="{{ $type }}"
                        {{ old('study_type', $student->study_type ?? '') == $type ? 'selected' : '' }}>
                        {{ $type }}</option>
                @endforeach
            </select>
        </div>
    </div>
@endif
{{-- Form Actions --}}
<div class="form-actions mt-2 pt-2 mt-1 pt-1 mt-md-2 pt-md-2 border-top">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 gap-md-2">
        <div class="d-flex gap-1 gap-md-2">
            {{-- Submit button --}}
            <button type="submit" id="submit-form"
                class="btn btn-success btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1">
                <i data-lucide="check" class="icon-md"></i>
                <span class="d-inline">{{ \Str::title($action['name'] ?? 'Submit') }}</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>

            {{-- Assign Course button (only for edit by Admin/Root) --}}
            @if (strtolower($action['name']) === 'edit' &&
                    auth()->user()->hasRole(['Admin', 'Root']))
                <button type="button" class="btn btn-primary btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1"
                    data-bs-toggle="modal" data-bs-target="#assign-course-sidebar" title="Assign additional courses">
                    <i data-lucide="plus-circle" class="icon-md"></i>
                    <span class="d-inline">Assign Course</span>
                </button>
            @endif

            {{-- Cancel button (go back) --}}
            <button type="reset"
                class="btn btn-outline-secondary btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1"
                id="cancel" data-bs-dismiss="modal">
                <i data-lucide="x" class="icon-md"></i>
                <span class="d-inline">Cancel</span>
            </button>

            {{-- Clear Input button --}}
            {{-- @if (strtolower($action['name']) === 'create')
                        <button type="clear"
                            class="btn btn-danger btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1"
                            id="clear" data-bs-dismiss="modal">
                            <i data-lucide="eraser" class="icon-md"></i>
                            <span class="d-none d-md-inline">Clear</span>
                        </button>
                    @endif --}}
        </div>
    </div>
</div>

<style>
    /* Make input and select placeholder text a lighter grey */
    ::placeholder {
        color: #b0b0b0 !important;
        opacity: 1;
    }

    :-ms-input-placeholder {
        /* Internet Explorer 10-11 */
        color: #b0b0b0 !important;
    }

    ::-ms-input-placeholder {
        /* Microsoft Edge */
        color: #b0b0b0 !important;
    }

    input.form-control::placeholder,
    textarea.form-control::placeholder,
    select.form-select::placeholder {
        color: #b0b0b0 !important;
        opacity: 1;
    }
</style>
