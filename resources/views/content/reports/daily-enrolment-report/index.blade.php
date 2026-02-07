@extends('layouts/contentLayoutMaster')

@section('title', $reportTitle)

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')
    <section id="daily-enrolment-report-datatable">
        <div class="row mb-2">
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="form-label" for="registration-date-filter">Registration Date:</label>
                <input
                       type="date"
                       class="form-control"
                       id="registration-date-filter"
                       name="registration_date"
                       value="{{ $registrationDate }}"
                       onchange="filterByDate()"
                >
            </div>
        </div>
        {{ $dataTable->table() }}
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
        function filterByDate() {
            const date = document.getElementById('registration-date-filter').value;
            if (date) {
                const url = new URL(window.location.href);
                url.searchParams.set('registration_date', date);
                window.location.href = url.toString();
            }
        }

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
            @endcan
            );

            myDataTable.initDefaults(defaults);
        });
    </script>
    {{ $dataTable->scripts() }}
@endsection
