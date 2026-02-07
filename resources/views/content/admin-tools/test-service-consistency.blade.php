@extends('layouts/contentLayoutMaster')

@section('title', 'Admin Tools - Test Service Consistency')

@section('vendor-style')
    {{-- vendor css files --}}
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
@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Admin Tools - Test Service Consistency</h4>
                    <p class="card-text">Test and compare course progress calculations between LMS
                        (StudentTrainingPlanService) and Admin Report (CourseProgressService) services.</p>
                </div>
                <div class="card-body">
                    <!-- Test Form -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h5 class="mb-2">Service Consistency Test</h5>
                            <p class="text-muted">Test a specific user and course combination to verify consistency between
                                LMS and Admin Report services</p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> LLN and PTR courses are excluded from this test as they are not
                                supported by the StudentTrainingPlanService.
                            </div>
                        </div>
                    </div>

                    <form id="testForm" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="user_id" class="form-label">User ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="user_id" name="user_id" required>
                            <div class="form-text">Enter the user ID to test</div>
                        </div>
                        <div class="col-md-4">
                            <label for="course_id" class="form-label">Course ID</label>
                            <input type="number" class="form-control" id="course_id" name="course_id">
                            <div class="form-text">Optional: Enter specific course ID, or leave blank to use first available
                                course (LLN/PTR courses are automatically excluded)</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i data-lucide="play" class="me-1"></i> Run Test
                            </button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="resultsSection" class="mt-4" style="display: none;">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Test Results</h5>
                                <div id="resultsContent"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center mt-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Running tests...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Test Form Handler
            document.getElementById('testForm').addEventListener('submit', function(e) {
                e.preventDefault();
                runTest();
            });

            function showLoading() {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('resultsSection').style.display = 'none';
            }

            function hideLoading() {
                document.getElementById('loadingSpinner').style.display = 'none';
            }

            function showResults(content) {
                document.getElementById('resultsContent').innerHTML = content;
                document.getElementById('resultsSection').style.display = 'block';
            }

            function runTest() {
                const userId = document.getElementById('user_id').value;
                const courseId = document.getElementById('course_id').value;

                if (!userId) {
                    alert('Please enter a User ID');
                    return;
                }

                showLoading();

                let url = `{{ route('admin-tools.test-service-consistency') }}?user_id=${userId}`;
                if (courseId) {
                    url += `&course_id=${courseId}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        displayTestResults(data);
                    })
                    .catch(error => {
                        hideLoading();
                        showResults(
                            `<div class="alert alert-danger"><strong>Error:</strong> ${error.message}</div>`
                        );
                    });
            }

            function displayTestResults(data) {
                let content = '';

                // Handle debug response
                if (data.debug) {
                    content = `
                <div class="alert alert-success">
                    <strong>Debug Response:</strong> ${data.debug}<br>
                    <strong>User ID:</strong> ${data.user_id}<br>
                    <strong>Course ID:</strong> ${data.course_id || 'Not provided'}<br>
                    <strong>Timestamp:</strong> ${data.timestamp}
                </div>
                <div class="alert alert-info">
                    <strong>Note:</strong> This is a debug response. The route is working correctly!
                </div>
            `;
                } else if (data.error) {
                    let errorContent =
                        `<div class="alert alert-danger"><strong>Error:</strong> ${data.error}</div>`;

                    // If there are enrolled courses, show them
                    if (data.enrolled_courses && data.enrolled_courses.length > 0) {
                        errorContent += `
                    <div class="alert alert-info">
                        <strong>Available Courses for this User:</strong>
                        <ul class="mb-0 mt-2">
                            ${data.enrolled_courses.map(course =>
                                `<li><strong>ID:</strong> ${course.id} - <strong>Title:</strong> ${course.title} - <strong>Category:</strong> ${course.category}</li>`
                            ).join('')}
                        </ul>
                        <p class="mt-2 mb-0"><strong>Tip:</strong> Try entering one of these course IDs in the Course ID field above.</p>
                    </div>
                `;
                    }

                    if (data.message) {
                        errorContent +=
                            `<div class="alert alert-warning"><strong>Note:</strong> ${data.message}</div>`;
                    }

                    content = errorContent;
                } else {
                    const isConsistent = data.consistent;
                    const alertClass = isConsistent ? 'alert-success' : 'alert-warning';
                    const icon = isConsistent ? 'check-circle' : 'alert-triangle';

                    content = `
                <div class="alert ${alertClass}">
                    <i data-lucide="${icon}" class="me-2"></i>
                    <strong>${data.message}</strong>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Test Information</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td><strong>User ID:</strong></td><td>${data.data.user_id}</td></tr>
                                    <tr><td><strong>Course ID:</strong></td><td>${data.data.course_id}</td></tr>
                                    <tr><td><strong>Course Title:</strong></td><td>${data.data.course_title}</td></tr>
                                    <tr><td><strong>Course Category:</strong></td><td>${data.data.course_category}</td></tr>
                                    <tr><td><strong>Main Course:</strong></td><td>${data.data.is_main_course ? 'Yes' : 'No'}</td></tr>
                                    <tr><td><strong>LLND Excluded:</strong></td><td>${data.data.is_llnd_excluded ? 'Yes' : 'No'}</td></tr>
                                    <tr><td><strong>User Onboarded:</strong></td><td>${data.data.user_onboarded ? 'Yes' : 'No'}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Comparison Results</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td><strong>Counts Match:</strong></td><td><span class="badge ${data.data.comparison.counts_match ? 'bg-success' : 'bg-danger'}">${data.data.comparison.counts_match ? 'Yes' : 'No'}</span></td></tr>
                                    <tr><td><strong>Percentage Match:</strong></td><td><span class="badge ${data.data.comparison.percentage_match ? 'bg-success' : 'bg-danger'}">${data.data.comparison.percentage_match ? 'Yes' : 'No'}</span></td></tr>
                                    <tr><td><strong>Percentage Difference:</strong></td><td>${data.data.comparison.percentage_difference.toFixed(4)}%</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">LMS Service (StudentTrainingPlanService)</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td><strong>Percentage:</strong></td><td>${data.data.lms_service.percentage.toFixed(2)}%</td></tr>
                                    <tr><td><strong>Total:</strong></td><td>${data.data.lms_service.total_counts.total}</td></tr>
                                    <tr><td><strong>Passed:</strong></td><td>${data.data.lms_service.total_counts.passed}</td></tr>
                                    <tr><td><strong>Failed:</strong></td><td>${data.data.lms_service.total_counts.failed}</td></tr>
                                    <tr><td><strong>Submitted:</strong></td><td>${data.data.lms_service.total_counts.submitted}</td></tr>
                                    <tr><td><strong>Processed:</strong></td><td>${data.data.lms_service.total_counts.processed}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Admin Service (CourseProgressService)</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><td><strong>Percentage:</strong></td><td>${data.data.admin_service.percentage.toFixed(2)}%</td></tr>
                                    <tr><td><strong>Total:</strong></td><td>${data.data.admin_service.total_counts.total}</td></tr>
                                    <tr><td><strong>Passed:</strong></td><td>${data.data.admin_service.total_counts.passed}</td></tr>
                                    <tr><td><strong>Failed:</strong></td><td>${data.data.admin_service.total_counts.failed}</td></tr>
                                    <tr><td><strong>Submitted:</strong></td><td>${data.data.admin_service.total_counts.submitted}</td></tr>
                                    <tr><td><strong>Processed:</strong></td><td>${data.data.admin_service.total_counts.processed}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
                }

                showResults(content);
                // feather.replace();
            }


        });
    </script>
@endsection
