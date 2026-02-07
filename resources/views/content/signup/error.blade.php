@extends('layouts/contentLayoutMaster')

@section('title', '403 - Forbidden')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">You are already registered</h4>
        </div>
        <div class="card-body">
            <div class="card-text">
                @guest
                    <p>Click here to register: <a href="{{ route('signup-link', $data) }}" title="Signup">{{ route('signup-link', $data) }}</a></p>
                @else
                    <p>Click here to
                    <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form></p>
                @endguest
            </div>
        </div>
    </div>
@endsection
