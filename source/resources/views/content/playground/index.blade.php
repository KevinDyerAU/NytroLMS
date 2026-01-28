@extends('layouts/contentLayoutMaster')

@section('title', 'Playground')

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection
@section('page-style')
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
          href="{{asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-sweet-alerts.css'))}}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
@endsection


@section('content')
    <div class="row">
        <div class="col-5">
            <h2>Admin Report</h2>
            <form method="post" action="{{ route('playground-process') }}" class="d-flex flex-column">
                @csrf
                <label for="course">Course ID</label>
                <input type="text" class="form-control" name="course"/>
                <label for="user">User IDs</label>
                <input type="text" class="form-control" name="user"/>
                <label for="condition">Condition</label>
                <div class="col d-flex flex-row">
                    <select class="select2 form-select" placeholder="type" name="condition[type]">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                        <option value="RAW">RAW</option>
                    </select>
                    <input type="text" class="form-control" placeholder="column" name="condition[column]"/>
                    <input type="text" class="form-control" placeholder="operator" name="condition[operator]"/>
                </div>
                <input type="text" class="form-control" placeholder="value" name="condition[value]"/>
                <label for="user">Column</label>
                <select class="select2 form-select" name="column">
                    @foreach( DB::select("DESCRIBE admin_reports") as $col)
                        @if(!in_array($col->Field, ['id','student_id','course_id','trainer_id','leader_id','company_id','created_at','updated_at']))
                            <option value="{{$col->Field}}">{{ $col->Field }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="hidden" name="form" value="adminReports"/>
                <button type="submit" class="btn btn-primary mt-2" name="submit" value="submit">Submit</button>
            </form>
        </div>
        <div class="col-5">
            <h2>Student Competency</h2>
            <form method="post" action="{{ route('playground-process') }}" class="d-flex flex-column">
                @csrf
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" name="start_date"/>
                <label for="end_date">End Date</label>
                <input type="date" class="form-control" name="end_date"/>
                <input type="hidden" name="form" value="studentCompetency"/>
                <button type="submit" class="btn btn-primary mt-2" name="submit" value="submit">Submit</button>
            </form>
        </div>
        <div class="col-3">
            <h2>Data Migration</h2>
            <form method="post" action="{{ route('playground-migrate-content') }}" class="d-flex flex-column">
                @csrf
                <label for="table">Select Table</label>
                <select name="table" class="form-control">
                    <option value="courses">Courses</option>
                    <option value="lessons">Lessons</option>
                    <option value="topics">Topics</option>
                    <option value="quizzes">Quizzes</option>
                </select>
                <button type="submit" class="btn btn-primary mt-2">Migrate Data</button>
            </form>
        </div>
        <div class="col-3">
            <h2>Misc. Calls</h2>
            <a class="btn btn-purple mt-3" href="{{ route('playground-test') }}">Run Test</a>
        </div>
        <div class="col-3">
            <h2>Course Progress Update</h2>
            <form method="post" action="{{ route('playground-course-progress') }}" class="d-flex flex-column">
                @csrf
                <label for="course_id">Course</label>
                <div class="col d-flex flex-row">
                    <select data-placeholder="Select a Course"
                            class="select2 form-select required"
                            data-class="@error('course_id') is-invalid @enderror"
                            id="course_id"
                            name='course_id' tabindex='3'>
                        <option></option>
                        @php $category = '' @endphp
                        @foreach(\App\Models\Course::notRestricted()->orderBy('category', 'asc')->get() as $course)

                            @if($category !== $course->category)
                                @if($category !== '')
                                    {{ "</optgroup>" }}
                                @endif
                                <optgroup label="{{ config( 'lms.course_category' )[(!empty($course->category) ? $course->category : 'uncategorized')] }}">
                                    @endif

                                    <option value="{{ $course->id }}">
                                        {{ ucwords($course->title) }}
                                    </option>
                                    @php $category = $course->category  @endphp
                                    @endforeach
                                </optgroup>
                    </select>
                </div>
                <div class="col">
                    <label for="users">User IDs</label>
                    <input type="text" class="form-control" id="users" name="users"/>
                </div>
                <button type="submit" class="btn btn-primary mt-2" name="submit" value="submit">Proceed</button>
            </form>
        </div>
        <div class="col-3">
            <h2>Functions to directly execute</h2>
            <form method="post" action="{{ route('playground-process') }}" class="d-flex flex-column">
                @csrf
                <label for="form">Form</label>
                <input type="text" name="form" class="form-control" value="lastLoginFix"/>
                <button type="submit" class="btn btn-primary mt-2" name="submit" value="submit">Submit</button>
            </form>
        </div>

    </div>
    @if(isset($exportForm))
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Export User Data</h4>
                </div>
                <div class="card-body">
                    <form id="exportForm" action="{{ route('user.data.export') }}" method="GET">
                        <div class="form-group">
                            <label for="user_id">User ID</label>
                            <input type="number" class="form-control" id="user_id" name="user_id" required>
                        </div>
                        <button type="submit" class="btn btn-primary mt-1">Export Data</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import User Data</h3>
                </div>
                <div class="card-body">
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="sql_file">Upload SQL File</label>
                            <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                        </div>
                        <button type="button" class="btn btn-success mt-1" id="importButton">Import Data</button>
                    </form>
                    <div id="errorMessage" class="alert alert-danger mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Modal -->
    <div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="summaryModalLabel">Operation Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="summaryContent"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="proceedImport" style="display: none;">Proceed</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Playground</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Verify Student Progress</h5>
                                    <p class="card-text">View and verify student course progress details</p>
                                    <a href="{{ route('playground.verify-progress') }}" class="btn btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Compare Progress</h5>
                                    <p class="card-text">Compare student profile, training plan, and admin report side by side</p>
                                    <a href="{{ route('playground.compare-progress') }}" class="btn btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-danger">
                                        <i class="fas fa-user-times me-2"></i>
                                        User Data Deletion
                                    </h5>
                                    <p class="card-text">Permanently delete all student data including enrolments, quiz attempts, and progress</p>
                                    <a href="{{ route('playground.user-deletion-form') }}" class="btn btn-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Open Tool
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Add more playground tools here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
@endsection
@section('page-script')
    <script>
        $(function () {
            $(".select2").select2({
                placeholder: 'Select Option'
            });
        });
    $(document).ready(function() {
        // Handle Export Form
        $('#exportForm').on('submit', function(e) {
            e.preventDefault();
            const userId = $('#user_id').val();

            $.get($(this).attr('action'), { user_id: userId })
                .done(function(response) {
                    // Create a blob from the response
                    const blob = new Blob([response], { type: 'application/sql' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `user_data_export_${userId}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.sql`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    // Show success message
                    $('#summaryModalLabel').text('Export Summary');
                    $('#summaryContent').html(`
                        <div class="alert alert-success">
                            <h6>Export Successful!</h6>
                            <p>User data for ID: ${userId} has been exported successfully.</p>
                            <p>The SQL file has been downloaded to your computer.</p>
                        </div>
                    `);
                    $('#proceedImport').hide(); // Ensure proceed button is hidden for export
                    $('#summaryModal').modal('show');
                })
                .fail(function(xhr) {
                    $('#summaryModalLabel').text('Export Error');
                    $('#summaryContent').html(`
                        <div class="alert alert-danger">
                            <h6>Export Failed!</h6>
                            <p>${xhr.responseJSON?.error || 'An error occurred during export.'}</p>
                        </div>
                    `);
                    $('#proceedImport').hide(); // Ensure proceed button is hidden for export errors
                    $('#summaryModal').modal('show');
                });
        });

        // Handle Import Form
        $('#importButton').on('click', function() {
            var formData = new FormData($('#importForm')[0]);

            $.ajax({
                url: '{{ route('user.data.import') }}',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#summaryModalLabel').html(response.title);
                    $('#summaryContent').html(response.content);
                    $('#proceedImport').show(); // Show proceed button for import preview
                    var modal = new bootstrap.Modal(document.getElementById('summaryModal'));
                    modal.show();
                },
                error: function(xhr) {
                    var errorMessage = xhr.responseJSON.error || 'An error occurred during import.';
                    $('#errorMessage').text(errorMessage).show();
                    $('#proceedImport').hide(); // Hide proceed button on import error
                }
            });
        });

        // Handle Proceed button click
        $('#proceedImport').on('click', function() {
            var formData = new FormData($('#importForm')[0]);
            formData.append('proceed', true);

            $.ajax({
                url: '{{ route('user.data.import') }}',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#summaryModalLabel').html(response.title);
                    $('#summaryContent').html(response.content);
                    $('#proceedImport').hide(); // Hide proceed button after import is done
                },
                error: function(xhr) {
                    var errorMessage = xhr.responseJSON.error || 'An error occurred during import.';
                    $('#errorMessage').text(errorMessage).show();
                    $('#proceedImport').hide(); // Hide proceed button on import error
                }
            });
        });

    });
    </script>
@endsection
