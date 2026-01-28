@extends('layouts/contentLayoutMaster')

@section('title', 'Students')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')
    <section id="students-datatable">
        @if (auth()->user()->isLeader())
            <form class="dt_adv_search col-10 position-absolute" method="POST">
                <div class="row g-1 mb-1">
                    <div class="col-md-3 form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="leader_specific" id="leader_specific" />
                        <label class="form-check-label" for="leader_specific">Show all students</label>
                    </div>
                </div>
            </form>
        @endif
        {{ $dataTable->table() }}
        <!-- Modal to add new record -->
        @if ($hasModal ?? false)
            <div class="modal modal-slide-in fade" id="modals-slide-in">
                <div class="modal-dialog sidebar-sm">
                    <form method='POST' action='{{ route('account_manager.students.store') }}'
                        class="add-new-record modal-content pt-0">
                        @csrf
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                        <div class="modal-header mb-1">
                            <h5 class="modal-title" id="exampleModalLabel">New Student</h5>
                        </div>
                        <div class="modal-body flex-grow-1">
                            @include('content.account-manager.students.modal-body')
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
    <script src="{{ asset(mix('js/scripts/_my/student-form-validation.js')) }}"></script>
    <script>
        $(function () {
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {

                @can('view students')
                    view: '{{ route('account_manager.students.show', ':id') }}',
                @endcan

                @can('update students')
                    edit: '{{ route('account_manager.students.edit', ':id') }}',
                @endcan

                                            },
            @can('update students')
                true
            @endcan + "");

        myDataTable.initDefaults(defaults);


        const url = new URL(window.location.href);

        if (url.searchParams.get('show_all') == 1) {
            $("#leader_specific").prop('checked', true);
        }

        $("#leader_specific").on('click', function (e) {
            console.log('leader_specific toggled');
            {{-- const data = $(this).is(":checked") ? '{{ auth()->user()->id }}':''; --}}
            // console.log(data);
            const data = $(this).is(":checked") ? "1" : "0";
            url.searchParams.set('show_all', data);
            window.location.href = url.toString();
            // window.location.href = "?leader_specific=" + ($(this).is(":checked") ? 1: 0);
            // myDataTable.filterColumn("student-table",13, data);
        });

        // Persistent toastr notification that survives DataTable reload
        @if(Session::has('toastr_info'))
            toastr.info(
                {!! json_encode(Session::get('toastr_info')) !!},
                'Information',
                {
                    closeButton: true,
                    tapToDismiss: false,
                    timeOut: {{ Session::get('toastr_timeout', 5000) }},
                    extendedTimeOut: 2000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                }
            );
        @endif
    });
    </script>
    {{ $dataTable->scripts() }}
@endsection
