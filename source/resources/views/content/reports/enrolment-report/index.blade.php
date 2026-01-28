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
    <section id="enrolment-report-datatable" style='position:relative;'>
{{--        <form class="dt_adv_search col-10 position-relative" method="POST">--}}
{{--            <div class="row g-1 mb-1">--}}
{{--                <div class="col-md-5">--}}
{{--                    <label class="form-label">Select Enrolment Date:</label>--}}
{{--                    <div id="reportrange" class="form-control" data-column='19'>--}}
{{--                        <i class="fa fa-calendar"></i>&nbsp;--}}
{{--                        <span></span> <i class="fa fa-caret-down"></i>--}}
{{--                        <input id="startDate" type="hidden" data-column='19' name="startDate"/>--}}
{{--                        <input id="endDate" type="hidden" data-column='19' name="endDate"/>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </form>--}}
        {{ $dataTable->table() }}
    </section>

@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
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
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
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
            // defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {
            //     popup: {target: "report-details", _route: "/api/v1/reports/enrolments/:id"} //api/v1/reports/admins/{report}
            // });
            defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {

                view: '{{ route('reports.enrolments.show',':user_id') }}'

            },false,0);
            myDataTable.initDefaults(defaults);

            var select = $('.select2');
            select.each(function () {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                $this.select2({
                    // the following code is used to disable x-scrollbar when click in select input and
                    // take 100% width in responsive also
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                });
            });
            $("#reportrange").on('select2:select', function (e) {
                console.log('dr select2');
                const data = e.params.data;
                console.log(data.id);
                myDataTable.filterColumn("enrolment-report-table", $(this).attr('data-column'), data.id);
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
                console.log('applying dp');
                // console.log(ev, picker);
                // myDataTable.search("enrolment-report-table", JSON.stringify({'start':startDateField,'end':endDateField}));
                myDataTable.filterColumn("enrolment-report-table", $(this).attr('data-column'), JSON.stringify({
                    'start': startDateField,
                    'end': endDateField
                }));
            });
        });
    </script>
    {{$dataTable->scripts()}}
@endsection
