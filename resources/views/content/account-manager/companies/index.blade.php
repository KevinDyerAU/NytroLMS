@extends('layouts/contentLayoutMaster')

@section('title','Companies')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')
    <section id="companies-datatable">
    {{$dataTable->table()}}
    <!-- Modal to add new record -->
        @if($hasModal?? false)
            <div class="modal modal-slide-in fade" id="modals-slide-in">
                <div class="modal-dialog sidebar-sm">
                    <form method='POST' action='{{ route('account_manager.companies.store') }}'
                          class="add-new-record modal-content pt-0">
                        @csrf
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">New Company</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            @include('content.account-manager.companies.modal-body')
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </section>
@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.bootstrap5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/dataTables.responsive.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/responsive.bootstrap4.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.checkboxes.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/datatables.buttons.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/jszip.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/pdfmake.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/vfs_fonts.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.html5.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/tables/datatable/buttons.print.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/datatables/buttons.server-side.js')) }}"></script>
@endsection
@section('page-script')
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script>
        $(function() {
            let defaults = myDataTable.setupDefaults();
            @can('create', App\Models\Company::class)
                defaults.buttons = myDataTable.setupAddRecordButton(defaults);
            @endcan
                defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {
                view: '{{ route('account_manager.companies.show',':id') }}',
                edit: '{{ route('account_manager.companies.edit',':id') }}',
                {{--delete: '{{ route('account_manager.companies.deactivate',':id') }}',--}}
                {{--restore: '{{ route('account_manager.companies.activate',':id') }}'--}}
            }, true);
            myDataTable.initDefaults(defaults);
        });
    </script>
    {{$dataTable->scripts()}}
@endsection
