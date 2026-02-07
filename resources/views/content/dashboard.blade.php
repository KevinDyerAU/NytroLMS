@extends('layouts/contentLayoutMaster')

@section('title', $title?? config('settings.site.institute_name', 'Key Institute'))
@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/charts/apexcharts.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection
@section('page-style')
    <!-- Page css files -->
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/charts/chart-apex.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection
@section('content')
    <script src="{{ asset(mix('vendors/js/jquery/jquery.min.js')) }}"></script>
    @if( auth()->user()->hasRole(['Leader']))
        <div class="row">
            <div class="col-12 d-flex align-items-center justify-content-center gap-3">
                @if(!empty($settings['featured_images']['leader']['image']))
                    <a href="{{ $settings['featured_images']['leader']['link'] ?? '#' }}"
                       title="{{ $settings['featured_images']['leader']['$title'] ?? config('settings.site.institute_name', 'Key Institute') }} - Study Club"
                       target="_blank">
                        <img src="{{ asset($settings['featured_images']['leader']['image']) }}"
                             class="d-flex align-content-center align-items-center height-300 pb-2"
                             alt="{{ $settings['featured_images']['leader']['title'] ?? '' }}"/>
                    </a>
                @endif
                @php
                    $email = strtolower(auth()->user()->email ?? '');
                @endphp
                @if(str_ends_with($email, '@apm.com.au') || str_ends_with($email, '@apm.net.au'))
                    <img src="{{ asset('images/badges/apm_badge.png') }}"
                         alt="APM Badge"
                         class="img-fluid"
                         style="width: 10%;" />
                @endif
            </div>
        </div>
    @endif
    <section id="dashboard-analytics">
        <div class="row match-height d-flex flex-row">
            @can('create students')
                @widget('StaticWidget',['content' => 'add_student'])
            @endcan

            @can( 'widget active_students')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('ActiveStudents')
                </div>
            @endcan

            @can( 'widget engaged_students')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('EngagedStudents')
                </div>
            @endcan

            @can( 'widget disengaged_students')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('DisengagedStudents')
                </div>
            @endcan

            @can('widget non_commenced students')
                <div class="col-lg-4 col-sm-6 col-12 non_commenced">
                    @widget('NonCommenced')
                </div>
            @endcan

            @can('widget leader companies')
                @widget('LeaderCompanies')
            @endcan

            @can( 'widget inactive_students')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('InactiveStudents')
                </div>
            @endcan
            @can( 'widget daily_enrolments')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('DailyEnrolments')
                </div>
            @endcan
            @can( 'widget registered_students')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('RegStudents')
                </div>
            @endcan
            @can( 'widget total_assessments')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('TotalAssessments')
                </div>
            @endcan
            @can( 'widget pending_assessments')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('PendingAssessments')
                </div>
            @endcan
            @can('widget competency')
                <div class="col-lg-4 col-sm-6 col-12 competency">
                    @widget('Competency')
                </div>
            @endcan
            @can('widget course flyer')
                <div class="col-lg-4 col-sm-6 col-12">
                    @widget('StaticWidget',['content' => 'course_flyer'])
                </div>
            @endcan
        </div>
    </section>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/charts/apexcharts.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    {{--        <script src="{{ asset(mix('js/scripts/pages/dashboard-analytics.js')) }}"></script>--}}
@endsection
