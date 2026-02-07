@extends('layouts/contentLayoutMaster')

@section('title','Trainer')

@section('content')
    @if( auth()->user()->can('delete trainers'))
        @if($trainer->isActive())
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Trainer: {{ $trainer->name }} is set Active.&nbsp;
                    <a href="{{ route('account_manager.trainers.deactivate', $trainer) }}" class="text-danger"> Click
                        here to Deactivate.</a></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @else
            <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Trainer: {{ $trainer->name }} is set Inactive.&nbsp;
                    <a href="{{ route('account_manager.trainers.activate', $trainer) }}" class="text-success"> Click
                        here to Activate.</a></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-header'>
                    <h2 class='fw-bolder text-primary mx-auto'>{{ \Str::title($trainer->name) }}</h2>
                </div>
                <div class='card-body'>
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark"> Trainer</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Username:</span>
                        <span class='col-sm-6'>{{ $trainer->username }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                        <span class='col-sm-6'>{{ $trainer->email }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->phone }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->address }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Language:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->language }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Country:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->country->name }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Timezone:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->timezone }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Last Sign In:</span>
                        <span class='col-sm-6'>{{ $trainer->detail->last_logged_in }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Create At</span>
                        <span class='col-sm-6'>{{ $trainer->created_at }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col col-sm-4 text-end'>Status:</span>
                        <span
                            class='col col-sm-6'>{!! '<span class="text-' . ($trainer->is_active? "success":"danger") . '">' . ($trainer->is_active?"Active":"In Active") . '</span>' !!}</span>
                    </div>
                    @if( intval($trainer->is_active) === 0)
                        <div class='row mb-2'>
                            <span class='fw-bolder me-25 col col-sm-4 text-end'>Deactivated By</span>
                            <span class='col col-sm-6'>{{ $activity['by']??'' }}</span>
                        </div>
                        <div class='row mb-2'>
                            <span class='fw-bolder me-25 col col-sm-4 text-end'>Deactivated On</span>
                            <span class='col col-sm-6'>{{ $activity['on']??'' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
