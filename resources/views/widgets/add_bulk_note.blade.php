<!-- XLSX Library for Excel file support -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="card blockUI">
    <div class="card-header">
        <h2 class='fw-bolder text-primary mx-auto'>Add Bulk {{ \Str::title( $config[ 'subject_type' ] ) }} Note</h2>
    </div>
    <div class="card-body">
        <div class='row mb-2'>
            <div class="col-12">
                <label for='csv_file' class='form-label'>Upload a CSV or XLSX file with a list of student IDs in the
                    first
                    column</label>
                <div class='d-flex'>
                    <input type='file' name='csv_file' id='csv_file' class='form-control' size="10" accept='.csv,.xlsx'
                        onchange="processFile(this)" />
                    <button type='button' class='btn btn-outline-secondary ms-1' onclick="clearFileInput()">
                        <i data-lucide="x"></i>
                    </button>
                </div>
                <div class="form-text">Only CSV and XLSX files are allowed</div>
            </div>

            <div class='col-12 mt-2'>
                <div class='mb-1' id='add_bulk_note_input'>
                    <label for='{{ $config[ 'input_id' ] }}' class='form-label'>Note</label>
                    <textarea name='note_body[]' id='{{ $config[ 'input_id' ] }}_bulk' class='form-control content-tinymce' tabindex='0'
                        autofocus></textarea>
                </div>
            </div>

            <div class='col-12'>
                <div class='d-flex flex-row justify-content-right align-items-center'>
                    {{-- subject_type, editor, student_list --}}
                    <button type="submit" class="btn btn-primary me-1 waves-effect waves-float waves-light"
                        onclick="saveBulkNotes('{{ $config[ 'subject_type' ] }}', '{{ $config[ 'input_id' ] }}_bulk', extractedStudentIds)">
                        Apply Bulk Notes
                    </button>

                    <button type="button" class="btn btn-outline-secondary me-1 waves-effect waves-float waves-light"
                        onclick="clearTextarea()">
                        Clear Note
                    </button>

                    <!-- Validation spinner -->
                    <div class='align-items-center' id='validation_spinner' style="display: none;">
                        <div class='d-flex align-items-center'>
                            <div class='spinner-border spinner-border-sm text-primary me-2' role='status'>
                                <span class='visually-hidden'>Loading...</span>
                            </div>
                            <span class='text-muted'>Validating user IDs...</span>
                        </div>
                    </div>
                    <div class='align-items-center' id='csv_preview' style="display: none;">
                        <div id='extracted_numbers'></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    let extractedStudentIds = [];
    let errorNumbers = [];

    // Helper functions for spinner
    function showValidationSpinner() {
        document.getElementById('validation_spinner').style.display = 'block';
    }

    function hideValidationSpinner() {
        document.getElementById('validation_spinner').style.display = 'none';
    }

    // Initialize TinyMCE when the page loads (replaces CKEditor for spell checking support)
    document.addEventListener('DOMContentLoaded', function () {
        const textareaId = '{{ $config[ 'input_id' ] }}_bulk';

        // Wait for TinyMCE to be loaded before initializing
        function initializeTinyMCE() {
            if (typeof tinymce === 'undefined') {
                // TinyMCE not loaded yet, wait a bit and try again
                setTimeout(initializeTinyMCE, 100);
                return;
            }

            // Initialize TinyMCE for bulk notes
            if (typeof initTinyMCEById === 'function') {
                initTinyMCEById(textareaId, {
                    plugins: 'lists wordcount link code',
                    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                    height: 200,
                    menubar: false,
                    branding: false,
                    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                    browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
                });
            } else {
                // Fallback if initTinyMCEById is not available
                tinymce.init({
                    selector: '#' + textareaId,
                    plugins: 'lists wordcount link code',
                    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat',
                    height: 200,
                    menubar: false,
                    branding: false,
                    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4;',
                    browser_spellcheck: true, // Enable browser's native spell checking (works with Grammarly)
                });
            }
        }

        // Start initialization
        initializeTinyMCE();

        // Clear file input on page load to prevent showing previous file name
        const fileInput = document.getElementById('csv_file');
        if (fileInput) {
            fileInput.value = '';
        }
    });

    function clearFileInput() {
        const fileInput = document.getElementById('csv_file');
        fileInput.value = '';
        // Also clear the preview
        document.getElementById('csv_preview').style.display = 'none';
        extractedStudentIds = [];
        errorNumbers = [];
        hideValidationSpinner();
    }

    // Function to clear the textarea/editor
    function clearTextarea() {
        const textareaId = '{{ $config[ 'input_id' ] }}_bulk';
        const editor = tinymce.get(textareaId);
        if (editor) {
            // Clear TinyMCE instance
            editor.setContent('');
        } else {
            // Clear regular textarea
            const textarea = document.getElementById(textareaId);
            if (textarea) {
                textarea.value = '';
            }
        }
    }

    function processFile(input) {
        const file = input.files[0];
        if (!file) return;

        const fileExtension = file.name.split('.').pop().toLowerCase();

        if (fileExtension === 'csv') {
            processCSV(file);
        } else if (fileExtension === 'xlsx') {
            processXLSX(file);
        } else {
            toastr['error']('Please upload a CSV or XLSX file', 'Invalid File Type', {
                closeButton: true,
                tapToDismiss: true,
            });
        }
    }

    function processCSV(file) {
        const reader = new FileReader();
        reader.onload = async function (e) {
            const csv = e.target.result;
            const lines = csv.split('\n');
            const allNumbers = [];

            // First, collect all numbers from the CSV
            lines.forEach((line, index) => {
                if (line.trim()) {
                    // Split by comma and get first column
                    const columns = line.split(',');
                    if (columns.length > 0) {
                        const firstColumn = columns[0].trim();

                        // Extract numbers using regex
                        const numberMatches = firstColumn.match(/\d+/g);
                        if (numberMatches) {
                            numberMatches.forEach(num => {
                                allNumbers.push(parseInt(num));
                            });
                        }
                    }
                }
            });

            // Process the extracted numbers
            await processExtractedNumbers(allNumbers);
        };

        reader.readAsText(file);
    }

    function processXLSX(file) {
        const reader = new FileReader();
        reader.onload = async function (e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: 'array'
                });

                // Get the first worksheet
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];

                // Convert to JSON, but only get the first column
                const jsonData = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1, // Use array format
                    range: 0 // Start from first row
                });

                const allNumbers = [];

                // Extract numbers from first column only
                jsonData.forEach((row, index) => {
                    if (row && row.length > 0 && row[0] !== undefined) {
                        const firstColumn = String(row[0]).trim();

                        // Extract numbers using regex
                        const numberMatches = firstColumn.match(/\d+/g);
                        if (numberMatches) {
                            numberMatches.forEach(num => {
                                allNumbers.push(parseInt(num));
                            });
                        }
                    }
                });

                // Process the extracted numbers
                await processExtractedNumbers(allNumbers);

            } catch (error) {
                console.error('Error reading XLSX file:', error);
                toastr['error']('Error reading XLSX file. Please make sure it\'s a valid Excel file.',
                    'File Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
            }
        };

        reader.readAsArrayBuffer(file);
    }

    async function processExtractedNumbers(allNumbers) {
        // Remove duplicates
        const uniqueNumbers = [...new Set(allNumbers)];

        // Show spinner during validation
        showValidationSpinner();

        // Validate all numbers using bulk validation
        try {
            const response = await fetch('/api/v1/validate-users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    user_ids: uniqueNumbers
                })
            });

            if (response.ok) {
                const data = await response.json();
                const validNumbers = [];
                const invalidNumbers = [];

                // Process results
                Object.entries(data.results).forEach(([userId, exists]) => {
                    if (exists) {
                        validNumbers.push(parseInt(userId));
                    } else {
                        invalidNumbers.push(parseInt(userId));
                    }
                });

                extractedStudentIds = validNumbers;
                errorNumbers = invalidNumbers;

                // Display results
                displayNumbers(validNumbers, invalidNumbers);
            } else {
                throw new Error('Validation request failed');
            }
        } catch (error) {
            console.error('Error validating user IDs:', error);
            toastr['error']('Error validating user IDs. Please try again.',
                'Validation Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } finally {
            // Always hide spinner when validation is complete
            hideValidationSpinner();
        }
    }

    function displayNumbers(validNumbers, invalidNumbers = []) {
        const previewDiv = document.getElementById('csv_preview');
        const numbersDiv = document.getElementById('extracted_numbers');
        const submitButton = document.querySelector('button[type="submit"]');

        if (validNumbers.length > 0 || invalidNumbers.length > 0) {
            let displayContent = '';

            // Show valid numbers count
            if (validNumbers.length > 0) {
                displayContent += `<div>
                    <span class="text-success"><i class="fas fa-check-circle"></i> Valid User IDs: ${validNumbers.length}</span>
                </div>`;
            }

            // Show invalid numbers if any
            if (invalidNumbers.length > 0) {
                displayContent += `<div>
                    <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Invalid User IDs: ${invalidNumbers.length}</span>
                    <div>
                        <small>Invalid IDs: ${invalidNumbers.join(', ')}</small>
                    </div>
                </div>`;
            }

            numbersDiv.innerHTML = displayContent;
            previewDiv.style.display = 'block';

            // Enable the submit button only if there are valid numbers
            if (validNumbers.length > 0) {
                submitButton.disabled = false;
                submitButton.removeAttribute('disabled');
            } else {
                submitButton.disabled = true;
                submitButton.setAttribute('disabled', 'disabled');
            }
        } else {
            numbersDiv.innerHTML = '<span class="text-warning">No numbers found in the first column</span>';
            previewDiv.style.display = 'block';
            submitButton.disabled = true;
            submitButton.setAttribute('disabled', 'disabled');
        }
    }


    function saveBulkNotes(subject_type, editorId, student_list) {
        if (student_list.length === 0) {
            toastr['warning']('No student IDs found in the CSV file', 'Warning', {
                closeButton: true,
                tapToDismiss: true,
            });
            return;
        }

        // Get note content from TinyMCE editor
        let note = '';
        const editor = tinymce.get(editorId);
        if (editor) {
            // TinyMCE instance
            note = editor.getContent();
        } else {
            // Fallback: try to get the textarea directly
            const textarea = document.getElementById(editorId);
            note = textarea ? textarea.value : '';
        }

        if (note && note.trim().length > 0) {
            // Additional safeguard: Filter out any invalid IDs that might have slipped through
            const validStudentList = student_list.filter(student_id => {
                // Only include IDs that are not in the errorNumbers array
                return !errorNumbers.includes(student_id);
            });

            if (validStudentList.length === 0) {
                toastr['warning']('No valid student IDs to process. Please check your CSV file.', 'Warning', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                return;
            }

            // Prepare bulk notes data using only valid IDs
            const bulkNotesData = validStudentList.map(student_id => ({
                note_body: note,
                subject_type: subject_type,
                subject_id: student_id,
            }));

            // Use the new bulk endpoint
            axios.post('/api/v1/notes/bulk', {
                notes: bulkNotesData
            })
                .then((response) => {
                    const res = response.data;
                    const totalCreated = res.data.total_created;
                    const errors = res.data.errors || [];

                    // Show success message
                    const totalProcessed = validStudentList.length;
                    const invalidCount = student_list.length - validStudentList.length;
                    let successMessage = res.message || `${totalCreated} notes added successfully.`;

                    if (invalidCount > 0) {
                        successMessage += ` (${invalidCount} invalid IDs were skipped)`;
                    }

                    toastr['success'](successMessage, 'Success', {
                        closeButton: true,
                        tapToDismiss: true,
                    });

                    // Show warnings for any errors
                    if (errors.length > 0) {
                        errors.forEach(error => {
                            toastr['warning'](error, 'Warning', {
                                closeButton: true,
                                tapToDismiss: true,
                            });
                        });
                    }


                    // Refresh notes for all students if Tabs object is available
                    if (typeof Tabs !== 'undefined' && Tabs.showNotes) {
                        student_list.forEach(student_id => {
                            Tabs.showNotes(subject_type, student_id, true);
                        });
                        Tabs.cancelNoteEditing();
                    }

                    // Clear both file input and textarea/editor
                    clearFileInput();
                    clearTextarea();
                })
                .catch((error) => {
                    console.log(error);
                    let errorMessage = 'Error adding bulk notes';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMessage = error.response.data.message;
                    }
                    toastr['error'](errorMessage, 'Error', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                });
        } else {
            // Show error message
            toastr['warning']('Please enter a bulk note', 'Warning', {
                closeButton: true,
                tapToDismiss: true,
            });
        }
    }
</script>
