@extends('layouts.contentLayoutMaster')

@section('title','Student Competencies')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet" href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet" href="{{asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-sweet-alerts.css'))}}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')
    <section id="competency-datatable" style='position:relative;'>
        <form class="dt_adv_search col-12 position-relative" id="filterForm">
            <div class="row g-1 mb-1">
               {{-- @if(empty(request('course')))
                    <div class="col col-md-4">
                        <label class="form-label">Select Course:</label>
                        <select data-placeholder="Course" class="select2 form-select" id="course" name='course'
                                data-column="9">
                            <option value="all">All</option>
                            @foreach($courses as $course)
                                <option data-length='{{ $course->course_length_days }}'
                                                                 value="{{ $course->id }}"
                                                                 data-category="{{ $course->category }}"
                                {{ ((old('course') === intval($course->id))?"selected='selected'":"") }} >
                                {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif--}}
                <div class="col col-md-4">
                    <label class="form-label">Select Date:</label>
                    <div id="reportrange" class="form-control" data-column='10'>
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                        <input id="startDate" type="hidden" data-column='10' name="startDate"/>
                        <input id="endDate" type="hidden" data-column='10' name="endDate"/>
                    </div>
                </div>
                <div class="col col-md-4 pt-1">
                    <button type="button" class="btn btn-primary btn-sm mt-1 me-1 waves-effect waves-float waves-light"
                            id="submitFilters">Submit
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 waves-effect" id="clearFilters">
                        Reset
                    </button>
                </div>
            </div>
        </form>
        <div class="clearfix row g-1 mt-1">
            {{ $dataTable->table() }}
        </div>
    </section>
    <div class="modal modal-slide-in fade" id="competencies-details" aria-hidden="true">
        <div class="modal-dialog sidebar-xlg">
            <div class="modal-content p-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">
                        <span class="align-middle">Competency Details</span>
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
    <script src="{{ asset('vendors/js/pickers/flatpickr/flatpickr.min.js')}}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
@endsection
@section('page-script')
    <script src="{{ asset(mix('js/scripts/components/components-accordion.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/lms.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>

        const url = new URL(window.location.href);
        let startDateField = null;
        let endDateField = null;
        let startDate = moment().subtract(1, 'year');
        let endDate = moment();

        let datePickerCallback = function (startDate, endDate) {
            // console.log(startDate, endDate);
            $("#reportrange").find('span').html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
            $("#startDate").val(startDate.format('DD-MM-YYYY'));
            $("#endDate").val(endDate.format('DD-MM-YYYY'));
            startDateField = startDate.format('YYYY-MM-DD');
            endDateField = endDate.format('YYYY-MM-DD');
        };
        $(function () {
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            defaults.columnDefs = myDataTable.setupActionColumnDef(defaults, {
                // popup: {target: "competencies-details", _route: "/api/v1/competencies/:id"} //api/v1/reports/admins/{report}
            },false,0);
            myDataTable.initDefaults(defaults);

            $("#reportrange").daterangepicker({
                startDate: startDate,
                endDate: endDate,
                autoApply: true,
                autoUpdateInput: true,
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

            $("#submitFilters").on('click', function (event) {
                event.preventDefault();
                // let course = $("#course").val();
                // if(url.searchParams.has('course')){
                //     course = url.searchParams.get('course');
                // }
                // url.searchParams.set('course', course);
                url.searchParams.set('start_date', startDateField);
                url.searchParams.set('end_date', endDateField);
                window.location.href = url;
            });

            $("#clearFilters").on('click', function (event) {
                event.preventDefault();
                //document.getElementById("filterForm").reset();
                $("#filterForm").trigger('reset');
                // if(!url.searchParams.has('course')){
                //     url.searchParams.delete('course');
                // }
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

