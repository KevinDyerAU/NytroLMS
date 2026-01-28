@extends('layouts/contentLayoutMaster')

@section('title',$action['name'].' Role')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
                    <form method='POST' action='{{ $action['url'] }}'
                          class="form form-vertical">
                        @if(strtolower($action['name']) === 'edit')
                            @method('PUT')
                            <input type='hidden' value='{{ md5($role->id) }}' name='v'>
                        @endif

                        @csrf
                        @include('content.user-management.roles.modal-body', ['action'=>$action, 'role'=>$role ?? []])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('js/scripts/forms/form-select2.js')) }}"></script>
    <script>
        $(function() {
            $('#cancel').on('click', function() {
                window.location = '{{ (strtolower($action['name']) === 'edit')?route('user_management.roles.show',$role):route('user_management.roles.index') }}';
            });
        });
    </script>
@endsection
