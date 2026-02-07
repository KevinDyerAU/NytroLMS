@extends('layouts/contentLayoutMaster')

@section('title', 'Leader')

@section('content')
    @if (auth()->user()->can('delete leaders'))
        @if ($leader->isActive())
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Leader: {{ $leader->name }} is set Active.&nbsp;
                    <a href="{{ route('account_manager.leaders.deactivate', $leader) }}" class="text-danger"> Click here to
                        Deactivate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @else
            <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">Leader: {{ $leader->name }} is set Inactive.&nbsp;
                    <a href="{{ route('account_manager.leaders.activate', $leader) }}" class="text-success"> Click here to
                        Activate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-header'>
                    <h2 class='fw-bolder text-primary mx-auto'>{{ \Str::title($leader->name) }}</h2>
                </div>
                <div class='card-body'>
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark"> Leader</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Associate with
                            {{ \Str::of('Company')->plural(count($leader->companies)) }}:</span>
                        <span class='col-sm-6'>
                            <ul class="list-style-square">
                                @foreach ($leader->companies as $company)
                                    <li>
                                        <a
                                            href='@php echo route("account_manager.companies.show", $company->id) @endphp'>{{ $company->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Username:</span>
                        <span class='col-sm-6'>{{ $leader->username }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                        <span class='col-sm-6'>{{ $leader->email }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                        <span class='col-sm-6'>{{ $leader->detail->phone }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                        <span class='col-sm-6'>{{ $leader->detail->address }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Language:</span>
                        <span class='col-sm-6'>{{ $leader->detail->language }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Country:</span>
                        <span class='col-sm-6'>{{ $leader->detail->country?->name }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Timezone:</span>
                        <span class='col-sm-6'>{{ $leader->detail->timezone }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Last Sign In:</span>
                        <span class='col-sm-6'>{{ $leader->detail->last_logged_in }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Created At:</span>
                        <span class='col-sm-6'>{{ $leader->created_at }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>Position:</span>
                        <span class='col-sm-6'>{{ $leader->detail->position }}</span>
                    </div>
                    @if ($leader->detail->role)
                        <div class='row mb-2'>
                            <span class='fw-bolder me-25 col-sm-4 text-end'>Role:</span>
                            <span class='col-sm-6'>{{ \Str::title($leader->detail->role) }}</span>
                        </div>
                    @endif
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col col-sm-4 text-end'>Status:</span>
                        <span class='col col-sm-6'>{!! '<span class="text-' .
                            ($leader->is_active ? 'success' : 'danger') .
                            '">' .
                            ($leader->is_active ? 'Active' : 'In Active') .
                            '</span>' !!}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>First Login:</span>
                        <span class='col-sm-6'>{{ $leader->detail->first_login }}</span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col-sm-4 text-end'>First Enrolment:</span>
                        <span class='col-sm-6'>{{ $leader->detail->first_enrollment }}</span>
                    </div>
                    @if (intval($leader->is_active) === 0)
                        <div class='row mb-2'>
                            <span class='fw-bolder me-25 col col-sm-4 text-end'>Deactivated By:</span>
                            <span class='col col-sm-6'>{{ $activity['by'] ?? '' }}</span>
                        </div>
                        <div class='row mb-2'>
                            <span class='fw-bolder me-25 col col-sm-4 text-end'>Deactivated On:</span>
                            <span class='col col-sm-6'>{{ $activity['on'] ?? '' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
