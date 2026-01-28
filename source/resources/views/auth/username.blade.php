@extends('layouts/fullLayoutMaster')

@section('title', 'Username Request')

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
                    <p class="card-text mb-2">Please enter your valid email</p>

                    <form class="auth-login-form mt-2" method="POST" action="{{ route('username.request') }}">
                        @csrf
                        <div class="mb-1">
                            <div class="d-flex justify-content-between">
                                <label for="email" class="form-label">Email</label>
                            </div>
                            <input type="text" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" placeholder="email" aria-describedby="email" tabindex="1" autofocus
                                value="{{ old('email') }}" />
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100" tabindex="4">Send</button>
                        <div class="mt-2">
                            <a class='text-dark fw-normal' href="{{ route('login') }}"
                                title='{{ config('settings.site.institute_name', 'Key Institute') }}'><i
                                    data-lucide="arrow-left"></i> <small>Back to
                                    {{ config('settings.site.institute_name', 'Key Institute') }} Login</small></a>
                        </div>
                    </form>
                </div>
            </div>
            <!-- /Login v1 -->
        </div>
    </div>
@endsection
