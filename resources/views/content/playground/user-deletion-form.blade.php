@extends('layouts/contentLayoutMaster')

@section('title', 'User Data Deletion')

@section('vendor-style')
<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}">
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Playground /</span> User Data Deletion
</h4>

@if(session('success'))
<div class="alert alert-success alert-dismissible" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    User Data Deletion Form
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ⚠️ WARNING: This action is irreversible!
                    </h6>
                    <p class="mb-0">
                        This tool will permanently delete all student data for the specified user including:
                        <strong>enrolments, quiz attempts, progress data, activities, and reports</strong>.
                        Please ensure you have verified the user ID and username before proceeding.
                    </p>
                </div>

                <form action="{{ route('playground.user-deletion-preview') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="user_id" class="form-label required">User ID</label>
                                <input type="number"
                                       class="form-control @error('user_id') is-invalid @enderror"
                                       id="user_id"
                                       name="user_id"
                                       placeholder="Enter user ID"
                                       value="{{ old('user_id') }}"
                                       required>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label required">Username</label>
                                <input type="text"
                                       class="form-control @error('username') is-invalid @enderror"
                                       id="username"
                                       name="username"
                                       placeholder="Enter username"
                                       value="{{ old('username') }}"
                                       required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_warning" required>
                            <label class="form-check-label" for="confirm_warning">
                                I understand that this action will permanently delete all student data and cannot be undone.
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning" id="previewBtn" disabled>
                            <i class="fas fa-eye me-2"></i>
                            Preview Deletion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Tables -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent User Data Deletions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Deleted At</th>
                                <th>Records Deleted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    No deletion history available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirm_warning');
    const previewBtn = document.getElementById('previewBtn');

    confirmCheckbox.addEventListener('change', function() {
        previewBtn.disabled = !this.checked;
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!confirmCheckbox.checked) {
            e.preventDefault();
            alert('Please confirm that you understand the consequences of this action.');
            return false;
        }

        if (!confirm('Are you sure you want to preview the deletion for this user?')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection
