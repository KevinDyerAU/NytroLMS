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
                    <a href="javascript:void(0);" class="brand-logo">
                        <img src="{{ config('settings.site.site_logo', 'https://v2.keyinstitute.com.au/storage/photos/1/Site/62f83337d1769.png') }}"
                            alt="{{ config('settings.site.institute_name', 'Key Institute') }}" style="height: 72px;" />
                    </a>

                    <h4 class="card-title mb-1">Verify Your Email Address! </h4>
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            <div class="alert-body">
                                {{ __('A fresh verification link has been sent to your email address.') }}</div>
                        </div>
                    @endif
                    <p class="card-text mb-2">
                        {{ __('Before proceeding, please check your email for a verification link.') }}</p>
                    <p class="card-text">{{ __('If you did not receive the email') }},

                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit"
                            class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                    </form>
                    </p>
                    <div class="dropdown-divider"></div>
                    <p class="flex flex-column"><span>or</span>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <i class="me-50" data-lucide="power"></i> {{ __('Logout') }}
                        </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
