@extends('frontend.layouts.contentLayoutMaster')

@section('title', '403 - Forbidden')

@section('content')
    <div class="d-flex flex-column align-items-center align-content-center justify-content-center my-5">
        <p class="clearfix"> {{ $message }}</p>
        @isset( $link)
            <p class="clearfix">
                <a href="{{ $link['href'] }}">{{ $link['title'] }}</a>
            </p>
        @endisset
    </div>
@endsection
