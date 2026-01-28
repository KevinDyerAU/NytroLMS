<div class='row'>
    <div class="col-md-6 col-12">
        <x-forms.input name="first_name" input-class="" label-class="required" placeholder="First name" type="text"
            value="{{ old('first_name') ?? ($user->first_name ?? '') }}" autofocus autocomplete="off"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="last_name" input-class="" label-class="required" type="text" placeholder="Last name"
            value="{{ old('last_name') ?? ($user->last_name ?? '') }}" autocomplete="off"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="email" input-class="" label-class="required" type="email" placeholder="Email"
            value="{{ old('email') ?? ($user->email ?? '') }}" autocomplete="off"></x-forms.input>
    </div>

    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="country">Country</label>
            <select data-placeholder="Select your Country..." class="select2-icons form-select"
                data-class="@error('country') is-invalid @enderror" id="country" name='country'>
                @foreach (App\Models\Country::withFlags()->get() as $country)
                    <option data-icon="{{ $country->flag ?? '' }}"
                        data-cc="{{ is_string($country->calling_codes) ? $country->calling_codes : '' }}"
                        data-code="{{ $country->code }}" value="{{ $country->id }}"
                        {{ old('country') == $country->id
                            ? 'selected="selected"'
                            : (!empty($user) && $user->detail->country_id === $country->id
                                ? 'selected="selected"'
                                : '') }}>
                        {{ ucwords($country->name) }}
                    </option>
                @endforeach
            </select>
            @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="timezone">Timezone</label>
            <select data-placeholder="Select your Timezone..." class="select2 form-select"
                data-class=" @error('timezone') is-invalid @enderror" id="timezone" name='timezone'>
                @foreach (\App\Models\Timezone::where('region', '=', 'Australia')->get()->pluck('name')->sort() as $timezone)
                    <option value="{{ $timezone }}"
                        {{ old('timezone') == $timezone
                            ? 'selected="selected"'
                            : (!empty($user) && $user->detail->timezone === $timezone
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
    <div class="col-md-6 col-12">
        <x-forms.input name="phone" input-class="" label-class="" placeholder="Select Country first"
            inputmode="numeric" type="text" {{--  maxlength="10"  pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')" --}} type="text"
            value="{{ old('phone') ?? ($user->detail->phone ?? '') }}"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="address" input-class="autocomplete-address" label-class="" placeholder="Address"
            type="text" value="{{ old('address') ?? ($user->detail->address ?? '') }}"></x-forms.input>
    </div>
    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="language">Select Language</label>
            <select data-placeholder="Select your Language..." class="select2 form-select"
                data-class=" @error('language') is-invalid @enderror" id="language" name='language'>
                <option value="en"
                    {{ old('language') == 'en' ? 'selected="selected"' : (!empty($user) && $user->detail->language === 'en' ? 'selected="selected"' : '') }}>
                    English
                </option>
            </select>
            @error('language')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="password" input-class="" label-class="required" placeholder="Password"
            type="password"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="password_confirmation" input-class="" label-class="required"
            placeholder="Retype Same Password" type="password"></x-forms.input>
    </div>
    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="role">Assign Role</label>
            {{--            {{ dd($allowedRoles) }} --}}
            <select data-placeholder="Assign a Role..." class="select2 form-select"
                data-class="@error('role') is-invalid @enderror" id="role" name='role'>
                <option></option>
                @foreach ($allowedRoles as $role)
                    <option value="{{ $role->name }}"
                        {{ old('role') == $role->name
                            ? 'selected="selected"'
                            : (!empty($user) && $user->role()->name === $role->name
                                ? 'selected="selected"'
                                : '') }}>
                        {{ ucwords($role->name) }}</option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class='col-12 mt-2'>
        <button type="submit"
            class="btn btn-primary me-1 waves-effect waves-float waves-light">{{ $action['name'] ?? 'Submit' }}</button>
        <button type="reset" class="btn btn-outline-secondary waves-effect" id='cancel'
            data-bs-dismiss="modal">Cancel
        </button>
    </div>
</div>
