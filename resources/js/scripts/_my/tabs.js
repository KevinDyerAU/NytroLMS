var Tabs = (function (Tabs) {
    const notesTab = '.content-notes';
    const workPlacementsTab = '.content-work-placements';

    Tabs.populate = (resource, tab) => {
        resource.tab();
    };
    Tabs.gotoRoute = route => {
        window.location = route;
    };

    Tabs.showNotes = (subject_type, subject_id, reload = false) => {
        const subjectSpinner = $(
            '#' + subject_type + '-notes > .spinner-border'
        );
        const subjectTab = $('#' + subject_type + '-notes-tab');

        subjectSpinner.show();

        if (subjectTab.hasClass('loaded') && !reload) {
            subjectSpinner.hide();
            return false;
        }
        axios
            .get('/api/v1/notes/all/' + subject_type + '/' + subject_id)
            .then(response => {
                const res = response.data;
                const notes = res.data;
                let $output = '';
                // console.log(res, notes);
                subjectSpinner.hide();
                if (res.status === 'success' && Object.keys(notes).length > 0) {
                    $output += `<div class='row d-print-block'>
                        <div class='col-12 mx-auto'>
                            <div class='card'>
                                <div class='card-header'>
                                    <h2 class='fw-bolder text-primary mx-auto'>Note History</h2>
                                </div>
                                <div class='card-body'>`;
                    $output += notes.html;

                    $output += `</div>
                                            </div>
                                        </div>
                                    </div>`;
                    subjectTab.find(notesTab).html($output);
                } else if (res.status === 'success') {
                    subjectTab.find(notesTab).html(`
                    <div class='row'>
                        <div class='col-12 mx-auto'>
                            <div class='card'>
                                <div class='card-header'>
                                    <h2 class='fw-bolder text-primary mx-auto'>Note History</h2>
                                </div>
                                <div class='card-body'><p> No notes added yet. </p></div>
                            </div>
                        </div>
                    </div>`);
                }
            })
            .catch(error => {
                subjectSpinner.hide();
                console.log(error);
            });
    };
    Tabs.deleteNote = (event, id) => {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-danger ms-1',
            },
            buttonsStyling: false,
        })
            .then(function (result) {
                // console.log(result);

                if (result.isConfirmed) {
                    axios
                        .delete('/api/v1/notes/' + id)
                        .then(response => {
                            let res = response.data;
                            // console.log(res);

                            toastr.success(res.message, 'Success!', {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            });
                            $(event.target)
                                .parents('li.list-group-item')
                                .remove();
                            // setTimeout(() => {
                            //     window.location.reload();
                            // }, 1000);
                        })
                        .catch(error => {
                            // console.log(error);
                            const response = error.response.data;
                            toastr.error(response.errors[0].message, 'Error!', {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            });
                        });
                }
            })
            .catch(error => {
                // Handle any errors that occurred during the deletion process
                console.error('Error deleting note:', error);
                toastr.error('Error deleting note.', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                    timeOut: 2000,
                });
            });
    };
    Tabs.editNote = (event, id) => {
        const editorId = $('#note_input_wrapper').find('textarea.content-tinymce').attr('id') || 'note_body2';
        const editor = tinymce.get(editorId);
        
        if (!editor) {
            toastr['warning']('Editor not initialized. Please refresh the page.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
            return;
        }
        
        const existingDataWrapper = $(event.target).parents(
            'li.list-group-item'
        );
        const existingData = existingDataWrapper.find('.note-content').html();

        $('#note_input_wrapper').find('.divider-text').text('Update Note');
        $('#note_input_wrapper').find('button[type="submit"]').text('Update');

        editor.setContent(existingData);
        if ($('#note_id').length > 0) {
            $('#note_id').val(id);
        } else {
            $(
                '<input type="hidden" id="note_id" name="note_id" value="' +
                    id +
                    '" />'
            ).insertAfter($('#note_input_wrapper').find('#add_note_input'));
        }
        // console.log($("#cancelNote").length);
        if ($('#cancelNote').length < 1) {
            $(
                '<button id="cancelNote" class="btn btn-secondary me-1 waves-effect waves-float waves-light" onclick="Tabs.cancelNoteEditing()">Cancel</button>'
            ).insertAfter($('#note_input_wrapper').find('button[type="submit"]'));
        }
        $('#note_input_wrapper').find('#add_note_input').focus();
    };
    Tabs.cancelNoteEditing = () => {
        const editorId = $('#note_input_wrapper').find('textarea.content-tinymce').attr('id') || 'note_body2';
        const editor = tinymce.get(editorId);
        $('#note_input_wrapper').find('.divider-text').text('Add Note');
        if (editor) {
            editor.setContent('');
        }
        $('#note_input_wrapper').find('[type="submit"]').text('Save');
        $('#note_input_wrapper').find('#cancelNote').remove();
        $('#note_input_wrapper').find('#note_id').remove();

        $('#note_input_wrapper').find('#add_note_input').focus();
    };
    Tabs.saveNote = (subject_type, subject_id, editorId) => {
        const editor = tinymce.get(editorId);
        if (!editor) {
            toastr['warning']('Editor not initialized. Please refresh the page.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
            return;
        }

        let note = editor.getContent();
        if (note.length > 0) {
            axios
                .post('/api/v1/notes', {
                    id: $('#note_input_wrapper').find('#note_id').val() || null,
                    note_body: note,
                    subject_type: subject_type,
                    subject_id: subject_id,
                })
                .then(response => {
                    const res = response.data;
                    toastr['success'](
                        res.message ? res.message : 'Note added successfully.',
                        'Success',
                        {
                            closeButton: true,
                            tapToDismiss: true,
                        }
                    );
                    editor.setContent('');
                    Tabs.showNotes(subject_type, subject_id, true);
                    Tabs.cancelNoteEditing();
                    // console.log(response, res, res.message);
                })
                .catch(error => {
                    console.log(error);
                });
        } else {
            toastr['warning']('Note input is required.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        }
    };

    Tabs.togglePinNote = (event, id, subject_type) => {
        const $button = $(event.target);
        const isPinned = $button.text().trim() === 'UnPin'; //unpin text when already pinned
        const actionText = isPinned ? 'Unpin' : 'Pin';
        const actionMessage = isPinned
            ? 'This note will no longer appear at the top.'
            : 'This note will appear at the top of the list.';
        const confirmText = isPinned ? 'Yes, unpin it!' : 'Yes, pin it!';

        Swal.fire({
            title: `${actionText} this note?`,
            text: actionMessage,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: confirmText,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-secondary ms-1',
            },
            buttonsStyling: false,
        }).then(function (result) {
            if (result.isConfirmed) {
                axios
                    .post('/api/v1/notes/' + id + '/pin', {
                        is_pinned: !isPinned,
                    })
                    .then(response => {
                        let res = response.data;
                        toastr.success(res.message, 'Success!', {
                            closeButton: true,
                            tapToDismiss: true,
                            timeOut: 2000,
                        });
                        Tabs.showNotes(subject_type, res.data.subject_id, true);
                        // Check if no pinned notes remain or added first, and reload student profile if subject_type is student
                        // console.log(subject_type, res.data.pinned_count, isPinned);
                        // if (subject_type === 'student' && ((isPinned  && res.data.pinned_count === 0) || (!isPinned && res.data.pinned_count === 1))) {
                        //     window.location.reload();
                        // }else{
                        $('#pinned-notes-alert')
                            .find('span')
                            .text('Important notes');
                        // }
                    })
                    .catch(error => {
                        const response = error.response.data.errors[0];
                        toastr.error(
                            response.message ||
                                `Error ${actionText.toLowerCase()}ning note.`,
                            'Error!',
                            {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            }
                        );
                    });
            }
        });
    };

    return Tabs;
})(Tabs || {});

var Student = (function (Student) {
    const enrolmentTab = $('#student-enrolment-tab');
    const documentsTab = $('#student-documents-tab > .content-documents');
    const assessmentsTab = $('#student-assessments-tab');
    const activitiesTab = $('#student-activities-tab');
    const historyTab = $('#student-history-tab');
    const trainingPlanTab = $('#student-training-plan-tab');
    const steps = {
        'step-1': {
            title: 'Personal Info',
            subtitle: 'Step #1',
            slug: 'step-1',
        },
        'step-2': {
            title: 'Education Details',
            subtitle: 'Step #2',
            slug: 'step-2',
        },
        'step-3': {
            title: 'Employer Details',
            subtitle: 'Step #3',
            slug: 'step-3',
        },
        'step-4': {
            title: 'Requirements',
            subtitle: 'Step #4',
            slug: 'step-4',
        },
        'step-5': {
            title: 'Pre-Training Review',
            subtitle: 'Step #5',
            slug: 'step-5',
        },
        'step-6': { title: 'Agreement', subtitle: 'Step #6', slug: 'step-6' },
    };

    Student.appendToURL = param => {
        // const url = new URL(window.location);
        // if (url.hash != param) {
        //     url.hash = 'Student.'+param;
        //     window.location.href = url;
        // }
    };
    Student.overview = student_id => {
        console.log('Student student_id: ' + student_id);
        // axios.get('/api/v1/companies/'+data.student_id)
    };
    Student.enrolment = student_id => {
        $('#student-enrolment > .spinner-border').show();
        if (enrolmentTab.hasClass('loaded')) {
            $('#student-enrolment > .spinner-border').hide();
            return false;
        }
        axios
            .get('/api/v1/student/enrolment/' + student_id + '/onboard')
            .then(response => {
                const res = response.data;
                const onboard = res.data;
                if (
                    res.success &&
                    typeof onboard.enrolment_value !== 'undefined'
                ) {
                    $('#student-enrolment > .spinner-border').hide();
                    let output =
                        "<div class=' d-print-block'><div class='row d-flex enrolment-grid'>";
                    $.each(onboard.enrolment_value, function (step, record) {
                        // console.log(step, typeof record, record.length, record);
                        // Skip step-5 from being displayed in the enrolment tab
                        if (step === 'step-5') {
                            return true; // Continue to next iteration
                        }
                        if (record.length !== 0) {
                            output += `<div class='col-md-4 col-12' id='${step}'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary'>${steps[step]['title']}</h2>
                        </div>
                        <div class='card-body'>
                            <div class='clearfix divider divider-secondary divider-start-center '>
                                <span class='divider-text text-dark'> ${steps[step]['subtitle']}</span>
                            </div>`;
                            $.each(record, function (idx, item) {
                                let index = item.key,
                                    val = item.value;
                                if (
                                    index != 'document1_type' &&
                                    index != 'document1' &&
                                    index != 'document2_type' &&
                                    index != 'document2'
                                ) {
                                    if (
                                        step === 'step-1' &&
                                        val != null &&
                                        typeof val != 'undefined'
                                    ) {
                                        output += `<div class='row mb-2'>
                                    <span class='fw-bolder me-25 col col-sm-5 text-end'>${index.toProperCase()}</span>`;

                                        output += `<span class=\'col col-sm-5\'>${val}</span>`;
                                        output += '</div>';
                                    } else if (step !== 'step-1') {
                                        output += `<div class='row mb-2'>
                                    <span class='fw-bolder me-25 col col-sm-5 text-end'>${
                                        index == 'usi_number'
                                            ? 'USI Number'
                                            : index == 'nominate_usi'
                                            ? 'Nominate USI'
                                            : index.toProperCase()
                                    }</span>`;

                                        output += `<span class=\'col col-sm-5\'>${val}</span>`;
                                        output += '</div>';
                                    }
                                }
                            });
                            output += `</div>
                    </div>
                </div>`;
                        }
                    });

                    output += '</div></div>';
                    enrolmentTab.addClass('loaded').html(output);
                }
            })
            .catch(error => {
                $('#student-enrolment > .spinner-border').hide();
                console.log(error);
                if (error.response && error.response.status === 404) {
                    enrolmentTab.html(` <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary 'mx-auto>Enrolment missing</h2>
                        </div>
                        <div class='card-body'><p> Student has not completed enrolment yet. </p></div>
                    </div>
                </div>`);
                }
            });
    };
    Student.showDocuments = (student_id, reload = false, canDelete = false) => {
        $('#student-documents > .spinner-border').show();
        if (documentsTab.hasClass('loaded') && !reload) {
            $('#student-documents > .spinner-border').hide();
            return false;
        }
        axios
            .get('/api/v1/documents/all/' + student_id)
            .then(response => {
                const res = response.data;
                const documents = res.data;
                // console.log(res, documents);
                $('#student-documents > .spinner-border').hide();
                if (
                    res.status === 'success' &&
                    Object.keys(documents).length > 0
                ) {
                    let output =
                        "<div class=' d-print-block'><div class='row d-flex enrolment-grid'>";
                    output += `<div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Documents</h2>
                        </div>
                        <div class='card-body'>`;
                    $.each(documents, function (index, val) {
                        output += `<div class='row mb-2 document'>
                            <span class='fw-bolder m-25 col-sm-4 text-end'>${val.file_name.toProperCase()}</span>`;

                        output += `<span class='col-sm-6'>`;
                        output += `<span class="col-2"> <a href="/api/v1/documents/${val.id}" download='${val.file_name}' class='item-download me-1 btn btn-secondary btn-sm d-print-none' title='Download ${val.file_name}'>
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download">
  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
  <polyline points="7 10 12 15 17 10"/>
  <line x1="12" y1="15" x2="12" y2="3"/>
</svg>
                            Download</a></span>`;

                        output += `<span class="col-2"> <a href="${val.file_path}" target="_blank" class='item-view me-1 btn btn-primary btn-sm d-print-none' title='View ${val.file_name}' >
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
  <circle cx="12" cy="12" r="3"/>
</svg>
                            View</a></span>`;

                        if (
                            canDelete &&
                            !(
                                val.file_path &&
                                val.file_path.includes('/agreement/')
                            )
                        ) {
                            output += `<span class="col-2"> <a class='item-delete me-1 btn btn-danger btn-sm d-print-none' title='Remove' onclick='Student.deleteDocument(event, ${val.user_id}, ${val.id})'>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-square"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg> Remove</a></span>`;
                        }
                        output += `</span>`;
                        output += '</div>';
                    });
                    output += `</div>
                    </div>
                    </div>
                </div>`;

                    output += '</div>';
                    documentsTab.addClass('loaded').html(output);
                } else if (res.status === 'success') {
                    documentsTab.html(` <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Documents not found</h2>
                        </div>
                        <div class='card-body'><p> No documents uploaded yet. </p></div>
                    </div>
                </div>`);
                }
            })
            .catch(error => {
                $('#student-documents > .spinner-border').hide();
                console.log(error);
            });
    };
    Student.deleteDocument = (event, user_id, id) => {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-danger ms-1',
            },
            buttonsStyling: false,
        })
            .then(function (result) {
                // console.log(result);

                if (result.isConfirmed) {
                    axios
                        .delete('/api/v1/documents/' + id)
                        .then(response => {
                            let res = response.data;
                            // console.log(res);

                            toastr.success(res.message, 'Success!', {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            });
                            $(event.target).parents('.document').remove();
                            // setTimeout(() => {
                            //     window.location.reload();
                            // }, 1000);
                        })
                        .catch(error => {
                            // console.log(error);
                            const response = error.response.data;
                            toastr.error(response.errors[0].message, 'Error!', {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            });
                        });
                }
            })
            .catch(error => {
                // Handle any errors that occurred during the deletion process
                console.error('Error deleting document:', error);
                toastr.error('Error deleting document.', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                    timeOut: 2000,
                });
            });
    };
    Student.saveDocument = (student_id, data, canDelete = false) => {
        let formData = new FormData();
        const studentFile = document.getElementById('student_document');
        if (!studentFile.files[0]) {
            formData.append('file', studentFile.files[0]);
            formData.append('id', data.id || null);
            formData.append('title', data.title);
            formData.append('student_id', student_id);
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );
            let axiosConfig = {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Accept: 'application/json',
                },
                timeout: 10000,
                retry: 3,
                retryDelay: 10000,
            };

            axios
                .post('/api/v1/documents/' + student_id, formData, axiosConfig)
                .then(response => {
                    const res = response.data;
                    if (res.success === true) {
                        toastr['success'](
                            res.message
                                ? res.message
                                : 'Document uploaded successfully.',
                            'Success',
                            {
                                closeButton: true,
                                tapToDismiss: true,
                            }
                        );
                        Student.showDocuments(student_id, true, canDelete);
                    } else {
                        toastr['warning'](
                            'Unable to upload document',
                            'Error',
                            {
                                closeButton: true,
                                tapToDismiss: true,
                            }
                        );
                    }
                    Student.cancelDocumentUploading();
                    // console.log(response, res, res.message);
                })
                .catch(error => {
                    console.log(error);
                });
        } else {
            toastr['warning']('File missing.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        }
    };
    Student.cancelDocumentUploading = () => {
        return false;
    };
    Student.history = student_id => {
        $('#student-history > .spinner-border').show();
        // console.log('starting calender');
        Calendar.init('#student-history', student_id);
    };
    Student.trainingPlan = (student_id, reload = false) => {
        $('#student-training-plan > .spinner-border').show();
        if (trainingPlanTab.hasClass('loaded') && !reload) {
            $('#student-training-plan > .spinner-border').hide();
            return false;
        }
        axios
            .get('/api/v1/student/training-plan/' + student_id)
            .then(response => {
                const res = response.data;
                const trainingPlan = res.data;
                // console.log(res, trainingPlan);
                $('#student-training-plan > .spinner-border').hide();
                if (
                    res.status === 'success' &&
                    Object.keys(trainingPlan).length > 0
                ) {
                    let output =
                        "<div class='d-print-block'><div class='row d-flex enrolment-grid '>";
                    output += `<div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Training Plan</h2>
                        </div>
                        <div class='card-body'>`;
                    output += trainingPlan.html;
                    output += '</div></div></div></div></div>';

                    trainingPlanTab.addClass('loaded').html(output);
                } else if (res.status === 'success') {
                    trainingPlanTab.html(` <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Student training not found</h2>
                        </div>
                        <div class='card-body'><p> Student has not yet enrolled in any course. </p></div>
                    </div>
                </div>`);
                }
            })
            .catch(error => {
                $('#student-training-plan > .spinner-border').hide();
                console.log(error);
            });
    };
    Student.assessments = (student_id, reload = false) => {
        $('#student-assessments > .spinner-border').show();
        if (assessmentsTab.hasClass('loaded') && !reload) {
            $('#student-assessments > .spinner-border').hide();
            return false;
        }
        axios
            .get('/api/v1/student/assessments/' + student_id)
            .then(response => {
                const res = response.data;
                const assessments = res.data;
                // console.log(res, assessments);
                $('#student-assessments > .spinner-border').hide();
                if (
                    res.status === 'success' &&
                    Object.keys(assessments).length > 0
                ) {
                    let output =
                        "<div class=' d-print-block'><div class='row d-flex enrolment-grid '>";
                    output += `<div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>Assessments Result</h2>
                        </div>
                        <div class='card-body'>`;
                    output += assessments.html;
                    output += '</div></div></div></div></div>';

                    assessmentsTab.addClass('loaded').html(output);
                } else if (res.status === 'success') {
                    assessmentsTab.html(` <div class='row'>
                <div class='col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary mx-auto'>No assessments found!</h2>
                        </div>
                        <div class='card-body'><p> Student has not yet submitted assessments in any assigned course(s). </p></div>
                    </div>
                </div>`);
                }
            })
            .catch(error => {
                $('#student-assessments > .spinner-border').hide();
                console.log(error);
            });
    };
    Student.activities = (student_id, reload = false) => {
        // console.log('Student Activity for: ' + student_id);
        $('#student-activities > .spinner-border').show();
        if (activitiesTab.hasClass('loaded') && !reload) {
            $('#student-activities > .spinner-border').hide();
            return false;
        }
        axios
            .get('/api/v1/student/activities/' + student_id)
            .then(response => {
                // console.log(response);
                $('#student-activities > .spinner-border').hide();
                if (response.status === 200) {
                    // Sanitize the notes HTML
                    activitiesTab.html(response.data);

                    // Initialize DatePicker only if the element exists
                    if ($('#reportrange').length) {
                        Student.initializeDatePicker();
                    }

                    // Initialize DataTable
                    if (
                        !$.fn.DataTable.isDataTable('#student-activities-table')
                    ) {
                        // console.log(table, response.data);
                        const table = $('#student-activities-table').DataTable({
                            processing: true,
                            serverSide: true, // Server-side processing enabled for server-driven filtering
                            paging: false, // Disable pagination
                            ajax: {
                                url: `/api/v1/student/activities/${student_id}/data`,
                                dataSrc: 'data',
                                error: function (xhr, error, thrown) {
                                    console.error('Error:', error);
                                    console.log('XHR:', xhr.responseText);
                                },
                                data: function (d) {
                                    // Append custom filter parameters
                                    d.start_date = $('#start_date').val();
                                    d.end_date = $('#end_date').val();
                                    d.period = $('#period').val();
                                },
                            },
                            columns: [
                                {
                                    data: 'user_id',
                                    title: 'User ID',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'activity_period',
                                    title: 'Activity Period',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'total_hours',
                                    title: 'Total Hours',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'activity_duration',
                                    title: 'Activity Duration',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'logged',
                                    title: 'Logged Time',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'total_hours_completed',
                                    title: 'Total Hours Completed',
                                    orderable: false,
                                    searchable: false,
                                },
                            ],
                            searching: false, // Disable front-end searching
                            ordering: false, // Disable front-end sorting
                        });
                        // console.log(table.ajax);
                        // Set up filter functionality
                        Student.setupFilters(student_id, table);
                    }
                    $('#student-activities > .spinner-border').hide();
                    activitiesTab.addClass('loaded');
                }
            })
            .catch(error => {
                $('#student-activities > .spinner-border').hide();
                console.log(error);
                activitiesTab.html(
                    '<p>Error loading activities. Please try again.</p>'
                );
            });
    };
    Student.setupFilters = (studentId, table) => {
        // console.log('DataTable instance:', table); // Confirm the table is a valid instance

        $('#submitFilters')
            .off('click')
            .on('click', () => {
                if (table && table.ajax) {
                    table.ajax.reload(null, false); // Reload table with filters
                } else {
                    console.error(
                        'DataTable instance is invalid or ajax is undefined.'
                    );
                }
            });

        $('#clearFilters')
            .off('click')
            .on('click', () => {
                $('#filterForm')[0].reset(); // Reset the filter form
                if (table && table.ajax) {
                    table.ajax.reload(null, false);
                } else {
                    console.error(
                        'DataTable instance is invalid or ajax is undefined.'
                    );
                }
            });
    };
    Student.initializeDatePicker = () => {
        let startDateField = null;
        let endDateField = null;
        let startDate = moment().subtract(1, 'year');
        let endDate = moment();

        let datePickerCallback = function (startDate, endDate) {
            // console.log(startDate, endDate);
            $('#reportrange')
                .find('span')
                .html(
                    startDate.format('MMMM D, YYYY') +
                        ' - ' +
                        endDate.format('MMMM D, YYYY')
                );
            $('#start_date').val(startDate.format('DD-MM-YYYY'));
            $('#end_date').val(endDate.format('DD-MM-YYYY'));
            startDateField = startDate.format('YYYY-MM-DD');
            endDateField = endDate.format('YYYY-MM-DD');
        };

        $('#reportrange').daterangepicker(
            {
                startDate: startDate,
                endDate: endDate,
                autoApply: true,
                autoUpdateInput: true,
                ranges: {
                    Today: [moment(), moment()],
                    Yesterday: [
                        moment().subtract(1, 'days'),
                        moment().subtract(1, 'days'),
                    ],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [
                        moment().startOf('month'),
                        moment().endOf('month'),
                    ],
                    'Last Month': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month'),
                    ],
                    'Last 6 Months': [moment().subtract(6, 'month'), moment()],
                    'Last 1 year': [moment().subtract(1, 'year'), moment()],
                },
            },
            datePickerCallback
        );
        datePickerCallback(startDate, endDate);
    };
    Student.assignCourse = student_id => {
        var jqForm = $('#assign-course-form');
        if (jqForm.length) {
            jqForm.validate({
                debug: true,
                submitHandler: function (form, event) {
                    event.preventDefault();
                    // form = $(form);
                    const data = $(form).serialize();
                    // console.log('tabs');
                    // console.log(JSON.parse(data));
                    if (form.valid()) {
                        axios
                            .post(
                                '/api/v1/students/' +
                                    student_id +
                                    '/assign_course',
                                { data }
                            )
                            .then(response => {})
                            .catch(error => {
                                console.log(error.response);
                            });
                    }
                },
            });
        }
    };
    Student.resetProgress = (student_id, course_id) => {
        axios
            .post('/api/v1/students/progress/reset', {
                student: student_id,
                course: course_id,
            })
            .then(response => {
                const res = response.data;
                toastr['success'](
                    res.message ? res.message : 'Progress reset successfully.',
                    'Success',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
                // console.log(response, res, res.message);
            })
            .catch(error => {
                console.log(error);
            });
    };
    Student.reEvaluateCourseProgress = (student_id, course_id) => {
        axios
            .post('/api/v1/students/progress/evaluate', {
                student: student_id,
                course: course_id,
            })
            .then(response => {
                const res = response.data;
                toastr['success'](
                    res.message
                        ? res.message
                        : 'Course progress updated successfully.',
                    'Success',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                // console.log(response, res, res.message);
            })
            .catch(error => {
                console.log(error);
            });
    };
    Student.issueCertificate = (student_id, course_id, next_course) => {
        axios
            .post('/api/v1/students/certificate/issue', {
                student: student_id,
                course: course_id,
                next_course_id: next_course,
                cert_issued_on: $('#cert_issued_on').val(),
            })
            .then(response => {
                const res = response.data;
                toastr['success'](
                    res.message
                        ? res.message
                        : 'Course completion certificate issued.',
                    'Success',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                // console.log(response, res, res.message);
            })
            .catch(error => {
                console.log(error);
            });
    };
    return Student;
})(Student || {});

var Company = (function (Company) {
    const studentsTab = $('#company-students-tab');
    const leadersTab = $('#company-leaders-tab');

    Company.students = (company_id, reload = false) => {
        // console.log('Student Activity for: ' + student_id);
        const studentTabSpinner = $('#company-students > .spinner-border');
        studentTabSpinner.show();

        if (studentsTab.hasClass('loaded') && !reload) {
            studentTabSpinner.hide();
            return false;
        }
        axios
            .get('/api/v1/company/students/' + company_id)
            .then(response => {
                // console.log(response);
                studentTabSpinner.hide();
                if (response.status === 200) {
                    studentsTab.html(response.data);

                    // Initialize DataTable
                    if (
                        !$.fn.DataTable.isDataTable('#company-students-table')
                    ) {
                        // console.log(table, response.data);
                        const table = $('#company-students-table').DataTable({
                            processing: true,
                            serverSide: true, // Server-side processing enabled for server-driven filtering
                            paging: false, // Disable pagination
                            columnDefs: [
                                {
                                    targets: 0,
                                    visible: true,
                                    render: function (data, type, full, meta) {
                                        return (
                                            '<a href="/account-manager/students/' +
                                            full.id +
                                            '/edit" class="item-edit me-1 text-secondary" title="Edit">' +
                                            feather.icons['edit'].toSvg({
                                                class: 'font-small-4',
                                            }) +
                                            '</a>' +
                                            '<a href="/account-manager/students/' +
                                            full.id +
                                            '" class="item-view me-1 text-primary" title="View Details">' +
                                            feather.icons['file-text'].toSvg({
                                                class: 'font-small-4',
                                            }) +
                                            '</a>'
                                        );
                                    },
                                },
                                { targets: 1, visible: true },
                                { targets: 2, visible: true },
                            ],
                            ajax: {
                                url: `/api/v1/company/students/${company_id}/data`,
                                dataSrc: 'data',
                                error: function (xhr, error, thrown) {
                                    console.error('Error:', error);
                                    console.log('XHR:', xhr.responseText);
                                },
                                data: function (d) {
                                    // Append custom filter parameters
                                    // d.start_date = $('#start_date').val();
                                    // d.end_date = $('#end_date').val();
                                    // d.period = $('#period').val();
                                },
                            },
                            columns: [
                                {
                                    data: 'id',
                                    title: '',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'first_name',
                                    title: 'First Name',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'last_name',
                                    title: 'Last Name',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'displayable_active',
                                    title: 'Status',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'email',
                                    title: 'Email',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.phone',
                                    title: 'Phone',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.last_logged_in',
                                    title: 'Last Signed In',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'created_at',
                                    title: 'Created on',
                                    orderable: false,
                                    searchable: false,
                                },
                            ],
                            buttons: [
                                {
                                    extend: 'collection',
                                    className:
                                        'btn btn-outline-secondary dropdown-toggle me-2',
                                    text:
                                        feather.icons['share'].toSvg({
                                            class: 'font-small-4 me-50',
                                        }) + 'Export',
                                    buttons: [
                                        {
                                            extend: 'csv',
                                            text:
                                                feather.icons[
                                                    'file-text'
                                                ].toSvg({
                                                    class: 'font-small-4 me-50',
                                                }) + 'CSV',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                            },
                                        },
                                    ],
                                },
                            ],
                            searching: false, // Disable front-end searching
                            ordering: false, // Disable front-end sorting
                        });
                        // console.log(table.ajax);
                    }
                    studentTabSpinner.hide();
                    studentsTab.addClass('loaded');
                }
            })
            .catch(error => {
                studentTabSpinner.hide();
                console.log(error);
                studentsTab.html(
                    '<p>Error loading students. Please try again.</p>'
                );
            });
    };

    Company.leaders = (company_id, reload = false) => {
        // console.log('Leader Activity for: ' + leader_id);
        const leaderTabSpinner = $('#company-leaders > .spinner-border');
        leaderTabSpinner.show();

        if (leadersTab.hasClass('loaded') && !reload) {
            leaderTabSpinner.hide();
            return false;
        }
        axios
            .get('/api/v1/company/leaders/' + company_id)
            .then(response => {
                // console.log(response);
                leaderTabSpinner.hide();
                if (response.status === 200) {
                    leadersTab.html(response.data);

                    // Initialize DataTable
                    if (!$.fn.DataTable.isDataTable('#company-leaders-table')) {
                        // console.log(table, response.data);
                        const table = $('#company-leaders-table').DataTable({
                            processing: true,
                            serverSide: true, // Server-side processing enabled for server-driven filtering
                            paging: false, // Disable pagination
                            columnDefs: [
                                {
                                    targets: 0,
                                    visible: true,
                                    render: function (data, type, full, meta) {
                                        return (
                                            '<a href="/account-manager/leaders/' +
                                            full.id +
                                            '/edit" class="item-edit me-1 text-secondary" title="Edit">' +
                                            feather.icons['edit'].toSvg({
                                                class: 'font-small-4',
                                            }) +
                                            '</a>' +
                                            '<a href="/account-manager/leaders/' +
                                            full.id +
                                            '" class="item-view me-1 text-primary" title="View Details">' +
                                            feather.icons['file-text'].toSvg({
                                                class: 'font-small-4',
                                            }) +
                                            '</a>'
                                        );
                                    },
                                },
                                { targets: 1, visible: true },
                                { targets: 2, visible: true },
                            ],
                            ajax: {
                                url: `/api/v1/company/leaders/${company_id}/data`,
                                dataSrc: 'data',
                                error: function (xhr, error, thrown) {
                                    console.error('Error:', error);
                                    console.log('XHR:', xhr.responseText);
                                },
                                data: function (d) {
                                    // Append custom filter parameters
                                    // d.start_date = $('#start_date').val();
                                    // d.end_date = $('#end_date').val();
                                    // d.period = $('#period').val();
                                },
                            },
                            columns: [
                                {
                                    data: 'id',
                                    title: '',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'first_name',
                                    title: 'First Name',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'last_name',
                                    title: 'Last Name',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.position',
                                    title: 'Position',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'email',
                                    title: 'Email',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.phone',
                                    title: 'Phone',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.last_logged_in',
                                    title: 'Last Signed In',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.first_login',
                                    title: 'First Login',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'detail.first_enrollment',
                                    title: 'First Enrolment',
                                    orderable: false,
                                    searchable: false,
                                },
                                {
                                    data: 'created_at',
                                    title: 'Created on',
                                    orderable: false,
                                    searchable: false,
                                },
                            ],
                            buttons: [
                                {
                                    extend: 'collection',
                                    className:
                                        'btn btn-outline-secondary dropdown-toggle me-2',
                                    text:
                                        feather.icons['share'].toSvg({
                                            class: 'font-small-4 me-50',
                                        }) + 'Export',
                                    buttons: [
                                        {
                                            extend: 'csv',
                                            text:
                                                feather.icons[
                                                    'file-text'
                                                ].toSvg({
                                                    class: 'font-small-4 me-50',
                                                }) + 'CSV',
                                            className: 'dropdown-item',
                                            exportOptions: {
                                                columns: ':visible',
                                            },
                                        },
                                    ],
                                },
                            ],
                            searching: false, // Disable front-end searching
                            ordering: false, // Disable front-end sorting
                        });
                        // console.log(table.ajax);
                    }
                    leaderTabSpinner.hide();
                    leadersTab.addClass('loaded');
                }
            })
            .catch(error => {
                leaderTabSpinner.hide();
                console.log(error);
                leadersTab.html(
                    '<p>Error loading leaders. Please try again.</p>'
                );
            });
    };

    return Company;
})(Company || {});

(function (window, undefined) {
    'use strict';
    /*const url = new URL(window.location);
    if (url.hash) {
        const func = url.hash.substring(1);
        const tab = func.toLowerCase().replace('.','-');
        console.log('#'+ tab);
        $('#'+ tab).trigger('click');
        // eval(func+'()');
    }*/
})(window);
