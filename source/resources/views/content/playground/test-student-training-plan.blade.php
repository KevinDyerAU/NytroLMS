@extends('layouts/contentLayoutMaster')

@section('title', 'Test Student Training Plan')

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
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Test Student Training Plan
                    </h4>
                    <p class="card-text">
                        Debug and analyze student training plan data. This tool helps you understand what raw data is being returned by the StudentTrainingPlanService.
                        Similar to the API endpoint: <code>/api/v1/student/training-plan/{student_id}</code>
                    </p>
                </div>
                <div class="card-body">
                    <form id="testTrainingPlanForm" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label for="student_id" class="form-label">
                                <strong>Student ID</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control form-control-lg" id="student_id" name="student_id"
                                   placeholder="Enter student ID (e.g., 21340)" required min="1"/>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                The student ID to test the training plan for.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="course_id" class="form-label">
                                <strong>Course ID</strong> <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="number" class="form-control form-control-lg" id="course_id" name="course_id"
                                   placeholder="Enter course ID (optional)" min="1"/>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Optional: Filter results to a specific course.
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="testButton">
                                <i class="fas fa-search me-2"></i>Test Training Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div id="testResults" class="row mt-4" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Training Plan Test Results
                    </h5>
                </div>
                <div class="card-body" id="resultsContent">
                    <!-- Results will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- What This Tool Does -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>
                        What This Tool Does
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary"><i class="fas fa-database me-2"></i>Raw Data Analysis</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Shows raw training plan data from StudentTrainingPlanService</li>
                                <li><i class="fas fa-check text-success me-2"></i>Displays student progress details and percentages</li>
                                <li><i class="fas fa-check text-success me-2"></i>Shows course enrolments and status</li>
                                <li><i class="fas fa-check text-success me-2"></i>Reveals total counts and calculations</li>
                                <li><i class="fas fa-check text-success me-2"></i>Copy raw JSON data to clipboard</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-info"><i class="fas fa-bug me-2"></i>Debugging Features</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Validates progress data integrity</li>
                                <li><i class="fas fa-check text-success me-2"></i>Shows rendered HTML output</li>
                                <li><i class="fas fa-check text-success me-2"></i>Provides detailed error information</li>
                                <li><i class="fas fa-check text-success me-2"></i>Includes debug timestamps and metadata</li>
                                <li><i class="fas fa-check text-success me-2"></i>Visual accordion display like actual training plan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Comparison -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-code me-2"></i>
                        API Endpoint Comparison
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-warning"><i class="fas fa-external-link-alt me-2"></i>Original API</h6>
                            <p><code>GET /api/v1/student/training-plan/{student_id}</code></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-arrow-right text-muted me-2"></i>Returns rendered HTML training plan</li>
                                <li><i class="fas fa-arrow-right text-muted me-2"></i>Limited debugging information</li>
                                <li><i class="fas fa-arrow-right text-muted me-2"></i>Production-ready response format</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="fas fa-tools me-2"></i>This Debug Tool</h6>
                            <p><code>GET /playground/test-student-training-plan/api</code></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Shows both raw data AND rendered HTML</li>
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Detailed progress breakdown</li>
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Course-specific filtering</li>
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Comprehensive debugging info</li>
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Copy functionality for JSON and HTML</li>
                                <li><i class="fas fa-arrow-right text-success me-2"></i>Visual accordion display</li>
                            </ul>
                        </div>
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
        $(document).ready(function() {
            // Handle Test Training Plan Form
            $('#testTrainingPlanForm').on('submit', function(e) {
                e.preventDefault();

                const studentId = $('#student_id').val().trim();
                const courseId = $('#course_id').val().trim();
                const testButton = $('#testButton');
                const testResults = $('#testResults');
                const resultsContent = $('#resultsContent');

                if (!studentId) {
                    toastr.error('Please enter a student ID');
                    return;
                }

                // Disable button and show loading state
                testButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testing...');
                testResults.hide();

                // Build query parameters
                const params = new URLSearchParams();
                params.append('student_id', studentId);
                if (courseId) {
                    params.append('course_id', courseId);
                }

                $.ajax({
                    url: '{{ route('playground.test-student-training-plan') }}?' + params.toString(),
                    type: 'GET',
                    success: function(response) {
                        // Show results
                        let resultsHtml = '';

                        // Student Info Card
                        resultsHtml += '<div class="row mb-4">';
                        resultsHtml += '<div class="col-12">';
                        resultsHtml += '<div class="card border-primary">';
                        resultsHtml += '<div class="card-header bg-primary text-white">';
                        resultsHtml += '<h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Student Information</h5>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="card-body">';
                        resultsHtml += '<div class="row">';
                        resultsHtml += '<div class="col-md-3"><strong>ID:</strong> ' + response.student.id + '</div>';
                        resultsHtml += '<div class="col-md-3"><strong>Name:</strong> ' + response.student.name + '</div>';
                        resultsHtml += '<div class="col-md-3"><strong>Email:</strong> ' + response.student.email + '</div>';
                        resultsHtml += '<div class="col-md-3"><strong>Full Name:</strong> ' + response.student.first_name + ' ' + response.student.last_name + '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';

                        // Summary Card
                        resultsHtml += '<div class="row mb-4">';
                        resultsHtml += '<div class="col-12">';
                        resultsHtml += '<div class="card border-info">';
                        resultsHtml += '<div class="card-header bg-info text-white">';
                        resultsHtml += '<h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Summary</h5>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="card-body">';
                        resultsHtml += '<div class="row">';
                        resultsHtml += '<div class="col-md-4 text-center">';
                        resultsHtml += '<h3 class="text-primary">' + response.summary.total_courses_with_progress + '</h3>';
                        resultsHtml += '<p class="mb-0">Courses with Progress</p>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="col-md-4 text-center">';
                        resultsHtml += '<h3 class="text-info">' + response.summary.total_enrolments + '</h3>';
                        resultsHtml += '<p class="mb-0">Total Enrolments</p>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="col-md-4 text-center">';
                        resultsHtml += '<h3 class="text-success">' + response.summary.raw_training_plan_courses + '</h3>';
                        resultsHtml += '<p class="mb-0">Training Plan Courses</p>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';

                        // Raw Training Plan Data
                        resultsHtml += '<div class="row mb-4">';
                        resultsHtml += '<div class="col-12">';
                        resultsHtml += '<div class="card">';
                        resultsHtml += '<div class="card-header d-flex justify-content-between align-items-center">';
                        resultsHtml += '<h6 class="card-title mb-0"><i class="fas fa-code me-2"></i>Raw Training Plan Data</h6>';
                        resultsHtml += '<button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(\'rawTrainingPlanData\')">';
                        resultsHtml += '<i class="fas fa-copy me-1"></i>Copy JSON';
                        resultsHtml += '</button>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="card-body">';
                        resultsHtml += '<pre id="rawTrainingPlanData" class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>' + JSON.stringify(response.raw_training_plan, null, 2) + '</code></pre>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';

                        // Visual Training Plan Output
                        if (response.rendered_training_plan && response.rendered_training_plan.html) {
                            resultsHtml += '<div class="row mb-4">';
                            resultsHtml += '<div class="col-12">';
                            resultsHtml += '<div class="card">';
                            resultsHtml += '<div class="card-header d-flex justify-content-between align-items-center">';
                            resultsHtml += '<h6 class="card-title mb-0"><i class="fas fa-eye me-2"></i>Visual Training Plan Output</h6>';
                            resultsHtml += '<button type="button" class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(\'visualTrainingPlanData\')">';
                            resultsHtml += '<i class="fas fa-copy me-1"></i>Copy HTML';
                            resultsHtml += '</button>';
                            resultsHtml += '</div>';
                            resultsHtml += '<div class="card-body">';
                            resultsHtml += '<div id="visualTrainingPlanData" class="border rounded p-3" style="max-height: 600px; overflow-y: auto;">';
                            resultsHtml += response.rendered_training_plan.html;
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                        }

                        // Student Progress Details
                        if (response.student_progress && response.student_progress.length > 0) {
                            resultsHtml += '<div class="row mb-4">';
                            resultsHtml += '<div class="col-12">';
                            resultsHtml += '<div class="card">';
                            resultsHtml += '<div class="card-header">';
                            resultsHtml += '<h6 class="card-title"><i class="fas fa-tasks me-2"></i>Student Progress Details</h6>';
                            resultsHtml += '</div>';
                            resultsHtml += '<div class="card-body">';

                            response.student_progress.forEach(function(progress) {
                                resultsHtml += '<div class="border rounded p-3 mb-3">';
                                resultsHtml += '<div class="d-flex justify-content-between align-items-start">';
                                resultsHtml += '<div>';
                                resultsHtml += '<h6 class="mb-1">' + progress.course_title + ' <span class="badge bg-secondary">ID: ' + progress.course_id + '</span></h6>';
                                resultsHtml += '<p class="mb-1"><strong>Progress ID:</strong> ' + progress.progress_id + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>Percentage:</strong> ' + progress.percentage + '%</p>';
                                resultsHtml += '<p class="mb-1"><strong>Valid Progress:</strong> ' + (progress.is_valid_progress ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') + '</p>';

                                if (progress.total_counts) {
                                    resultsHtml += '<p class="mb-1"><strong>Total Counts:</strong></p>';
                                    resultsHtml += '<ul class="mb-0">';
                                    Object.entries(progress.total_counts).forEach(([key, value]) => {
                                        resultsHtml += '<li><strong>' + key + ':</strong> ' + value + '</li>';
                                    });
                                    resultsHtml += '</ul>';
                                }
                                resultsHtml += '</div>';

                                // Status badge
                                if (progress.is_valid_progress) {
                                    resultsHtml += '<span class="badge bg-success">Valid</span>';
                                } else {
                                    resultsHtml += '<span class="badge bg-danger">Invalid</span>';
                                }

                                resultsHtml += '</div>';
                                resultsHtml += '</div>';
                            });

                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                        }

                        // Enrolments
                        if (response.enrolments && response.enrolments.length > 0) {
                            resultsHtml += '<div class="row mb-4">';
                            resultsHtml += '<div class="col-12">';
                            resultsHtml += '<div class="card">';
                            resultsHtml += '<div class="card-header">';
                            resultsHtml += '<h6 class="card-title"><i class="fas fa-book me-2"></i>Student Enrolments</h6>';
                            resultsHtml += '</div>';
                            resultsHtml += '<div class="card-body">';

                            response.enrolments.forEach(function(enrolment) {
                                resultsHtml += '<div class="border rounded p-3 mb-3">';
                                resultsHtml += '<div class="d-flex justify-content-between align-items-start">';
                                resultsHtml += '<div>';
                                resultsHtml += '<h6 class="mb-1">' + enrolment.course_title + ' <span class="badge bg-secondary">ID: ' + enrolment.course_id + '</span></h6>';
                                resultsHtml += '<p class="mb-1"><strong>Enrolment ID:</strong> ' + enrolment.id + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>Status:</strong> ' + enrolment.status + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>Start Date:</strong> ' + (enrolment.course_start_at || 'N/A') + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>End Date:</strong> ' + (enrolment.course_ends_at || 'N/A') + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>Expiry:</strong> ' + (enrolment.course_expiry || 'N/A') + '</p>';
                                resultsHtml += '<p class="mb-1"><strong>Completed:</strong> ' + (enrolment.course_completed_at || 'Not completed') + '</p>';
                                resultsHtml += '</div>';

                                // Status badge
                                const statusClass = enrolment.status === 'ACTIVE' ? 'bg-success' : 'bg-warning';
                                resultsHtml += '<span class="badge ' + statusClass + '">' + enrolment.status + '</span>';

                                resultsHtml += '</div>';
                                resultsHtml += '</div>';
                            });

                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                        }

                        // Course Specific Data (if course_id was provided)
                        if (response.course_specific_data) {
                            resultsHtml += '<div class="row mb-4">';
                            resultsHtml += '<div class="col-12">';
                            resultsHtml += '<div class="card border-warning">';
                            resultsHtml += '<div class="card-header bg-warning text-dark">';
                            resultsHtml += '<h6 class="card-title"><i class="fas fa-filter me-2"></i>Course Specific Data</h6>';
                            resultsHtml += '</div>';
                            resultsHtml += '<div class="card-body">';
                            resultsHtml += '<pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"><code>' + JSON.stringify(response.course_specific_data, null, 2) + '</code></pre>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                            resultsHtml += '</div>';
                        }

                        // Debug Info
                        resultsHtml += '<div class="row mb-4">';
                        resultsHtml += '<div class="col-12">';
                        resultsHtml += '<div class="card border-secondary">';
                        resultsHtml += '<div class="card-header bg-secondary text-white">';
                        resultsHtml += '<h6 class="card-title"><i class="fas fa-bug me-2"></i>Debug Information</h6>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="card-body">';
                        resultsHtml += '<div class="row">';
                        resultsHtml += '<div class="col-md-6">';
                        resultsHtml += '<p><strong>Student ID:</strong> ' + response.debug_info.student_id + '</p>';
                        resultsHtml += '<p><strong>Course ID:</strong> ' + (response.debug_info.course_id || 'Not specified') + '</p>';
                        resultsHtml += '</div>';
                        resultsHtml += '<div class="col-md-6">';
                        resultsHtml += '<p><strong>Timestamp:</strong> ' + response.debug_info.timestamp + '</p>';
                        resultsHtml += '<p><strong>Service Class:</strong> ' + response.debug_info.service_class + '</p>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';
                        resultsHtml += '</div>';

                        resultsContent.html(resultsHtml);
                        testResults.show();

                        // Scroll to results
                        testResults[0].scrollIntoView({ behavior: 'smooth' });

                        // Show success toast
                        toastr.success('Training plan test completed successfully!');
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred during testing.';
                        let errorDetails = '';

                        if (xhr.responseJSON) {
                            errorMessage = xhr.responseJSON.error || errorMessage;
                            if (xhr.responseJSON.trace) {
                                errorDetails = '<br><small class="text-muted">Stack trace available in console</small>';
                            }
                        } else if (xhr.responseText) {
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                errorMessage = errorData.error || errorMessage;
                            } catch (e) {
                                errorMessage = xhr.responseText;
                            }
                        }

                        resultsContent.html(`
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Test Failed</h6>
                                <p>${errorMessage}${errorDetails}</p>
                            </div>
                        `);
                        testResults.show();

                        // Show error toast
                        toastr.error('Test failed: ' + errorMessage);
                    },
                    complete: function() {
                        // Re-enable button
                        testButton.prop('disabled', false).html('<i class="fas fa-search me-2"></i>Test Training Plan');
                    }
                });
            });

            // Auto-focus on student ID input when page loads
            $('#student_id').focus();
        });

        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                toastr.error('Element not found');
                return;
            }

            let textToCopy = '';

            if (elementId === 'rawTrainingPlanData') {
                // For JSON data, get the text content from the code element
                const codeElement = element.querySelector('code');
                textToCopy = codeElement ? codeElement.textContent : element.textContent;
            } else if (elementId === 'visualTrainingPlanData') {
                // For HTML data, get the innerHTML
                textToCopy = element.innerHTML;
            } else {
                textToCopy = element.textContent || element.innerText;
            }

            // Use the modern clipboard API if available
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    toastr.success('Copied to clipboard!');
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopyTextToClipboard(textToCopy);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(textToCopy);
            }
        }

        // Fallback copy function for older browsers
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;

            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    toastr.success('Copied to clipboard!');
                } else {
                    toastr.error('Failed to copy to clipboard');
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                toastr.error('Failed to copy to clipboard');
            }

            document.body.removeChild(textArea);
        }
    </script>
@endsection
