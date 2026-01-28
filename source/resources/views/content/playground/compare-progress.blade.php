@extends('layouts.contentLayoutMaster')

@section('title', 'Compare Progress')

@section('vendor-style')
    <style>
    .json-tree-wrapper {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.357rem;
        font-family: monospace;
        font-size: 14px;
        max-height: 600px;
        overflow-y: auto;
    }
    .json-tree-wrapper ul {
        list-style: none;
        margin: 0;
        padding: 0 0 0 20px;
    }
    .json-tree-wrapper li {
        position: relative;
        padding: 2px 0;
    }
    .json-tree-wrapper .key {
        color: #7367f0;
        font-weight: bold;
    }
    .json-tree-wrapper .string { color: #28c76f; }
    .json-tree-wrapper .number { color: #ff9f43; }
    .json-tree-wrapper .boolean { color: #00cfe8; }
    .json-tree-wrapper .null { color: #ff4961; }    .json-tree-wrapper .collapsible {
        cursor: pointer;
        user-select: none;
        position: relative;
        padding-left: 15px;
    }
    .json-tree-wrapper .collapsible::before {
        content: '-';
        position: absolute;
        left: 0;
        width: 10px;
        text-align: center;
        color: #666;
    }
    .json-tree-wrapper .collapsible.collapsed::before {
        content: '+';
    }
    .comparison-section {
        margin-bottom: 1rem;
    }
    .comparison-section h6 {
        margin-bottom: 0.5rem;
    }
    .alert-mismatch {
        background-color: #ffe5e5;
        border-color: #ff4961;
        color: #ff4961;
    }
    .diff-highlight {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
    }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Compare Student Progress Data</h4>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="student_id">Student ID</label>
                        <input type="number" class="form-control" id="student_id" name="student_id" placeholder="Enter Student ID" value="{{ $user_id ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="course_id">Course ID</label>
                        <input type="number" class="form-control" id="course_id" name="course_id" placeholder="Enter Course ID" value="{{ $course_id ?? '' }}">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary" id="compareBtn">Compare Progress</button>
                    </div>
                </div>

                <div class="row">
                    <!-- Student Profile Section -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Student Profile</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" id="expandProfile">Expand</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="collapseProfile">Collapse</button>
                                </div>
                                <div id="profile-data" class="json-tree-wrapper">
                                    Enter student and course IDs above and click Compare Progress
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Training Plan Section -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Training Plan</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" id="expandTraining">Expand</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="collapseTraining">Collapse</button>
                                </div>
                                <div id="training-data" class="json-tree-wrapper">
                                    Enter student and course IDs above and click Compare Progress
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Report Section -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Admin Report</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" id="expandAdmin">Expand</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="collapseAdmin">Collapse</button>
                                </div>
                                <div id="admin-data" class="json-tree-wrapper">
                                    Enter student and course IDs above and click Compare Progress
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comparison Results -->
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Comparison Results</h5>
                            </div>
                            <div class="card-body">
                                <div id="comparison-results">
                                    Enter student and course IDs above and click Compare Progress
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
    {{-- vendor scripts --}}
    <script src="{{ asset(mix('js/scripts/_my/json-tree.js')) }}"></script>
@endsection

@section('page-script')
<script>
    $(function() {
        // Initialize JSON Tree viewers
        let profileTree = new JSONTree(document.getElementById('profile-data'));
        let trainingTree = new JSONTree(document.getElementById('training-data'));
        let adminTree = new JSONTree(document.getElementById('admin-data'));

        // Handle comparison
        function compareProgress() {
            const student_id = $('#student_id').val();
            const course_id = $('#course_id').val();

            if (!student_id || !course_id) {
                alert('Please enter both Student ID and Course ID');
                return;
            }

            // Update URL without reloading
            const url = new URL(window.location);
            url.pathname = `/playground/compare-progress/${student_id}/${course_id}`;
            window.history.pushState({}, '', url);

            // Show loading state
            $('#profile-data, #training-data, #admin-data, #comparison-results').text('Loading...');

            // Fetch comparison details
            $.get(`/playground/compare-progress/${student_id}/${course_id}/details`, function(response) {
                // Update JSON trees
                profileTree.render({
                    student: response.student,
                    course: response.course,
                    enrollment: response.enrollment,
                    course_progress: response.course_progress
                });

                trainingTree.render(response.training_plan);
                adminTree.render(response.admin_report);

                // Compare and show results
                let results = [];
                
                // Compare progress percentages
                const profileProgress = response.course_progress?.percentage || 0;
                const adminProgress = response.admin_report?.student_course_progress?.current_course_progress || 0;
                
                if (profileProgress !== adminProgress) {
                    results.push(`
                        <div class="alert alert-mismatch">
                            Progress percentage mismatch:<br>
                            Profile: <span class="diff-highlight">${profileProgress}%</span><br>
                            Admin Report: <span class="diff-highlight">${adminProgress}%</span>
                        </div>
                    `);
                }

                // Compare completion dates
                const profileCompleted = response.enrollment?.course_completed_at;
                const adminCompleted = response.admin_report?.course_completed_at;
                
                if (profileCompleted !== adminCompleted) {
                    results.push(`
                        <div class="alert alert-mismatch">
                            Completion date mismatch:<br>
                            Profile: <span class="diff-highlight">${profileCompleted || 'Not set'}</span><br>
                            Admin Report: <span class="diff-highlight">${adminCompleted || 'Not set'}</span>
                        </div>
                    `);
                }

                // Compare expiry dates
                const profileExpiry = response.enrollment?.course_expiry;
                const adminExpiry = response.admin_report?.course_expiry;
                
                if (profileExpiry !== adminExpiry) {
                    results.push(`
                        <div class="alert alert-mismatch">
                            Expiry date mismatch:<br>
                            Profile: <span class="diff-highlight">${profileExpiry || 'Not set'}</span><br>
                            Admin Report: <span class="diff-highlight">${adminExpiry || 'Not set'}</span>
                        </div>
                    `);
                }

                // Show results
                $('#comparison-results').html(
                    results.length > 0 
                        ? results.join('')
                        : '<div class="alert alert-success">All data is in sync!</div>'
                );

            }).fail(function(error) {
                $('#comparison-results').html(`
                    <div class="alert alert-danger">
                        Error fetching comparison details: ${error.responseJSON?.error || error.statusText}
                    </div>
                `);
            });
        }

        // Handle compare button click
        $('#compareBtn').on('click', compareProgress);

        // Handle Enter key in input fields
        $('#student_id, #course_id').on('keypress', function(e) {
            if (e.which === 13) {
                compareProgress();
            }
        });

        // Handle expand/collapse buttons for each section
        $('#expandProfile').on('click', () => profileTree.expandAll());
        $('#collapseProfile').on('click', () => profileTree.collapseAll());
        
        $('#expandTraining').on('click', () => trainingTree.expandAll());
        $('#collapseTraining').on('click', () => trainingTree.collapseAll());
        
        $('#expandAdmin').on('click', () => adminTree.expandAll());
        $('#collapseAdmin').on('click', () => adminTree.collapseAll());

        // Initialize with existing values if present
        if ($('#student_id').val() && $('#course_id').val()) {
            compareProgress();
        }
    });
</script>
@endsection
