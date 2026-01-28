@extends('layouts/contentLayoutMaster')

@section('title', $title?? config('settings.site.institute_name', 'Key Institute'))

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Welcome to {{ config('settings.site.institute_name', 'Key Institute') }}</h4>
        </div>
        <div class="card-body">
            <div class="card-text">
                @guest
                <p> To proceed <a href='{{ route('login') }}'>login here</a>.</p>
                @else
                    <p>You have successfully logged in</p>
                    <script>
                        window.location.href = '{{ route('dashboard') }}';
                    </script>
                @endguest
            </div>
        </div>
    </div>
@endsection
