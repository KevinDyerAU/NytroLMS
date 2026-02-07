@extends('errors::minimal')

@section('title', __('Key Institute - Page Expired'))
@section('code', '419')
@section('message')
    <a href="{{ route('home') }}" class="btn btn-primary">
        Your session has expired - Click here to log back in to Key Institute
    </a>
@endsection
