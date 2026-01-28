@extends('layouts/contentLayoutMaster')

@section('title', $reportTitle)

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
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet" href="{{asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-sweet-alerts.css'))}}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
@endsection

@section('content')
    <section id="admin-report-datatable" style='position:relative;'>
        <form class="dt_adv_search col-10 position-relative" method="POST">
            <div class="row g-1 mb-1">
                <div class="col-md-5">
                    <label class="form-label">Select Course Start Date:</label>
                    <div id="reportrange" class="form-control" data-column='28'>
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                        <input id="startDate" type="hidden" data-column='28' name="startDate"/>
                        <input id="endDate" type="hidden" data-column='28' name="endDate"/>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Company:</label>
                    <select class="select2 form-select" id="company-filter"
                            name='company-filter[]' data-column='company' multiple>
                        @foreach($companies as $company)
                            <option
                                value="{{ $company->id }}" {{ (is_array(request('company')) && in_array($company->id, request('company'))) || (request('company') && intval(request('company')) === intval($company->id)) ?'selected':'' }}>{{ \Str::title($company->name) }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isLeader())
                    <div class="col-md-3">
                        <label class="form-label">Company:</label>
                        <select class="select2 form-select" id="company"
                                name='company'>
                            @foreach( auth()->user()->companies as $company)
                                <option
                                    value="{{ $company->id }}" {{ ( intval(request('company')) === intval($company->id)) ?'selected':'' }}>{{ \Str::title($company->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </form>
        {{$dataTable->table()}}
    </section>
    <div class="modal modal-slide-in fade" id="report-details" aria-hidden="true">
        <div class="modal-dialog sidebar-lg">
            <div class="modal-content p-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">
                        <span class="align-middle">Report Details</span>
                    </h5>
                </div>
                <div class="modal-body flex-grow-1 blockUI">
                </div>
            </div>
        </div>
    </div>

@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
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
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
@endsection
@section('page-script')
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        var startDateField = null;
        var endDateField = null;
        var startDate = moment().subtract(1, 'year');
        var endDate = moment();
        datePickerCallback = function (startDate, endDate) {
            $("#reportrange").find('span').html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
            $("#startDate").val(startDate.format('DD-MM-YYYY'));
            $("#endDate").val(endDate.format('DD-MM-YYYY'));
            startDateField = startDate.format('YYYY-MM-DD');
            endDateField = endDate.format('YYYY-MM-DD');
            // console.log(startDateField,endDateField);
        };
        $(function () {
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {
                popup: {target: "report-details", _route: "/api/v1/reports/admins/:id"} //api/v1/reports/admins/{report}
            },false,0);
            myDataTable.initDefaults(defaults);

            var select = $('.select2');
            select.each(function () {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                var select2Options = {
                    // the following code is used to disable x-scrollbar when click in select input and
                    // take 100% width in responsive also
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                };
                // Enable multiple selection and search for company-filter
                if ($this.attr('id') === 'company-filter') {
                    select2Options.multiple = true;
                    select2Options.placeholder = 'Select companies...';
                    select2Options.allowClear = true;
                }
                $this.select2(select2Options);
            });
            $("#company").on("select2:select", function (e) {
                const data = e.params.data;
                console.log('company selected', data.id);
                window.location.href = "?company=" + data.id;
            });
            const getCompanyColumnIndex = function() {
                if (!$.fn.DataTable.isDataTable('#admin-report-table')) {
                    return null;
                }
                const table = $('#admin-report-table').DataTable();
                let columnIndex = null;
                table.columns().every(function() {
                    const column = table.column(this);
                    const dataSrc = column.dataSrc();
                    if (dataSrc === 'company') {
                        columnIndex = column.index();
                        return false;
                    }
                });
                return columnIndex;
            };

            const handleCompanyFilter = function() {
                const selectedCompanies = $("#company-filter").val();
                const columnIndex = getCompanyColumnIndex();
                if (columnIndex !== null && columnIndex !== undefined) {
                    // Pass array as JSON string for backend processing
                    const filterValue = selectedCompanies && selectedCompanies.length > 0
                        ? JSON.stringify(selectedCompanies)
                        : '';
                    myDataTable.filterColumn("admin-report-table", columnIndex, filterValue);
                }
            };

            $("#company-filter").on("select2:select", function (e) {
                handleCompanyFilter();
            });
            $("#company-filter").on("select2:unselect", function (e) {
                handleCompanyFilter();
            });
            $("#company-filter").on("select2:clear", function (e) {
                handleCompanyFilter();
            });
            $("#reportrange").on('select2:select', function (e) {
                console.log('dr select2');
                const data = e.params.data;
                console.log(data.id);
                myDataTable.filterColumn("admin-report-table", $(this).attr('data-column'), data.id);
            });
            $("#reportrange").daterangepicker({
                startDate: startDate,
                endDate: endDate,
                autoApply: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'month'), moment()],
                    'Last 1 year': [moment().subtract(1, 'year'), moment()],
                }
            }, datePickerCallback);
            datePickerCallback(startDate, endDate);

            $("#reportrange").on('apply.daterangepicker', function (ev, picker) {
                // console.log(ev, picker);
                // myDataTable.search("admin-report-table", JSON.stringify({'start':startDateField,'end':endDateField}));
                const date_range = JSON.stringify({
                    'start': startDateField,
                    'end': endDateField
                });
                // console.log('applying dp', date_range, $(this).attr('data-column'));
                myDataTable.filterColumn("admin-report-table", $(this).attr('data-column'), date_range);
            });
        });
    </script>
    {{$dataTable->scripts()}}
@endsection
