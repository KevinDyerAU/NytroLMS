<div class='card'>
    <div class='card-body'>
        <form method='POST' action='{{ route('settings.update', [strtolower($type)]) }}' class="form ">
            @csrf
            <div class='row'>
                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <label class="form-label required" for="site_logo">Upload Logo</label>
                        <div class="input-group">
               <span class="input-group-btn">
                 <a id="lfm_logo" data-input="site_logo" data-preview="holder" class="btn btn-info" tabindex="1">
                   <i class="fa fa-picture-o"></i> Choose
                 </a>
               </span>
                            <input id="site_logo" class="form-control" type="text" name="site_logo"
                                   value="{{ old('site_logo')??$settings['site_logo']??'' }}" required/>
                        </div>
                        <div id="holder" style="margin-top:15px;max-height:100px;"></div>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <x-forms.input name="institute_rto_code" input-class="required" label-class="required"
                                       type="text"
                                       value="{{ old('institute_rto_code')??$settings['institute_rto_code']??'' }}"
                                       tabindex="2"></x-forms.input>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <x-forms.input name="institute_name" input-class="required" label-class="required" type="text"
                                       value="{{ old('institute_name')??$settings['institute_name']??'' }}"
                                       tabindex="2"></x-forms.input>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <x-forms.input name="institute_email" input-class="required" label-class="required" type="email"
                                       value="{{ old('institute_email')??$settings['institute_email']??'' }}"
                                       tabindex="3"></x-forms.input>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <x-forms.input name="institute_phone" input-class="required" label-class="required" type="text"
                                       value="{{ old('institute_phone')??$settings['institute_phone']??'' }}"
                                       tabindex="4"></x-forms.input>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        <x-forms.input name="copyright_text" input-class="required" label-class="required" type="text"
                                       value="{{ old('copyright_text')??$settings['copyright_text']??'' }}"
                                       tabindex="5"></x-forms.input>
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                        {{--            <x-forms.input name="privacy_policy_link" input-class="required" label-class="required" type="text"--}}
                        {{--                           value="{{ old('privacy_policy_link')??$settings->privacy_policy_link??'' }}" tabindex="6"></x-forms.input>--}}
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                    </div>
                </div>

                <div class="col-md-6 col-12">
                    <div class="mb-1">
                    </div>
                </div>
                <div class='col-12 mt-2'>
                    <button type="submit"
                            class="btn btn-primary me-1 waves-effect waves-float waves-light">Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
