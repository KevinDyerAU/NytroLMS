@extends('layouts/fullLayoutMaster')

@section('title', 'Login Page')

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-auth.css')) }}">
@endsection

@section('content')
    <div class="auth-wrapper auth-v1 px-2">
        <div class="auth-inner py-2">
            <!-- Login v1 -->
            <div class="card mb-0">
                <div class="card-body">
                    <a href="#" class="brand-logo">
                        <img src="{{ config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png') }}"
                            alt="{{ config('settings.site.institute_name', 'Key Institute') }}" style="height: 72px;" />
                    </a>
                    @if ($message = Session::get('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-body d-flex align-items-center">{{ $message }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if ($message = Session::get('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="alert-body d-flex align-items-center">{{ $message }}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (isset($timeoutMessage) && $timeoutMessage)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <div class="alert-body d-flex align-items-center">
                                <i class="feather icon-clock me-2"></i>
                                {{ $timeoutMessage }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <p class="card-text mb-2">Please sign in</p>

                    <form class="auth-login-form mt-2" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-1">
                            <div class="d-flex justify-content-between">
                                <label for="login-username" class="form-label">Username / Email</label>
                                @if (Route::has('username.request'))
                                    <a href="{{ route('username.request') }}">
                                        <small>Forgot Username?</small>
                                    </a>
                                @endif
                            </div>
                            <input type="text"
                                class="form-control {{ $errors->has('username') || $errors->has('email') ? ' is-invalid' : '' }}"
                                id="login-username" name="login" placeholder="Enter Username or Email"
                                aria-describedby="login-username" tabindex="1" autofocus
                                value="{{ old('username') ?: old('email') }}" />
                            @if ($errors->has('username') || $errors->has('email'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('username') ?: $errors->first('email') }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="mb-1">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="login-password">Password</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">
                                        <small>Forgot Password?</small>
                                    </a>
                                @endif
                            </div>
                            <div class="input-group input-group-merge form-password-toggle">
                                <input type="password" class="form-control form-control-merge" id="login-password"
                                    name="password" tabindex="2"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="login-password" />
                                <span class="input-group-text cursor-pointer"><i data-lucide="eye"></i></span>
                            </div>
                        </div>
                        {{--          <div class="mb-1"> --}}
                        {{--            <div class="form-check"> --}}
                        {{--              <input class="form-check-input" type="checkbox" id="remember" name="remember" tabindex="3" {{ old('remember') ? 'checked' : '' }} /> --}}
                        {{--              <label class="form-check-label" for="remember"> Remember Me </label> --}}
                        {{--            </div> --}}
                        {{--          </div> --}}
                        <button type="submit" class="btn btn-primary w-100" tabindex="4">Sign in</button>
                    </form>
                </div>
            </div>
            <!-- /Login v1 -->
        </div>
    </div>
@endsection
