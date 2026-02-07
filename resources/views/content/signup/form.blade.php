<div class='row'>
    <div class="col-md-6 col-12">
        <x-forms.input name="first_name" input-class="" label-class="required" placeholder="First name" type="text"
            value="{{ old('first_name') }}" autofocus></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="last_name" input-class="" label-class="required" type="text" placeholder="Last name"
            value="{{ old('last_name') }}"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="email" input-class="" label-class="required" type="email" placeholder="Email"
            value="{{ old('email') }}"></x-forms.input>
    </div>
    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="timezone">Timezone</label>
            <select data-placeholder="Select your Timezone..." class="select2 form-select"
                data-class=" @error('timezone') is-invalid @enderror" id="timezone" name='timezone'>
                <option></option>
                @foreach (\App\Models\Timezone::where('region', '=', 'Australia')->get()->pluck('name')->sort() as $timezone)
                    <option value="{{ $timezone }}" {{ old('timezone') == $timezone ? 'selected=selected' : '' }}>
                        {{ $timezone }}
                    </option>
                @endforeach
            </select>
            @error('timezone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <button type="button" class="btn btn-sm btn-flat-primary float-end" id="toggle-method-phone" title="Phone"
            data-bs-placement="top"
            data-bs-content="Student’s phone number, we will call the student within one business day to welcome them, answer any questions they have and encourage them to get started."
            data-bs-toggle="popover" data-bs-container="body"><i data-lucide="info"
                style="width: 50px; height: 50px;"></i>
        </button>
        {{-- Already has phone validation. only allows numbers --}}
        <x-forms.input name="phone" input-class="" label-class="required" placeholder="Phone Number" type="text"
            value="{{ old('phone') }}"></x-forms.input>
    </div>
    <div class="row">
        <div class="col-md-6 col-12">
            <button type="button" class="btn btn-sm btn-flat-primary float-end" id="toggle-method-password"
                title="Password" data-bs-placement="top"
                data-bs-content="Student’s initial password, once enrolled student will receive an email with their login information and can chose their new password."
                data-bs-toggle="popover" data-bs-container="body"><i data-lucide="info"></i>
            </button>
            <div class="mb-1">
                <label class="form-label required" for="password">Password</label>
                <input type="password" placeholder="Password"
                    class="form-control  @error('password') is-invalid @enderror" id="password" name="password"
                    aria-label="Password" autocomplete="off" />
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6 col-12">
            <div class="mb-1">
                <label class="form-label required" for="password_confirmation">Password Confirmation</label>
                <input type="password" placeholder="Retype Same Password"
                    class="form-control  @error('password_confirmation') is-invalid @enderror  mt-50"
                    id="password_confirmation" name="password_confirmation" aria-label="Password Confirmation"
                    autocomplete="off" />
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
    <div class='col-12 mt-2 d-flex justify-content-between flex-wrap mt-2 mt-md-1'>
        <div class='mb-0'>
            <button type="submit" class="btn btn-primary me-1 waves-effect waves-float waves-light">Register
            </button>
        </div>
    </div>
</div>
