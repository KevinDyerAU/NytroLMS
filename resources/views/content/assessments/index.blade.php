@extends('layouts/contentLayoutMaster')

@section('title', 'Assessments')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
    @endsections

@section('content')
    <section id="assessments-datatable" style='position:relative;'>
        <form class="dt_adv_search w-100 position-relative" id="filterForm">
            <div class="row mb-1 flex-nowrap">
                @if (empty(request('status')) || \Str::lower(request('status')) !== 'pending')
                    <div class="col-auto">
                        <label class="form-label">Filter Assessments:</label>
                        @php
                            $statuses = array_keys(config('lms.status'));
                            sort($statuses);
                        @endphp
                        <select data-placeholder="Filter Status" class="select2 form-select" id="status" name='status'
                            data-column="9">
                            <option value="all">All</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}"
                                    {{ $status === request('status') ? 'selected="selected"' : '' }}>
                                    {{ \Str::title($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-auto">
                    <label class="form-label">Select Date:</label>
                    <div id="reportrange" class="form-control" data-column='10'>
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                        <input id="startDate" type="hidden" data-column='10' name="startDate" />
                        <input id="endDate" type="hidden" data-column='10' name="endDate" />
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center justify-content-center">
                    <button type="button" class="btn btn-primary btn-sm mt-1 me-1 waves-effect waves-float waves-light"
                        id="submitFilters"><i data-lucide="filter"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 waves-effect" id="clearFilters"><i
                            data-lucide="x"></i></button>
                </div>
            </div>
        </form>
        {{ $dataTable->table() }}
    </section>
@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>

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
    <script src="{{ asset('vendors/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/assessment.js')) }}"></script>
    <script>
        $(function() {
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {
                view: '{{ route('assessments.show', ':id') . (request('status') ? '?status=' . request('status') : '') }}'
            });
            myDataTable.initDefaults(defaults);
            /*$("#status").on('select2:select', function (e) {
                const data = e.params.data;
                console.log(data.id);
                url.searchParams.set('status', data.id);
                window.location.href = url;
                myDataTable.filterColumn("assessments-table", $(this).attr('data-column'), data.id);
            });*/
            const url = new URL(window.location.href);
            let startDateField = null;
            let endDateField = null;
            let startDate = moment().subtract(1, 'year');
            let endDate = moment();

            let datePickerCallback = function(startDate, endDate) {
                // console.log(startDate, endDate);
                $("#reportrange").find('span').html(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format(
                    'DD/MM/YYYY'));
                $("#startDate").val(startDate.format('DD-MM-YYYY'));
                $("#endDate").val(endDate.format('DD-MM-YYYY'));
                startDateField = startDate.format('YYYY-MM-DD');
                endDateField = endDate.format('YYYY-MM-DD');
            };

            $("#reportrange").daterangepicker({
                startDate: startDate,
                endDate: endDate,
                autoApply: true,
                autoUpdateInput: true,
                locale: {
                    format: 'DD/MM/YYYY'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month'), moment()],
                    'Last 1 year': [moment().subtract(1, 'year'), moment()],
                }
            }, datePickerCallback);
            datePickerCallback(startDate, endDate);

            $("#submitFilters").on('click', function(event) {
                event.preventDefault();
                let status = $("#status").val();
                if (url.searchParams.has('status')) {
                    status = url.searchParams.get('status');
                }
                url.searchParams.set('status', status);
                url.searchParams.set('start_date', startDateField);
                url.searchParams.set('end_date', endDateField);
                window.location.href = url;
            });

            $("#clearFilters").on('click', function(event) {
                event.preventDefault();
                //document.getElementById("filterForm").reset();
                $("#filterForm").trigger('reset');
                if (!url.searchParams.has('status') || url.searchParams.get('status') !== "PENDING") {
                    url.searchParams.delete('status');
                }
                url.searchParams.delete('start_date');
                url.searchParams.delete('end_date');
                window.location.href = url;
                // .not(':button, :submit, :reset, :hidden')
                // .val('')
                // .prop('checked', false)
                // .prop('selected', false);
            });

            /*$("#reportrange").on('apply.daterangepicker', function (ev, picker) {
                // console.log('applying dp');
                // console.log(ev, picker);
                // myDataTable.search("admin-report-table", JSON.stringify({'start':startDateField,'end':endDateField}));
                myDataTable.filterColumn("studentactivitydatatable-table", $(this).attr('data-column'), JSON.stringify({
                    'start': startDateField,
                    'end': endDateField
                }));
            });*/
        });
    </script>
    {{ $dataTable->scripts() }}
@endsection
