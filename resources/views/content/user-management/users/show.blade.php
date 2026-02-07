@extends('layouts/contentLayoutMaster')

@section('title','Users')

@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-header'>
                    <h2 class='fw-bolder text-primary mx-auto'>{{ \Str::title($user->name) }}</h2>
                </div>
                <div class='card-body'>
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark"> Details:</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Username:</span>
                        <span class='col-sm-6'>{{ $user->username }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                        <span class='col-sm-6'>{{ $user->email }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                        <span class='col-sm-6'>{{ $user->detail?->phone ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                        <span class='col-sm-6'>{{ $user->detail?->address ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Language:</span>
                        <span class='col-sm-6'>{{ $user->detail?->language ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Country:</span>
                        <span class='col-sm-6'>{{ $user->detail?->country?->name ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Timezone:</span>
                        <span class='col-sm-6'>{{ $user->detail?->timezone ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Last Sign In:</span>
                        <span class='col-sm-6'>{{ $user->detail?->last_logged_in ?? "" }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Create At</span>
                        <span class='col-sm-6'>{{ $user->created_at }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
