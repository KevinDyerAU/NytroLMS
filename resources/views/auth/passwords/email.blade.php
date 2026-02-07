@extends('layouts/fullLayoutMaster')

@section('title', 'Forgot Password')

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-auth.css')) }}">
@endsection

@section('content')
    <div class="auth-wrapper auth-v1 px-2">
        <div class="auth-inner py-2">
            <!-- Forgot Password v1 -->
            <div class="card mb-0">
                <div class="card-body">
                    <a href="javascript:void(0);" class="brand-logo">
                        <img src="{{ config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png') }}"
                            alt="{{ config('settings.site.institute_name', 'Key Institute') }}" style="height: 72px;" />
                    </a>

                    <h4 class="card-title mb-1">Forgot Password? 🔒</h4>
                    <p class="card-text mb-2">Enter your email and we'll send you instructions to reset your password</p>

                    <form class="auth-forgot-password-form mt-2" method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="mb-1">
                            <label for="forgot-password-email" class="form-label">Email</label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror"
                                id="forgot-password-email" name="email" value="{{ old('email') }}"
                                placeholder="john@example.com" aria-describedby="forgot-password-email" tabindex="1"
                                autofocus />
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            @if (session('status'))
                                <p class="text-success font-small-3" role="alert">{{ session('status') }}</p>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary w-100" tabindex="2">Send reset link</button>
                    </form>

                    <p class="text-center mt-2">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}"> <i data-lucide="chevron-left"></i> Back to login </a>
                        @endif
                    </p>
                </div>
            </div>
            <!-- /Forgot Password v1 -->
        </div>
    </div>
@endsection
