@extends('layouts/contentLayoutMaster')

@section('title', 'Admin Tools')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection
@section('page-style')
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
          href="{{asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-sweet-alerts.css'))}}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Admin Tools</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-success">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        Sync Stats
                                    </h5>
                                    <p class="card-text">Sync student profiles by updating course progress, stats, expiry dates, and admin reports</p>
                                    <a href="{{ route('admin-tools.sync-stats') }}" class="btn btn-success">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        Open Tool
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-info">
                                        <i class="fas fa-balance-scale me-2"></i>
                                        Compare Stats
                                    </h5>
                                    <p class="card-text">Compare course progress calculations between LMS and Admin Report services to verify consistency</p>
                                    <a href="{{ route('admin-tools.compare-stats') }}" class="btn btn-info">
                                        <i class="fas fa-balance-scale me-2"></i>
                                        Open Tool
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
@endsection
@section('page-script')
    <script>
        $(function () {
            $(".select2").select2({
                placeholder: 'Select Option'
            });
        });
    </script>
@endsection
