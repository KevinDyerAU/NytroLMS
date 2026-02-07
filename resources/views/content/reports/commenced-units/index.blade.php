@extends('layouts.contentLayoutMaster')

@section('title', 'Commenced Units Report')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/dataTables.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/responsive.bootstrap4.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/tables/datatable/buttons.bootstrap5.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
@endsection

@section('page-style')
    <style>
        /* Tom Select styling to match Bootstrap form-select */
        .ts-wrapper {
            width: 100%;
            margin: 0 !important;
        }

        .ts-control {
            background: #fff !important;
            border: 1px solid #d8d6de !important;
            border-radius: 0.357rem !important;
            padding: 0.438rem 1rem !important;
            min-height: calc(1.45em + 0.876rem + 2px) !important;
            max-height: 200px !important;
            height: auto !important;
            font-size: 1rem !important;
            line-height: 1.45 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: flex-start !important;
            overflow-y: auto !important;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        }

        .ts-control:focus {
            border-color: #7367f0 !important;
            box-shadow: 0 3px 10px 0 rgba(34, 41, 47, 0.1) !important;
        }

        .ts-control input {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-size: 1rem !important;
        }

        .ts-control .item {
            background: #7367f0 !important;
            color: #fff !important;
            border: none !important;
            padding: 2px 8px !important;
            border-radius: 0.357rem !important;
            margin: 2px 3px 2px 0 !important;
            max-width: 85% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: wrap !important;
        }

        .ts-dropdown {
            background: #fff !important;
            border: 1px solid #d8d6de !important;
            border-radius: 0.357rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            margin-top: 4px !important;
        }

        .ts-dropdown .ts-dropdown-content {
            background: #fff !important;
            max-height: 300px !important;
            overflow-y: auto !important;
        }

        .ts-dropdown .option {
            background: #fff !important;
            padding: 0.5rem 1rem !important;
        }

        .ts-dropdown .option:hover,
        .ts-dropdown .option.active {
            background: #f3f2f7 !important;
            color: #6e6b7b !important;
        }

        /* Ensure DataTable processing overlay has highest z-index */
        .dataTables_processing {
            z-index: 9999 !important;
        }
    </style>
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
@endsection

@section('content')
    <section id="commenced-units-datatable" style='position:relative;'>
        <form class="dt_adv_search col-12 position-relative mb-2" id="filterForm">
            <div class="row g-1 mb-1">
                <div class="col col-md-3">
                    <label class="form-label">Select Course Start Date:</label>
                    <div id="reportrange" class="form-control" data-column='10'>
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span></span> <i class="fa fa-caret-down"></i>
                        <input id="startDate" type="hidden" data-column='10' name="startDate" />
                        <input id="endDate" type="hidden" data-column='10' name="endDate" />
                    </div>
                </div>
                <div class="col col-md-2">
                    <label class="form-label">Study Type:</label>
                    <select id="studyType" class="form-select" name="study_type">
                        <option value="">All Study Types</option>
                        <option value="null">None</option>
                        @foreach (config('constants.study_type') as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3">
                    <label class="form-label">Course Name:</label>
                    <select id="courseName" name="course_name[]" multiple placeholder="Search and select courses...">
                        @foreach (\App\Models\Course::select('id', 'title')->distinct('title')->orderBy('title')->get() as $course)
                            <option value="{{ $course->id }}">{{ $course->id . ' - ' . $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col col-md-3 pt-1">
                    <button type="button" class="btn btn-primary btn-sm mt-1 me-1 waves-effect waves-float waves-light"
                        id="submitFilters">Submit
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 waves-effect" id="clearFilters">
                        Reset
                    </button>
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
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
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
    <script>
        $(function() {
            let defaults = myDataTable.setupDefaults();
            defaults.responsive = true;
            defaults.columnDefs = [];
            myDataTable.initDefaults(defaults);

            const url = new URL(window.location.href);
            let startDateField = null;
            let endDateField = null;

            // Get dates from URL parameters or use defaults
            let startDate = url.searchParams.get('start_date') ? moment(url.searchParams.get('start_date')) :
                moment().subtract(1, 'year');
            let endDate = url.searchParams.get('end_date') ? moment(url.searchParams.get('end_date')) : moment();

            // Set study type from URL parameter
            let studyType = url.searchParams.get('study_type') || '';
            $('#studyType').val(studyType);

            // Initialize Tom Select for course names
            let courseSelect = new TomSelect('#courseName', {
                plugins: ['remove_button', 'clear_button', 'dropdown_input'],
                persist: false,
                create: false,
                maxOptions: 500,
                placeholder: 'Search and select courses...',
                controlInput: null
            });

            // Set course names from URL parameter
            let courseNames = url.searchParams.get('course_name');
            if (courseNames) {
                let courseIds = courseNames.split(',');
                courseSelect.setValue(courseIds);
            }

            let datePickerCallback = function(startDate, endDate) {
                $("#reportrange").find('span').html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format(
                    'MMMM D, YYYY'));
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
                url.searchParams.set('start_date', startDateField);
                url.searchParams.set('end_date', endDateField);

                let studyType = $('#studyType').val();
                if (studyType && studyType !== '') {
                    url.searchParams.set('study_type', studyType);
                } else {
                    url.searchParams.delete('study_type');
                }

                // Handle course names
                let courseNames = courseSelect.getValue();
                url.searchParams.delete('course_name');
                if (courseNames && courseNames.length > 0) {
                    url.searchParams.set('course_name', courseNames.join(','));
                }

                window.location.href = url;
            });

            $("#clearFilters").on('click', function(event) {
                event.preventDefault();
                $("#filterForm").trigger('reset');
                courseSelect.clear();
                url.searchParams.delete('start_date');
                url.searchParams.delete('end_date');
                url.searchParams.delete('study_type');
                url.searchParams.delete('course_name');
                window.location.href = url;
            });
        });
    </script>
    {{ $dataTable->scripts() }}
@endsection
