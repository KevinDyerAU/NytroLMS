@php
    $settings_key = strtolower(env('SETTINGS_KEY','KeyInstitute'));
@endphp
<script>
    const userId = '{{ (auth()->check() ?auth()->user()->id : null) }}';
    const $studentId = '{{ (request()->segment(3) ?? null) }}';
</script>
<!-- BEGIN: Vendor CSS-->
@if($configData['direction'] === 'rtl' && isset($configData['direction']))
<link rel="stylesheet" href="{{ asset(mix('vendors/css/vendors-rtl.min.css')) }}" />
@else
<link rel="stylesheet" href="{{ asset(mix('vendors/css/vendors.min.css')) }}" />
@endif

@yield('vendor-style')
<!-- END: Vendor CSS-->

<!-- BEGIN: Theme CSS-->
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/core.css")) }}" />

@php $configData = Helper::applClasses(); @endphp

<!-- BEGIN: Page CSS-->
@if($configData['mainLayoutType'] === 'horizontal')
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/base/core/menu/menu-types/horizontal-menu.css")) }}" />
@else
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/base/core/menu/menu-types/vertical-menu.css")) }}" />
@endif

{{-- Page Styles --}}
@yield('page-style')

<!-- laravel style -->
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/overrides.css")) }}" />

<!-- BEGIN: Custom CSS-->
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/components.css")) }}" />
@if($configData['direction'] === 'rtl' && isset($configData['direction']))
<link rel="stylesheet" href="{{ asset(mix("css-rtl/{$settings_key}/custom-rtl.css")) }}" />
<link rel="stylesheet" href="{{ asset(mix("css-rtl/{$settings_key}/style-rtl.css")) }}" />

@else
{{-- user custom styles --}}
<link rel="stylesheet" href="{{ asset(mix("css/{$settings_key}/style.css")) }}" />
@endif
<link rel="stylesheet" href="{{ asset(mix("css/common-style.css")) }}" />
