@extends('layouts/contentLayoutMaster')

@section('title', 'User Data Deletion Preview')

@section('vendor-style')
<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Playground / User Data Deletion /</span> Preview
</h4>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-times text-danger me-2"></i>
                    Deletion Preview for User: {{ $user->username }} (ID: {{ $user->id }})
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ⚠️ FINAL WARNING: This action will permanently delete data!
                    </h6>
                    <p class="mb-0">
                        You are about to permanently delete all student data for <strong>{{ $user->username }}</strong>.
                        This action cannot be undone. Please review the data below carefully before proceeding.
                    </p>
                </div>

                <!-- User Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold">User Details</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td>{{ $user->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Username:</strong></td>
                                <td>{{ $user->username }}</td>
                            </tr>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $user->email }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Data to be Deleted</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Table</th>
                                        <th>Records</th>
                                        <th>Impact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Student Course Enrolments</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $deletionStats['student_course_enrolments'] }}</span>
                                        </td>
                                        <td class="text-danger">All course progress lost</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Admin Reports</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $deletionStats['admin_reports'] }}</span>
                                        </td>
                                        <td class="text-danger">Reporting data removed</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Student Activities</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">{{ $deletionStats['student_activities'] }}</span>
                                        </td>
                                        <td class="text-danger">Activity history lost</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Quiz Attempts</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $deletionStats['quiz_attempts'] }}</span>
                                        </td>
                                        <td class="text-danger">Assessment results gone</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Student Course Stats</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-dark">{{ $deletionStats['student_course_stats'] }}</span>
                                        </td>
                                        <td class="text-danger">Progress statistics lost</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Evaluations</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-success">{{ $deletionStats['evaluations'] }}</span>
                                        </td>
                                        <td class="text-danger">Assessment feedback gone</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Feedback</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $deletionStats['feedback'] }}</span>
                                        </td>
                                        <td class="text-danger">User feedback removed</td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td><strong>TOTAL</strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger fs-6">
                                                {{ array_sum($deletionStats) }}
                                            </span>
                                        </td>
                                        <td><strong class="text-danger">ALL DATA LOST</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="fw-bold">Detailed Breakdown</h6>
                        <div class="accordion" id="deletionDetails">
                            @if($deletionStats['student_course_enrolments'] > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enrolments">
                                        Course Enrolments ({{ $deletionStats['student_course_enrolments'] }} records)
                                    </button>
                                </h2>
                                <div id="enrolments" class="accordion-collapse collapse" data-bs-parent="#deletionDetails">
                                    <div class="accordion-body">
                                        <p class="text-muted mb-3">This will remove all course enrollments, progress tracking, and completion status for this user.</p>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Course Name</th>
                                                        <th>Category</th>
                                                        <th>Status</th>
                                                        <th>Enrolled</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($courseEnrolments as $enrolment)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $enrolment['course_name'] }}</strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">{{ $enrolment['course_category'] }}</span>
                                                        </td>
                                                        <td>
                                                            @if($enrolment['status'] === 'ENROLLED')
                                                                <span class="badge bg-success">{{ $enrolment['status'] }}</span>
                                                            @elseif($enrolment['status'] === 'COMPLETED')
                                                                <span class="badge bg-primary">{{ $enrolment['status'] }}</span>
                                                            @elseif($enrolment['status'] === 'DELIST')
                                                                <span class="badge bg-danger">{{ $enrolment['status'] }}</span>
                                                            @else
                                                                <span class="badge bg-warning">{{ $enrolment['status'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $enrolment['enrolled_at'] ? $enrolment['enrolled_at']->format('M d, Y') : 'N/A' }}</td>
                                                        <td>{{ $enrolment['course_start_at'] ? \Carbon\Carbon::parse($enrolment['course_start_at'])->format('M d, Y') : 'N/A' }}</td>
                                                        <td>{{ $enrolment['course_ends_at'] ? \Carbon\Carbon::parse($enrolment['course_ends_at'])->format('M d, Y') : 'N/A' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($deletionStats['quiz_attempts'] > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#quizAttempts">
                                        Quiz Attempts ({{ $deletionStats['quiz_attempts'] }} records)
                                    </button>
                                </h2>
                                <div id="quizAttempts" class="accordion-collapse collapse" data-bs-parent="#deletionDetails">
                                    <div class="accordion-body">
                                        <p class="text-muted">All quiz submissions, answers, scores, and completion records will be permanently deleted.</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($deletionStats['student_activities'] > 0)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#activities">
                                        Student Activities ({{ $deletionStats['student_activities'] }} records)
                                    </button>
                                </h2>
                                <div id="activities" class="accordion-collapse collapse" data-bs-parent="#deletionDetails">
                                    <div class="accordion-body">
                                        <p class="text-muted">All activity logs including lesson starts, completions, and system interactions will be removed.</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('playground.user-deletion-form') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Go Back
                    </a>

                    <form action="{{ route('playground.user-deletion-execute') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <input type="hidden" name="username" value="{{ $user->username }}">

                        <div class="d-flex align-items-center">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" id="final_confirmation" name="confirmation" value="PROCEED" required>
                                <label class="form-check-label text-danger fw-bold" for="final_confirmation">
                                    I understand this action is irreversible and I want to proceed
                                </label>
                            </div>

                            <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="fas fa-trash me-2"></i>
                                Execute Deletion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const finalConfirmation = document.getElementById('final_confirmation');
    const deleteBtn = document.getElementById('deleteBtn');

    finalConfirmation.addEventListener('change', function() {
        deleteBtn.disabled = !this.checked;
    });

    // Final confirmation before deletion
    const deleteForm = document.querySelector('form[action*="execute"]');
    deleteForm.addEventListener('submit', function(e) {
        if (!finalConfirmation.checked) {
            e.preventDefault();
            alert('Please confirm that you understand this action is irreversible.');
            return false;
        }

        if (!confirm('⚠️ FINAL WARNING: You are about to permanently delete ALL data for user {{ $user->username }}. This action cannot be undone. Are you absolutely sure?')) {
            e.preventDefault();
            return false;
        }

        // Show loading state
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
        deleteBtn.disabled = true;
    });
});
</script>
@endsection
