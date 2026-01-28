@extends('layouts/fullLayoutMaster')

@section('title', 'Register Page')

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-auth.css')) }}">
@endsection

@section('content')
    <div class="auth-wrapper auth-v1 px-2">
        <div class="auth-inner py-2">
            <!-- Register v1 -->
            <div class="card mb-0">
                <div class="card-body">
                    <a href="#" class="brand-logo">
                        <img src="{{ config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png') }}"
                            alt="{{ config('settings.site.institute_name', 'Key Institute') }}" style="height: 72px;" />
                    </a>

                    <h4 class="card-title mb-1">Adventure starts here 🚀</h4>
                    <p class="card-text mb-2">Make your app management easy and fun!</p>

                    <form class="auth-register-form mt-2" method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="mb-1">
                            <label for="register-username" class="form-label">Username</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="register-username" name="name" placeholder="johndoe"
                                aria-describedby="register-username" tabindex="1" autofocus value="{{ old('name') }}" />
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-1">
                            <label for="register-email" class="form-label">Email</label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror"
                                id="register-email" name="email" placeholder="john@example.com"
                                aria-describedby="register-email" tabindex="2" value="{{ old('email') }}" />
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-1">
                            <label for="register-password" class="form-label">Password</label>

                            <div
                                class="input-group input-group-merge form-password-toggle @error('password') is-invalid @enderror">
                                <input type="password"
                                    class="form-control form-control-merge @error('password') is-invalid @enderror"
                                    id="register-password" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="register-password" tabindex="3" />
                                <span class="input-group-text cursor-pointer"><i data-lucide="eye"></i></span>
                            </div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-1">
                            <label for="register-password-confirm" class="form-label">Confirm Password</label>

                            <div class="input-group input-group-merge form-password-toggle">
                                <input type="password" class="form-control form-control-merge"
                                    id="register-password-confirm" name="password_confirmation"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="register-password" tabindex="3" />
                                <span class="input-group-text cursor-pointer"><i data-lucide="eye"></i></span>
                            </div>
                        </div>

                        <div class="mb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="register-privacy-policy"
                                    tabindex="4" />
                                <label class="form-check-label" for="register-privacy-policy">
                                    I agree to <a href="#">privacy policy & terms</a>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" tabindex="5">Sign up</button>
                    </form>

                    <p class="text-center mt-2">
                        <span>Already have an account?</span>
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}">
                                <span>Sign in instead</span>
                            </a>
                        @endif
                    </p>

                    <div class="divider my-2">
                        <div class="divider-text">or</div>
                    </div>

                    <div class="auth-footer-btn d-flex justify-content-center">
                        <a href="#" class="btn btn-facebook">
                            <i data-lucide="facebook"></i>
                        </a>
                        <a href="#" class="btn btn-twitter white">
                            <i data-lucide="twitter"></i>
                        </a>
                        <a href="#" class="btn btn-google">
                            <i data-lucide="mail"></i>
                        </a>
                        <a href="#" class="btn btn-github">
                            <i data-lucide="github"></i>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /Register v1 -->
        </div>
    </div>
@endsection
