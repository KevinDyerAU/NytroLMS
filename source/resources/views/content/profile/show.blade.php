@extends('layouts/contentLayoutMaster')

@section('title',$title)
@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/pages/page-profile.css')) }}">
@endsection

@section('content')
    <div id="user-profile" class="clearfix mt-5">
        <!-- profile header -->
        <div class="row">
            <div class="col-12">
                <div class="card profile-header mb-2">
                    <div class="position-relative">
                        <!-- profile picture -->
                        <div class="profile-img-container d-flex align-items-center">
                            <div class="profile-img" data-bs-toggle="tooltip"
                                 data-popup="tooltip-custom" data-bs-placement="top" title=""
                                 data-bs-original-title="Edit Profile Picture">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#avatarUpdateForm">
                                    @if(!empty($user->avatar()))
                                        <img class="rounded img-fluid" src="{{ $user->avatar() }}"
                                             alt="{{ $user->name }}"/>
                                    @else
                                        <h3 class="text-muted text-center text-white pt-1" style="width:100%;height: 100%;">
                                            <span class="">No Image</span>
                                        </h3>
                                    @endif
                                </a>
                            </div>
                            <!-- profile title -->
                            <div class="profile-title ms-2">
                                <h2 class="text-black">{{ $user->name }}</h2>
                                <p class="text-gray-500">{{ $user->roles->first()->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ profile header -->
        <div class="divider divider-primary divider-center ">
            <span class="divider-text"> Basic Information</span>
        </div>
        <!-- profile info section -->
        <section id="profile-info" class="clearfix mt-2">
            <div class="row row-cols-1 row-cols-md-3 match-height mb-2">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-75">Timezone:</h5>
                            <p class="card-text">{{ $user->detail->timezone }}</p>
                            <div class="mt-2">
                                <h5 class="mb-75">Joined:</h5>
                                <p class="card-text">{{ $user->created_at }}</p>
                            </div>
                            <div class="mt-2">
                                <h5 class="mb-75">Last Active On:</h5>
                                <p class="card-text">{{ $user->detail->last_logged_in }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-75">Email</h5>
                            <p class="card-text">{{ $user->email }}</p>
                            <div class="mt-2">
                                <h5 class="mb-75">Address:</h5>
                                <p class="card-text">{{ $user->detail->address }}</p>
                            </div>
                            <div class="mt-2">
                                <h5 class="mb-50">Contact No.:</h5>
                                @if($user->detail->phone)
                                    <p class="card-text mb-0">{{ $user->detail->phone }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-75">Country:</h5>
                            <p class="card-text">
                                <i class="flag-icon flag-icon-{{ strtolower($user->detail->country?->code) }}"></i>
                                {{ $user->detail->country?->name }}
                            </p>
                            <div class="mt-2">
                                <h5 class="mb-50">Preferred Language:</h5>
                                <p class="card-text mb-0">{{ $user->detail->language }}</p>
                            </div>
                            @if(!empty($user->company))
                                <div class="mt-2">
                                    <h5 class="mb-50">Related Company:</h5>
                                    <p class="card-text mb-0">{{ $user->company }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('content.components.modal.avatar')
@endsection

@section('page-script')
    {{-- Page js files --}}
    <script src="{{ asset(mix('js/scripts/pages/page-profile.js')) }}"></script>
@endsection
