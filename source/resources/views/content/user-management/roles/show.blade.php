@extends('layouts/contentLayoutMaster')

@section('title','Roles')

@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-header'>
                    <h2 class='fw-bolder text-primary mx-auto'>{{ strtoupper($role->name) }}</h2>
                </div>
                <div class='card-body'>
                    <div class="clearfix divider divider-secondary divider-start-center ">
                        <span class="divider-text text-dark"> Permissions:</span>
                    </div>
                    <ul class='mt-2 list-inline'>
                        @foreach($role->permissions as $permission)
                            <li class='list-inline-item badge badge-light-secondary mb-2'>{{ ucwords($permission->name) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
