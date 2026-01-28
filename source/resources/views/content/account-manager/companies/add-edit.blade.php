@extends('layouts/contentLayoutMaster')

@section('title',$action['name'].' Company')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection


@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
{{--                    @if(strtolower($action['name']) === 'edit' && $company->trashed())--}}
{{--                        Kindly restore the company first to edit it.--}}
{{--                    @else--}}
                        <form method='POST' action='{{ $action['url'] }}'
                              class="form form-vertical">
                            @if(strtolower($action['name']) === 'edit')
                                @method('PUT')
                                <input type='hidden' value='{{ md5($company->id) }}' name='v'>
                            @endif

                            @csrf
                            @include('content.account-manager.companies.modal-body', ['action'=>$action, 'company'=>$company ?? []])
                        </form>
{{--                    @endif--}}
                </div>
{{--                @if(strtolower($action['name']) === 'edit' && $company->trashed())--}}
{{--                    <div class='card-footer bg-light-danger'>--}}
{{--                        <a href='{{ route('account_manager.companies.activate',$company) }}'--}}
{{--                           class="btn btn-success me-1 waves-effect waves-float waves-light">Activate Company</a>--}}
{{--                    </div>--}}
{{--                @endif--}}
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/cleave.min.js'))}}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/addons/cleave-phone.us.js'))}}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script>
        $(function() {
            const phoneMask = $('.phone-number-mask');

            // if (phoneMask.length) {
            //     new Cleave(phoneMask, {
            //         blocks: [3, 3, 3, 4, 5],
            //         uppercase: true
            //     });
            // }

            let select = $('.select2');
            select.each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                $this.select2({
                    // the following code is used to disable x-scrollbar when click in select input and
                    // take 100% width in responsive also
                    minimumResultsForSearch: 10,
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                });
            });

            $("#poc_user_id").wrap('<div class="position-relative form-select-control' +  $("#poc_user_id").data('class') + '"></div>');

            $("#poc_user_id").select2({
                ajax:{
                    url: '/api/v1/select2/users',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        let query = {
                            search: params.term,
                            page: params.page || 1,
                            source: 'users',
                            params: params
                        }

                        // Query parameters will be ?search=[term]&type=users
                        return query;
                    },
                    processResults: function (resp, params) {
                        params.page = params.page || 1;
                        // Transforms the top-level key of the response object from 'items' to 'results'
                        // console.log(resp, params,!!resp.next_page_url,  (resp.current_page > 0 && params.page <= resp.last_page));
                        let hasMore = !!resp.next_page_url;
                        if(resp.next_page_url === null){
                            hasMore = false;
                        }else{
                            hasMore = (resp.current_page > 0 && params.page <= resp.last_page);
                        }

                        return {
                            results: resp.data,
                            pagination: {
                                more: hasMore
                            }
                        };
                    }
                },
                placeholder: 'Search for a User',
                minimumInputLength: 2,
                cache: true
            });

            $('#cancel').on('click', function() {
                window.location = '{{ (strtolower($action['name']) === 'edit')?route('account_manager.companies.show',$company):route('account_manager.companies.index') }}';
            });
        });
    </script>
@endsection
