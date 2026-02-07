<div class='row'>
    <div class='col-12'>
        <x-forms.input name="company_name" input-class="" label-class="required" placeholder="Company name" type="text"
            value="{{ old('company_name') ? urldecode(old('company_name')) : $company->name ?? '' }}"
            autofocus></x-forms.input>
    </div>
    <div class='col-12'>
        <x-forms.input name="company_email" input-class="" label-class="required" placeholder="Company email address"
            type="email" value="{{ old('company_email') ?? ($company->email ?? '') }}"></x-forms.input>
    </div>
    <div class='col-12'>
        <x-forms.input name="address" input-class="autocomplete-address" label-class="" placeholder="Company address"
            type="text" value="{{ old('address') ?? ($company->address ?? '') }}"></x-forms.input>
    </div>
    <div class='col-12'>
        <x-forms.input name="company_number" input-class="" label-class="required" placeholder="Company phone number"
            type="text" value="{{ old('company_number') ?? ($company->number ?? '') }}"></x-forms.input>
    </div>

    @can('manage company poc')
        <div class='col-12'>
            <div class="mb-1">
                <label class="form-label" for="poc_user_id">Assign Point of Contact Business Relationship Manager</label>
                <select data-placeholder="POC BRM" class="select2 form-select"
                    data-class=" @error('poc_user_id') is-invalid @enderror" id="poc_user_id" name='poc_user_id'>
                    @if (!empty($company->pocUser))
                        <option value="{{ $company->pocUser->id }}" selected="selected">
                            {{ $company->pocUser->name . ' <' . $company->pocUser->email . '>' }}</option>
                    @endif
                    {{-- <option></option>
                    @if (!empty($users))
                        @foreach ($users as $user)
                            <option></option>
                            <option
                                value="{{ $user->id }}"
                                {{
                                    (( !empty(old('poc_user_id')) && $user->id == old('poc_user_id')) ? 'selected=selected' :
                                    ((!empty($poc_user_id) && $user->id === $poc_user_id) ? 'selected=selected' : ''))
                                }}>
                                {{ \Str::title($user->name)." <{$user->email}>" }}
                            </option>
                        @endforeach
                    @endif --}}
                </select>
                @error('poc_user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endcan
    @if (strtolower($action['name']) === 'edit')
        @can('manage company bm')
            <div class='col-12'>
                <div class="mb-1">
                    <label class="form-label" for="bm_user_id">Assign BM</label>
                    <select data-placeholder="Select a Leader..." class="select2 form-select"
                        data-class=" @error('bm_user_id') is-invalid @enderror" id="bm_user_id" name='bm_user_id'>
                        @if (!empty($company->leaders))
                            <option></option>
                            @foreach ($company->leaders as $user)
                                <option value="{{ $user->id }}"
                                    {{ !empty(old('bm_user_id')) && $user->id == old('bm_user_id')
                                        ? 'selected=selected'
                                        : (!empty($company->bm_user_id) && $user->id === $company->bm_user_id
                                            ? 'selected=selected'
                                            : '') }}>
                                    {{ \Str::title($user->name) . " <{$user->email}>" }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('bm_user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endcan
    @endif
    <div class='col-12'>
        <button type="submit"
            class="btn btn-primary me-1 waves-effect waves-float waves-light">{{ $action['name'] ?? 'Submit' }}</button>
        <button type="reset" class="btn btn-outline-secondary waves-effect" id='cancel'
            data-bs-dismiss="modal">Cancel
        </button>
    </div>
</div>
