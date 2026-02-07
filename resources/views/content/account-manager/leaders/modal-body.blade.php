{{-- Leader Information Form --}}
<div class="row g-1 g-md-2 p-1" data-client-validate>
    {{-- First Name input (required) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label required" for="first_name">First Name</label>
            <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name"
                name="first_name" placeholder="First Name" value="{{ old('first_name') ?? ($leader->first_name ?? '') }}"
                required autofocus aria-describedby="first_name_help">
            @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Last Name input (required) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label required" for="last_name">Last Name</label>
            <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name"
                name="last_name" placeholder="Last Name" value="{{ old('last_name') ?? ($leader->last_name ?? '') }}"
                required aria-describedby="last_name_help">
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Email input (required) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label required" for="email">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                name="email" placeholder="Email Address" value="{{ old('email') ?? ($leader->email ?? '') }}" required
                autocomplete="email" aria-describedby="email_help">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Hidden fields --}}
    <input type="hidden" name="country" value="15" />
    <input type="hidden" name="language" value="en" />

    {{-- Timezone select (required) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label required" for="timezone">Timezone</label>
            <select data-placeholder="Select your Timezone..."
                class="select2 form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone"
                required aria-describedby="timezone_help">
                <option value="">Select Timezone...</option>
                @foreach (\App\Models\Timezone::where('region', '=', 'Australia')->get()->pluck('name')->sort() as $timezone)
                    <option value="{{ $timezone }}"
                        {{ old('timezone') == $timezone
                            ? 'selected="selected"'
                            : (!empty($leader) && $leader->detail->timezone === $timezone
                                ? 'selected="selected"'
                                : '') }}>
                        {{ $timezone }}
                    </option>
                @endforeach
            </select>
            @error('timezone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Phone input (optional) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" class="form-control phone-input @error('phone') is-invalid @enderror" id="phone"
                name="phone" placeholder="Phone Number" value="{{ old('phone') ?? ($leader->detail->phone ?? '') }}"
                inputmode="tel" autocomplete="tel" aria-describedby="phone_help">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Address input (optional) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label" for="address">Address</label>
            <input type="text" class="form-control autocomplete-address @error('address') is-invalid @enderror"
                id="address" name="address" placeholder="Address"
                value="{{ old('address') ?? ($leader->detail->address ?? '') }}" autocomplete="street-address"
                aria-describedby="address_help">
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Company select (required) --}}
    <div class='col-md-6 col-12'>
        <label class="form-label required" for="role">Assign Companies</label>
        <select data-placeholder="Assign one or more Companies" class="select2 form-select"
            data-class="@error('company') is-invalid @enderror" id="company" name="company[]" multiple="multiple"
            required aria-describedby="company_help">
            @foreach (App\Models\Company::all() as $company)
                <option value="{{ $company->id }}"
                    {{ old('company') == $company->id
                        ? 'selected="selected"'
                        : (!empty($leader) && in_array($company->id, $leader_companies)
                            ? 'selected="selected"'
                            : '') }}>
                    {{ \Str::title($company->name) }}</option>
            @endforeach
        </select>
        @error('company')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Position input (optional) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label" for="position">Position</label>
            <input type="text" class="form-control @error('position') is-invalid @enderror" id="position"
                name="position" placeholder="Position"
                value="{{ old('position') ?? ($leader->detail->position ?? '') }}" aria-describedby="position_help">
            @error('position')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Role select (required) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label required" for="role">Role</label>
            <select data-placeholder="Select Role..." class="select2 form-select @error('role') is-invalid @enderror"
                id="role" name="role" required aria-describedby="role_help">
                <option value="">Select Role...</option>
                @foreach (config('constants.leader_roles') as $role)
                    <option value="{{ $role }}"
                        {{ old('role') == $role || (!empty($leader) && $leader->detail->role === $role) ? 'selected="selected"' : '' }}>
                        {{ $role }}</option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 col-12">
        {{-- This is a gap --}}
    </div>

    {{-- Password input (required for create) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label @if (strtolower($action['name']) === 'create') required @endif"
                for="password">Password</label>
            <div class="input-group">
                <input type="password" placeholder="Password"
                    class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                    aria-label="Password" autocomplete="new-password"
                    @if (strtolower($action['name']) === 'create') required minlength="6" @endif />
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="form-text">Minimum 6 characters</div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Password confirmation (required for create) --}}
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label @if (strtolower($action['name']) === 'create') required @endif"
                for="password_confirmation">Confirm Password</label>
            <div class="input-group">
                <input type="password" placeholder="Retype Same Password"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" name="password_confirmation" aria-label="Password Confirmation"
                    autocomplete="new-password" @if (strtolower($action['name']) === 'create') required @endif />
                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmation">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>


    {{-- Form Actions --}}
    <div class="col-12">
        <div class="form-actions mt-2 pt-2 mt-1 pt-1 mt-md-2 pt-md-2 border-top">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 gap-md-2">
                <div class="d-flex gap-1 gap-md-2">
                    {{-- Submit button --}}
                    <button type="submit" id="submitBtn"
                        class="btn btn-success btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1">
                        <i data-lucide="check" class="icon-md"></i>
                        <span class="d-inline">{{ \Str::title($action['name'] ?? 'Submit') }}</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                    </button>

                    {{-- Cancel button (go back) --}}
                    <button type="reset"
                        class="btn btn-outline-secondary btn-lg btn-sm btn-md-lg d-flex align-items-center gap-1"
                        id="cancel" data-bs-dismiss="modal">
                        <i data-lucide="x" class="icon-md"></i>
                        <span class="d-inline">Cancel</span>
                    </button>
                </div>
            </div>
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
    textarea.form-control::placeholder {
        color: #b0b0b0 !important;
        opacity: 1;
    }
</style>
