@extends('layouts.contentLayoutMaster')

@section('title', $title)
@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-pills mb-2">
                <li class="nav-item">
                    <a class="nav-link active" href="javascript:void(0);">
                        <i data-lucide="lock" class="font-medium-3 me-50"></i>
                        <span class="fw-bold">Security</span>
                    </a>
                </li>
            </ul>
            <div class="card">
                <div class="card-header border-bottom">
                    <h4 class="card-title">Change Password</h4>
                </div>
                <div class="card-body pt-1">
                    <!-- form -->
                    <form class="validate-form" method="POST"
                        action="{{ route('profile.password.reset', auth()->user()) }}">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-sm-6 mb-1">
                                <label class="form-label" for="account-old-password">Old Password</label>
                                <div class="input-group form-password-toggle input-group-merge">
                                    <input type="password" id="account-old-password" name="old_password"
                                        class="form-control" placeholder="Enter old password" />
                                    <div class="input-group-text cursor-pointer">
                                        <i data-lucide="eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6 mb-1">
                                <label class="form-label" for="account-new-password">New Password</label>
                                <div class="input-group form-password-toggle input-group-merge">
                                    <input type="password" id="account-new-password" name="password" class="form-control"
                                        placeholder="Enter new password" />
                                    <div class="input-group-text cursor-pointer">
                                        <i data-lucide="eye"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 mb-1">
                                <label class="form-label" for="account-retype-new-password">Retype New Password</label>
                                <div class="input-group form-password-toggle input-group-merge">
                                    <input type="password" class="form-control" id="account-retype-new-password"
                                        name="password_confirmation" placeholder="Confirm your new password" />
                                    <div class="input-group-text cursor-pointer"><i data-lucide="eye"></i></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-1 mt-1">Save changes</button>
                                <button type="reset" class="btn btn-outline-secondary mt-1">Discard</button>
                            </div>
                        </div>
                    </form>
                    <!--/ form -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
@endsection
