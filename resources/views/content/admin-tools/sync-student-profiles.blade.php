@extends('layouts/contentLayoutMaster')

@section('title', 'Sync Student Profiles')

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
                <div class="card-header py-2">
                    <h4 class="card-title mb-1">
                        <i class="fas fa-sync-alt me-2"></i>
                        Sync Student Profiles
                    </h4>
                    <p class="card-text mb-0 small">
                        Sync student profiles by updating course progress, stats, expiry dates, and admin reports.
                        This tool processes the same logic as StudentSyncService::syncProfile() method.
                        <strong>Now supports batch processing for large numbers of user IDs!</strong>
                    </p>
                </div>
                <div class="card-body py-2">
                    <form id="syncStudentProfilesForm" class="row g-3">
                        @csrf
                        <div class="col-md-8">
                            <label for="user_ids" class="form-label">
                                <strong>User ID(s)</strong>
                            </label>
                            <textarea class="form-control form-control-lg" id="user_ids" name="user_ids" rows="3"
                                   placeholder="Enter user IDs (e.g., 123 or 123,456,789 or one per line)" required></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Enter single user ID, comma-separated multiple user IDs, or one per line. The system will automatically split large lists into batches for processing.
                                <br><small class="text-muted">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    If you get "User ID not found" errors, verify that the user IDs exist in the system and are active.
                                </small>
                            </div>
                            <div id="userCount" class="form-text text-muted" style="display: none;">
                                <i class="fas fa-calculator me-1"></i>
                                <span id="userCountText">0 user IDs entered</span>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex flex-column gap-3">
                            <div>
                                <label for="batch_size" class="form-label">
                                    <strong>Batch Size</strong>
                                </label>
                                <select class="form-select" id="batch_size" name="batch_size">
                                    <option value="5">5 users per batch</option>
                                    <option value="10" selected>10 users per batch</option>
                                    <option value="20">20 users per batch</option>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Choose how many users to process in each batch.
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success w-100" id="syncButton">
                                    <i class="fas fa-sync-alt me-2"></i>Process Batches
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Processing Section -->
    <div id="batchProcessingSection" class="row mt-2" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="fas fa-layer-group me-2"></i>
                                Batch Processing
                            </h5>
                            <p class="card-text mb-0 small">
                                Your user IDs have been split into batches for processing. Click "Process Batch" to start processing each batch. You can process batches in any order and review results before proceeding to the next batch.
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="cleanupModalBackdrop()" title="Fix stuck modal overlay">
                            <i class="fas fa-broom me-1"></i>Fix Overlay
                        </button>
                    </div>
                </div>
                <div class="card-body py-2" id="batchContainer">
                    <!-- Batches will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" aria-labelledby="progressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        Processing Batch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="progressContent">
                        <p class="text-center">Processing batch...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeProgressModal" style="display: none;">
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBatchButton" style="display: none;" onclick="processNextBatch()">
                        <i class="fas fa-arrow-right me-2"></i>Next Batch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div id="syncResults" class="row mt-2" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list-alt me-2"></i>
                        Sync Results
                    </h5>
                </div>
                <div class="card-body py-2" id="resultsContent">
                    <!-- Results will be populated here -->
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
            let batches = [];
            let currentBatchIndex = 0;
            let allResults = [];
            let allErrors = [];
            let batchStartTime = null;
            let progressModalInstance = null;

            // Real-time user count validation
            $('#user_ids').on('input', function() {
                const value = $(this).val().trim();
                const userCountDiv = $('#userCount');
                const userCountText = $('#userCountText');

                if (value) {
                    const userIds = parseUserIds(value);
                    const validIds = userIds.filter(id => id && !isNaN(id) && parseInt(id) > 0);
                    const count = validIds.length;

                    userCountDiv.show();
                    userCountText.text(`${count} user ID${count !== 1 ? 's' : ''} entered`);

                    if (count > 0) {
                        userCountText.removeClass('text-danger').addClass('text-success');
                    } else {
                        userCountText.removeClass('text-success text-danger').addClass('text-muted');
                    }
                } else {
                    userCountDiv.hide();
                }
            });

            // Parse user IDs from various input formats
            function parseUserIds(input) {
                // Split by comma, newline, or space
                return input.split(/[,\n\s]+/)
                    .map(id => id.trim())
                    .filter(id => id.length > 0);
            }

            // Create batches from user IDs
            function createBatches(userIds, batchSize) {
                const batches = [];
                for (let i = 0; i < userIds.length; i += batchSize) {
                    batches.push(userIds.slice(i, i + batchSize));
                }
                return batches;
            }

            // Display batches in UI
            function displayBatches(batches) {
                const batchContainer = $('#batchContainer');
                batchContainer.empty();

                batches.forEach((batch, index) => {
                    const batchHtml = `
                        <div class="row mb-2" id="batch-${index}">
                            <div class="col-md-8">
                                <div class="card border-primary">
                                    <div class="card-header bg-info text-white py-1">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-layer-group me-2"></i>
                                            Current Batch: ${index + 1}, Total Batches: ${batches.length} (${batch.length} user IDs)
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        <p class="mb-1"><strong>User IDs:</strong></p>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${batch.map(id => `<span class="badge bg-secondary">${id}</span>`).join('')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex flex-column gap-2">
                                <button type="button" class="btn btn-success"
                                        id="processBatch-${index}"
                                        onclick="processBatch(${index})">
                                    <i class="fas fa-play me-1"></i>Process Batch
                                </button>
                                <button type="button" class="btn btn-info"
                                        id="viewProgress-${index}"
                                        onclick="viewBatchProgress(${index})"
                                        style="display: none;">
                                    <i class="fas fa-eye me-1"></i>View Progress
                                </button>
                            </div>
                        </div>
                    `;
                    batchContainer.append(batchHtml);
                });

                $('#batchProcessingSection').show();
            }

            // Process a single batch
            window.processBatch = function(batchIndex) {
                const batch = batches[batchIndex];
                const button = $(`#processBatch-${batchIndex}`);
                const batchRow = $(`#batch-${batchIndex}`);

                // Check if this batch is already being processed or completed
                const existingResult = allResults.find(r => r.batchIndex === batchIndex);
                
                // Use single modal instance to prevent conflicts
                if (!progressModalInstance) {
                    progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
                }
                const progressModal = progressModalInstance;

                // If batch is already processed, just show the results
                if (existingResult) {
                    viewBatchResults(batchIndex);
                    return;
                }

                // If modal is already open for this batch, just toggle it
                if (progressModal._isShown) {
                    safeModalHide(progressModal);
                    // Clean up any stuck backdrops
                    setTimeout(() => cleanupModalBackdrop(), 100);
                    return;
                }

                // Start timer
                batchStartTime = new Date();

                // Disable button and show loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');

                // Show View Progress button
                $(`#viewProgress-${batchIndex}`).show();

                // Show progress modal with working progress bar
                $('#progressModalLabel').html(`<i class="fas fa-spinner fa-spin me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length}`);
                $('#progressContent').html(`
                    <div class="text-center">
                        <p><strong>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length}</strong></p>
                        <p>User IDs: ${batch.join(', ')}</p>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                Processing...
                            </div>
                        </div>
                        <p class="text-muted">Please wait while the batch is being processed...</p>
                        <p class="text-info"><i class="fas fa-clock me-1"></i>Started at: ${batchStartTime.toLocaleTimeString()}</p>
                    </div>
                `);
                $('#closeProgressModal').hide();
                $('#nextBatchButton').hide();
                progressModal.show();

                // Make AJAX request
                $.ajax({
                    url: '{{ route('admin-tools.sync-student-profiles') }}',
                    type: 'GET',
                    data: { user_ids: batch.join(',') },
                    success: function(response) {
                        // Calculate processing time
                        const batchEndTime = new Date();
                        const processingTime = Math.round((batchEndTime - batchStartTime) / 1000); // in seconds
                        const processingTimeFormatted = processingTime < 60 ?
                            `${processingTime}s` :
                            `${Math.floor(processingTime / 60)}m ${processingTime % 60}s`;

                        // Update button to show completion and make it toggleable
                        button.removeClass('btn-success').addClass('btn-info')
                              .prop('disabled', false)
                              .html('<i class="fas fa-eye me-1"></i>View Results')
                              .attr('onclick', `viewBatchResults(${batchIndex})`);

                        // Hide View Progress button
                        $(`#viewProgress-${batchIndex}`).hide();

                        // Store results with processing time
                        allResults.push({
                            batchIndex: batchIndex,
                            batch: batch,
                            response: response,
                            processingTime: processingTime,
                            processingTimeFormatted: processingTimeFormatted,
                            startTime: batchStartTime,
                            endTime: batchEndTime
                        });

                        // Update progress modal with detailed results
                        let modalContent = `
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Completed Successfully</h6>
                                <div class="row mt-3">
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-primary">${response.summary.total_users_processed}</h4>
                                        <p class="mb-0">Users Processed</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-info">${response.summary.total_enrolments_processed}</h4>
                                        <p class="mb-0">Enrolments Processed</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-warning">${response.summary.total_errors}</h4>
                                        <p class="mb-0">Errors</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h4 class="text-success">${processingTimeFormatted}</h4>
                                        <p class="mb-0">Processing Time</p>
                                    </div>
                                </div>
                                <div class="mt-2 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Started: ${batchStartTime.toLocaleTimeString()} |
                                        Completed: ${batchEndTime.toLocaleTimeString()}
                                    </small>
                                </div>
                            </div>
                        `;

                                                // Add user results if available
                        if (response.results && response.results.length > 0) {
                            modalContent += `
                                <div class="card mt-2">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-users me-2"></i>User Results</h6>
                                    </div>
                                    <div class="card-body py-2">
                            `;

                            response.results.forEach(function(result) {
                                const hasErrors = result.errors && result.errors.length > 0;
                                const canRetry = hasErrors || (result.enrolments_processed === 0 && !result.message);

                                modalContent += `
                                    <div class="border rounded p-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">${result.user_name} <span class="badge bg-secondary">ID: ${result.user_id}</span></h6>
                                `;

                                if (result.message) {
                                    modalContent += `<p class="text-warning mb-1"><i class="fas fa-exclamation-triangle me-1"></i>${result.message}</p>`;
                                } else {
                                    modalContent += `<p class="mb-1"><strong>Enrolments Processed:</strong> ${result.enrolments_processed}/${result.total_enrolments}</p>`;

                                    if (hasErrors) {
                                        modalContent += `
                                            <div class="mt-1">
                                                <small class="text-danger"><strong>Errors:</strong></small>
                                                <ul class="mb-0 mt-1">
                                        `;
                                        result.errors.forEach(function(error) {
                                            modalContent += `<li class="text-danger"><small>${error}</small></li>`;
                                        });
                                        modalContent += `</ul></div>`;
                                    }
                                }

                                modalContent += `</div>`;

                                // Status badge and retry button
                                modalContent += `<div class="d-flex flex-column align-items-end">`;

                                if (result.enrolments_processed > 0) {
                                    modalContent += `<span class="badge bg-success mb-1">Success</span>`;
                                } else if (result.message) {
                                    modalContent += `<span class="badge bg-warning mb-1">No Enrolments</span>`;
                                } else {
                                    modalContent += `<span class="badge bg-danger mb-1">Failed</span>`;
                                }

                                if (canRetry) {
                                    modalContent += `
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="retryUserSync(${result.user_id}, '${result.user_name}')">
                                            <i class="fas fa-redo me-1"></i>Retry
                                        </button>
                                    `;
                                }

                                modalContent += `</div></div></div>`;
                            });

                            modalContent += `</div></div>`;
                        }

                        // Add global errors if any
                        if (response.errors && response.errors.length > 0) {
                            modalContent += `
                                <div class="alert alert-danger mt-3">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Global Errors</h6>
                                    <ul class="mb-0">
                            `;
                            response.errors.forEach(function(error) {
                                modalContent += `<li>${error}</li>`;
                            });
                            modalContent += `</ul></div>`;
                        }

                        $('#progressContent').html(modalContent);
                        $('#progressModalLabel').html(`<i class="fas fa-check-circle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Results`);
                        $('#closeProgressModal').show();

                        // Update the modal content without hiding/showing to avoid DOM issues
                        // The modal is already open, so we just update the content

                        // Show "Next Batch" button if there are more batches to process
                        const nextBatchIndex = batchIndex + 1;
                        if (nextBatchIndex < batches.length) {
                            $('#nextBatchButton').show().attr('onclick', `processBatch(${nextBatchIndex})`);
                        } else {
                            $('#nextBatchButton').hide();
                        }

                        // Keep modal open for user to review results
                        // User can close manually when ready to proceed to next batch

                        toastr.success(`Batch ${batchIndex + 1} completed successfully!`);
                    },
                    error: function(xhr) {
                        // Update button to show error and make it toggleable
                        button.removeClass('btn-success').addClass('btn-danger')
                              .prop('disabled', false)
                              .html('<i class="fas fa-exclamation-triangle me-1"></i>View Error')
                              .attr('onclick', `viewBatchError(${batchIndex})`);

                        // Hide View Progress button
                        $(`#viewProgress-${batchIndex}`).hide();

                        // Store error
                        allErrors.push({
                            batchIndex: batchIndex,
                            batch: batch,
                            error: xhr.responseJSON || xhr.responseText
                        });

                        // Update progress modal
                        $('#progressContent').html(`
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Failed</h6>
                                <p class="mb-0">${xhr.responseJSON?.error || 'An error occurred during processing'}</p>
                            </div>
                        `);
                        $('#progressModalLabel').html(`<i class="fas fa-exclamation-triangle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Error`);
                        $('#closeProgressModal').show();
                        $('#nextBatchButton').hide();

                        // Keep modal open for user to review error details
                        // User can close manually when ready

                        toastr.error(`Batch ${batchIndex + 1} failed: ${xhr.responseJSON?.error || 'Unknown error'}`);
                    }
                });
            };

                        // View batch progress (toggle popup during processing)
            window.viewBatchProgress = function(batchIndex) {
                if (!progressModalInstance) {
                    progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
                }
                const progressModal = progressModalInstance;

                // If modal is already open, just toggle it
                if (progressModal._isShown) {
                    safeModalHide(progressModal);
                    return;
                }

                // Show the current progress modal (it should already be open during processing)
                progressModal.show();
            };

            // Function to clean up modal backdrop if it gets stuck
            window.cleanupModalBackdrop = function() {
                try {
                    // Remove any stuck modal backdrops
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css({
                        'padding-right': '',
                        'overflow': ''
                    });
                    // Ensure scrolling is restored
                    $('html').css('overflow', '');
                } catch (e) {
                    console.warn('Error cleaning up modal backdrop:', e);
                }
            };

            // Safe modal hide function
            window.safeModalHide = function(modalInstance) {
                try {
                    if (modalInstance && modalInstance._element && modalInstance._element.parentNode) {
                        modalInstance.hide();
                    } else {
                        // If modal instance is invalid, just clean up manually
                        cleanupModalBackdrop();
                    }
                } catch (e) {
                    console.warn('Error hiding modal:', e);
                    cleanupModalBackdrop();
                }
            };

            // Safe modal disposal function
            window.safeModalDispose = function(modalInstance) {
                try {
                    if (modalInstance && modalInstance._element && modalInstance._element.parentNode) {
                        modalInstance.dispose();
                    }
                    cleanupModalBackdrop();
                } catch (e) {
                    console.warn('Error disposing modal:', e);
                    cleanupModalBackdrop();
                }
            };

                        // View batch results
            window.viewBatchResults = function(batchIndex) {
                const batchResult = allResults.find(r => r.batchIndex === batchIndex);
                if (!batchResult) return;

                if (!progressModalInstance) {
                    progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
                }
                const progressModal = progressModalInstance;

                // If modal is already open, just toggle it
                if (progressModal._isShown) {
                    safeModalHide(progressModal);
                    // Clean up any stuck backdrops
                    setTimeout(() => cleanupModalBackdrop(), 100);
                    return;
                }

                const response = batchResult.response;
                const processingTime = batchResult.processingTimeFormatted || 'N/A';
                const startTime = batchResult.startTime ? batchResult.startTime.toLocaleTimeString() : 'N/A';
                const endTime = batchResult.endTime ? batchResult.endTime.toLocaleTimeString() : 'N/A';

                let resultsHtml = '';

                // Summary Card
                resultsHtml += '<div class="row mb-4">';
                resultsHtml += '<div class="col-12">';
                resultsHtml += '<div class="card border-success">';
                resultsHtml += '<div class="card-header bg-success text-white">';
                resultsHtml += '<h5 class="card-title mb-0"><i class="fas fa-check-circle me-2"></i>Current Batch: ' + (batchIndex + 1) + ', Total Batches: ' + batches.length + ' - Results</h5>';
                resultsHtml += '</div>';
                resultsHtml += '<div class="card-body">';
                resultsHtml += '<div class="row">';
                resultsHtml += '<div class="col-md-3 text-center">';
                resultsHtml += '<h3 class="text-primary">' + response.summary.total_users_processed + '</h3>';
                resultsHtml += '<p class="mb-0">Users Processed</p>';
                resultsHtml += '</div>';
                resultsHtml += '<div class="col-md-3 text-center">';
                resultsHtml += '<h3 class="text-info">' + response.summary.total_enrolments_processed + '</h3>';
                resultsHtml += '<p class="mb-0">Enrolments Processed</p>';
                resultsHtml += '</div>';
                resultsHtml += '<div class="col-md-3 text-center">';
                resultsHtml += '<h3 class="text-warning">' + response.summary.total_errors + '</h3>';
                resultsHtml += '<p class="mb-0">Errors</p>';
                resultsHtml += '</div>';
                resultsHtml += '<div class="col-md-3 text-center">';
                resultsHtml += '<h3 class="text-success">' + processingTime + '</h3>';
                resultsHtml += '<p class="mb-0">Processing Time</p>';
                resultsHtml += '</div>';
                resultsHtml += '</div>';
                resultsHtml += '<div class="mt-2 text-center">';
                resultsHtml += '<small class="text-muted"><i class="fas fa-clock me-1"></i>Started: ' + startTime + ' | Completed: ' + endTime + '</small>';
                resultsHtml += '</div>';
                resultsHtml += '</div>';
                resultsHtml += '</div>';
                resultsHtml += '</div>';
                resultsHtml += '</div>';

                // User Results
                if (response.results && response.results.length > 0) {
                    resultsHtml += '<div class="row">';
                    resultsHtml += '<div class="col-12">';
                    resultsHtml += '<div class="card">';
                    resultsHtml += '<div class="card-header py-2">';
                    resultsHtml += '<h6 class="card-title mb-0"><i class="fas fa-users me-2"></i>User Results</h6>';
                    resultsHtml += '</div>';
                    resultsHtml += '<div class="card-body py-2">';

                    response.results.forEach(function(result) {
                        const hasErrors = result.errors && result.errors.length > 0;
                        const canRetry = hasErrors || (result.enrolments_processed === 0 && !result.message);

                        resultsHtml += '<div class="border rounded p-2 mb-2">';
                        resultsHtml += '<div class="d-flex justify-content-between align-items-start">';
                        resultsHtml += '<div class="flex-grow-1">';
                        resultsHtml += '<h6 class="mb-1">' + result.user_name + ' <span class="badge bg-secondary">ID: ' + result.user_id + '</span></h6>';

                        if (result.message) {
                            resultsHtml += '<p class="text-warning mb-1"><i class="fas fa-exclamation-triangle me-1"></i>' + result.message + '</p>';
                        } else {
                            resultsHtml += '<p class="mb-1"><strong>Enrolments Processed:</strong> ' + result.enrolments_processed + '/' + result.total_enrolments + '</p>';

                            if (hasErrors) {
                                resultsHtml += '<div class="mt-1">';
                                resultsHtml += '<small class="text-danger"><strong>Errors:</strong></small>';
                                resultsHtml += '<ul class="mb-0 mt-1">';
                                result.errors.forEach(function(error) {
                                    resultsHtml += '<li class="text-danger"><small>' + error + '</small></li>';
                                });
                                resultsHtml += '</ul>';
                                resultsHtml += '</div>';
                            }
                        }
                        resultsHtml += '</div>';

                        // Status badge and retry button
                        resultsHtml += '<div class="d-flex flex-column align-items-end">';

                        if (result.enrolments_processed > 0) {
                            resultsHtml += '<span class="badge bg-success mb-1">Success</span>';
                        } else if (result.message) {
                            resultsHtml += '<span class="badge bg-warning mb-1">No Enrolments</span>';
                        } else {
                            resultsHtml += '<span class="badge bg-danger mb-1">Failed</span>';
                        }

                        if (canRetry) {
                            resultsHtml += '<button type="button" class="btn btn-outline-primary btn-sm" onclick="retryUserSync(' + result.user_id + ', \'' + result.user_name + '\')">';
                            resultsHtml += '<i class="fas fa-redo me-1"></i>Retry</button>';
                        }

                        resultsHtml += '</div></div></div>';
                    });

                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                }

                // Global errors
                if (response.errors && response.errors.length > 0) {
                    resultsHtml += '<div class="row mt-3">';
                    resultsHtml += '<div class="col-12">';
                    resultsHtml += '<div class="alert alert-danger">';
                    resultsHtml += '<h6><i class="fas fa-exclamation-triangle me-2"></i>Global Errors</h6>';
                    resultsHtml += '<ul class="mb-0">';
                    response.errors.forEach(function(error) {
                        resultsHtml += '<li>' + error + '</li>';
                    });
                    resultsHtml += '</ul>';
                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                    resultsHtml += '</div>';
                }

                // Show results in modal
                const resultsModal = new bootstrap.Modal(document.getElementById('progressModal'));
                $('#progressModalLabel').html('<i class="fas fa-list-alt me-2"></i>Current Batch: ' + (batchIndex + 1) + ', Total Batches: ' + batches.length + ' - Results');
                $('#progressContent').html(resultsHtml);
                $('#closeProgressModal').show();
                $('#nextBatchButton').hide();
                resultsModal.show();
            };

                        // View batch error
            window.viewBatchError = function(batchIndex) {
                const batchError = allErrors.find(r => r.batchIndex === batchIndex);
                if (!batchError) return;

                if (!progressModalInstance) {
                    progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
                }
                const progressModal = progressModalInstance;

                // If modal is already open, just toggle it
                if (progressModal._isShown) {
                    safeModalHide(progressModal);
                    // Clean up any stuck backdrops
                    setTimeout(() => cleanupModalBackdrop(), 100);
                    return;
                }

                const error = batchError.error;
                const batch = batchError.batch;

                let errorHtml = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Failed</h6>
                        <p class="mb-0">${error.error || 'An error occurred during processing'}</p>
                    </div>
                    <div class="card mt-2">
                        <div class="card-header py-2">
                            <h6 class="card-title mb-0"><i class="fas fa-layer-group me-2"></i>Failed Batch Details</h6>
                        </div>
                        <div class="card-body py-2">
                            <p class="mb-1"><strong>User IDs:</strong></p>
                            <div class="d-flex flex-wrap gap-1">
                                ${batch.map(id => `<span class="badge bg-secondary">${id}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                `;

                $('#progressContent').html(errorHtml);
                $('#progressModalLabel').html(`<i class="fas fa-exclamation-triangle me-2"></i>Current Batch: ${batchIndex + 1}, Total Batches: ${batches.length} - Error`);
                $('#closeProgressModal').show();
                $('#nextBatchButton').hide();
                progressModal.show();
            };

            // Function to process the next batch (called from Next Batch button)
            window.processNextBatch = function() {
                const nextBatchIndex = currentBatchIndex + 1;
                if (nextBatchIndex < batches.length) {
                    // Close current modal
                    if (progressModalInstance) {
                        safeModalHide(progressModalInstance);
                    }
                    // Process next batch
                    processBatch(nextBatchIndex);
                }
            };

            // Function to retry sync for a specific user
            window.retryUserSync = function(userId, userName) {
                if (confirm(`Retry sync for user "${userName}" (ID: ${userId})?`)) {
                    // Start timer
                    batchStartTime = new Date();

                    // Show progress modal with working progress bar
                    if (!progressModalInstance) {
                        progressModalInstance = new bootstrap.Modal(document.getElementById('progressModal'));
                    }
                    const progressModal = progressModalInstance;
                    $('#progressModalLabel').html(`<i class="fas fa-spinner fa-spin me-2"></i>Retrying Sync for User: ${userName}`);
                    $('#progressContent').html(`
                        <div class="text-center">
                            <p><strong>Retrying sync for user: ${userName} (ID: ${userId})</strong></p>
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                    Processing...
                                </div>
                            </div>
                            <p class="text-muted">Please wait while the user sync is being processed...</p>
                            <p class="text-info"><i class="fas fa-clock me-1"></i>Started at: ${batchStartTime.toLocaleTimeString()}</p>
                        </div>
                    `);
                    $('#closeProgressModal').hide();
                    $('#nextBatchButton').hide();
                    progressModal.show();

                    // Make AJAX request for single user
                    $.ajax({
                        url: '{{ route('admin-tools.sync-student-profiles') }}',
                        type: 'GET',
                        data: { user_ids: userId.toString() },
                        success: function(response) {
                            // Calculate processing time
                            const batchEndTime = new Date();
                            const processingTime = Math.round((batchEndTime - batchStartTime) / 1000);
                            const processingTimeFormatted = processingTime < 60 ?
                                `${processingTime}s` :
                                `${Math.floor(processingTime / 60)}m ${processingTime % 60}s`;

                            // Update modal with results
                            let modalContent = `
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle me-2"></i>User Sync Retry Completed Successfully</h6>
                                    <div class="row mt-2">
                                        <div class="col-md-3 text-center">
                                            <h4 class="text-primary">${response.summary.total_users_processed}</h4>
                                            <p class="mb-0">Users Processed</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h4 class="text-info">${response.summary.total_enrolments_processed}</h4>
                                            <p class="mb-0">Enrolments Processed</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h4 class="text-warning">${response.summary.total_errors}</h4>
                                            <p class="mb-0">Errors</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h4 class="text-success">${processingTimeFormatted}</h4>
                                            <p class="mb-0">Processing Time</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Started: ${batchStartTime.toLocaleTimeString()} |
                                            Completed: ${batchEndTime.toLocaleTimeString()}
                                        </small>
                                    </div>
                                </div>
                            `;

                            // Add user results
                            if (response.results && response.results.length > 0) {
                                modalContent += `
                                    <div class="card mt-2">
                                        <div class="card-header py-2">
                                            <h6 class="card-title mb-0"><i class="fas fa-user me-2"></i>User Result</h6>
                                        </div>
                                        <div class="card-body py-2">
                                `;

                                response.results.forEach(function(result) {
                                    modalContent += `
                                        <div class="border rounded p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">${result.user_name} <span class="badge bg-secondary">ID: ${result.user_id}</span></h6>
                                    `;

                                    if (result.message) {
                                        modalContent += `<p class="text-warning mb-1"><i class="fas fa-exclamation-triangle me-1"></i>${result.message}</p>`;
                                    } else {
                                        modalContent += `<p class="mb-1"><strong>Enrolments Processed:</strong> ${result.enrolments_processed}/${result.total_enrolments}</p>`;

                                        if (result.errors && result.errors.length > 0) {
                                            modalContent += `
                                                <div class="mt-1">
                                                    <small class="text-danger"><strong>Errors:</strong></small>
                                                    <ul class="mb-0 mt-1">
                                            `;
                                            result.errors.forEach(function(error) {
                                                modalContent += `<li class="text-danger"><small>${error}</small></li>`;
                                            });
                                            modalContent += `</ul></div>`;
                                        }
                                    }

                                    modalContent += `</div>`;

                                    // Status badge
                                    if (result.enrolments_processed > 0) {
                                        modalContent += `<span class="badge bg-success">Success</span>`;
                                    } else if (result.message) {
                                        modalContent += `<span class="badge bg-warning">No Enrolments</span>`;
                                    } else {
                                        modalContent += `<span class="badge bg-danger">Failed</span>`;
                                    }

                                    modalContent += `</div></div></div>`;
                                });

                                modalContent += `</div></div>`;
                            }

                            $('#progressContent').html(modalContent);
                            $('#progressModalLabel').html(`<i class="fas fa-check-circle me-2"></i>User Sync Retry Results`);
                            $('#closeProgressModal').show();

                            toastr.success(`User "${userName}" sync retry completed successfully!`);
                        },
                        error: function(xhr) {
                            $('#progressContent').html(`
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>User Sync Retry Failed</h6>
                                    <p class="mb-0">${xhr.responseJSON?.error || 'An error occurred during retry'}</p>
                                </div>
                            `);
                            $('#progressModalLabel').html(`<i class="fas fa-exclamation-triangle me-2"></i>User Sync Retry Error`);
                            $('#closeProgressModal').show();

                            toastr.error(`User "${userName}" sync retry failed: ${xhr.responseJSON?.error || 'Unknown error'}`);
                        }
                    });
                }
            };

            // Handle Sync Student Profiles Form
            $('#syncStudentProfilesForm').on('submit', function(e) {
                e.preventDefault();

                const userIdsInput = $('#user_ids').val().trim();
                const syncButton = $('#syncButton');

                if (!userIdsInput) {
                    toastr.error('Please enter at least one user ID');
                    return;
                }

                // Parse user IDs
                const userIds = parseUserIds(userIdsInput);
                const validIds = userIds.filter(id => id && !isNaN(id) && parseInt(id) > 0);

                if (validIds.length === 0) {
                    toastr.error('Please enter valid user IDs (positive numbers only)');
                    return;
                }

                // Get selected batch size
                const batchSize = parseInt($('#batch_size').val()) || 10;

                // Create batches
                batches = createBatches(validIds, batchSize);
                allResults = [];
                allErrors = [];
                currentBatchIndex = 0;

                // Disable button and show loading state
                syncButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Preparing Batches...');

                // Display batches
                displayBatches(batches);

                // Re-enable button
                syncButton.prop('disabled', false).html('<i class="fas fa-sync-alt me-2"></i>Process Batches');

                toastr.success(`Created ${batches.length} batch(es) for ${validIds.length} user IDs (${batchSize} users per batch)`);
            });

            // Auto-focus on input when page loads
            $('#user_ids').focus();

            // Add event listeners to properly clean up modal when closed
            $('#progressModal').on('hidden.bs.modal', function () {
                // Clean up any stuck backdrops
                cleanupModalBackdrop();
            });

            // Add event listener for modal show to ensure proper state
            $('#progressModal').on('show.bs.modal', function () {
                // Ensure modal is properly initialized
                $(this).removeClass('fade');
            });

            // Add event listener for modal hide to prevent DOM issues
            $('#progressModal').on('hide.bs.modal', function (e) {
                // Prevent default hide behavior if modal is being disposed
                if (progressModalInstance && progressModalInstance._isBeingDestroyed) {
                    e.preventDefault();
                    return false;
                }
            });



            // Add a global click handler to clean up stuck backdrops
            $(document).on('click', function(e) {
                // If clicking outside modal and backdrop is stuck, clean it up
                if ($('.modal-backdrop').length > 0 && !$(e.target).closest('.modal').length) {
                    setTimeout(() => {
                        if ($('.modal-backdrop').length > 0 && !$('.modal.show').length) {
                            cleanupModalBackdrop();
                        }
                    }, 100);
                }
            });

            // Clean up modal on page unload
            $(window).on('beforeunload', function() {
                if (progressModalInstance) {
                    safeModalDispose(progressModalInstance);
                }
            });
        });
    </script>
@endsection
