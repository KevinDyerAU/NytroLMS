<div class='row'>
    <div class="col-md-6 col-12">
        <x-forms.input name="first_name" input-class="" label-class="required" placeholder="First name" type="text"
            value="{{ old('first_name') ?? ($trainer->first_name ?? '') }}" autofocus></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="last_name" input-class="" label-class="required" type="text" placeholder="Last name"
            value="{{ old('last_name') ?? ($trainer->last_name ?? '') }}"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="email" input-class="" label-class="required" type="email" placeholder="Email"
            value="{{ old('email') ?? ($trainer->email ?? '') }}"></x-forms.input>
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
                            : (!empty($trainer) && $trainer->detail->country_id === $country->id
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
                            : (!empty($trainer) && $trainer->detail->timezone === $timezone
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
        <x-forms.input name="phone" input-class="" label-class="" placeholder="Select Country first" type="text"
            value="{{ old('phone') ?? ($trainer->detail->phone ?? '') }}"></x-forms.input>
    </div>
    <div class="col-md-6 col-12">
        <x-forms.input name="address" input-class="autocomplete-address" label-class="" placeholder="Address"
            type="text" value="{{ old('address') ?? ($trainer->detail->address ?? '') }}"></x-forms.input>
    </div>
    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="language">Select Language</label>
            <select data-placeholder="Select your Language..." class="select2 form-select"
                data-class=" @error('language') is-invalid @enderror" id="language" name='language'>
                <option value="en"
                    {{ old('language') == 'en' ? 'selected="selected"' : (!empty($trainer) && $trainer->detail->language === 'en' ? 'selected="selected"' : '') }}>
                    English
                </option>
            </select>
            @error('language')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label @if (strtolower($action['name']) === 'create') required @endif" for="password">Password</label>
            <input type="password" placeholder="Password" class="form-control  @error('password') is-invalid @enderror"
                id="password" name="password" aria-label="Password" autocomplete="off" />
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="mb-1">
            <label class="form-label @if (strtolower($action['name']) === 'create') required @endif"
                for="password_confirmation">Password Confirmation</label>
            <input type="password" placeholder="Retype Same Password"
                class="form-control  @error('password_confirmation') is-invalid @enderror" id="password_confirmation"
                name="password_confirmation" aria-label="Password Confirmation" autocomplete="off" />
            @error('password_confirmation')
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
