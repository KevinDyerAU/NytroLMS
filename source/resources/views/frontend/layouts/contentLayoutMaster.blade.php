@isset($pageConfigs)
    {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset

<!DOCTYPE html>
@php
    $configData = Helper::applClasses();
@endphp

<html class="loading {{ $configData['theme'] === 'light' ? '' : $configData['layoutTheme'] }}"
    lang="@if (session()->has('locale')) {{ session()->get('locale') }}@else{{ $configData['defaultLanguage'] }} @endif"
    data-textdirection="{{ env('MIX_CONTENT_DIRECTION') === 'rtl' ? 'rtl' : 'ltr' }}"
    @if ($configData['theme'] === 'dark') data-layout="dark-layout" @endif>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="_timezone" content="{{ date_default_timezone_get() }}">
    <meta name="description"
        content="{{ config('settings.site.institute_name', 'Key Institute') }} is a Registered Training Organisation that first began educating in 1999. We believe in challenging the status quo of the education sector, of doing things differently.">
    <meta name="keywords"
        content="{{ config('settings.site.institute_name', 'Key Institute') }}, Registered Training Organisation, Australia Online Learning, Multi Lingual Learning">
    <meta name="author" content="Mohsin @InceptionSol">
    <title>@yield('title') | {{ config('settings.site.institute_name', 'Key Institute') }}</title>
    <link rel="apple-touch-icon" href="{{ asset('images/ico/apple-icon-120.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo/favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
        rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_ID') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', '{{ env('GOOGLE_ANALYTICS_ID') }}');
    </script>

    {{-- Include core + vendor Styles --}}
    @include('frontend/panels/styles')
    <script>
        const $studentID = "{{ auth()?->user()?->id }}";
        @if (!empty($post))
            const $quizID = "{{ $post?->id }}";
        @endif
    </script>
</head>
<!-- END: Head-->

<!-- BEGIN: Body-->
@isset($configData['mainLayoutType'])
    @extends($configData['mainLayoutType'] === 'horizontal' ? 'frontend.layouts.horizontalLayoutMaster' : 'frontend.layouts.verticalLayoutMaster')
@endisset
