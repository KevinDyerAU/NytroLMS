@extends('frontend.layouts.contentLayoutMaster')

@section('title', 'Privacy Policy')

@section('content')
    @if( env('SETTINGS_KEY') === 'KeyInstitute')
        @include('frontend.content.pages.key-privacy-policy')
    @else
        @include('frontend.content.pages.ks-privacy-policy')
    @endif
@endsection
