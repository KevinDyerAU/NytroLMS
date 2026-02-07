let timeZone = 'Australia/Sydney';
moment.tz.setDefault(timeZone);

var Sidebar = (function (Sidebar) {
    Sidebar.expendParentAccordion = function (el) {
        let parents = el.parents('.accordion-collapse');
        parents.each(function () {
            $(this).collapse('show');
        });
    };
    return Sidebar;
})(Sidebar || {});

var LMS = (function (LMS) {
    LMS.UploadEvidence = function (lessonID, elementID, studentID) {
        event.preventDefault();
        let formData = new FormData();
        const checklistFile = document.getElementById(elementID);
        // const statusField = document.getElementById(elementID + '_status');
        if (!checklistFile.files[0]) {
            toastr['warning']('You need to upload a valid file.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            formData.append('file', checklistFile.files[0]);
            formData.append('student', studentID);
            // formData.append('status', statusField.value);
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );

            axios
                .post('/api/v1/upload/evidence/' + lessonID, formData)
                .then(response => {
                    let res = response.data;
                    // console.log(response);
                    toastr['success'](res.message, 'Successfully Uploaded', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    Student.trainingPlan(studentID, true);
                })
                .then(() => {
                    $(document).trigger('refreshStudentActivity');
                })
                .catch(error => {
                    console.log(error);
                });
            return false;
        }
    };
    LMS.UploadChecklist = function (quizID, elementID, studentID) {
        event.preventDefault();
        let formData = new FormData();
        const checklistFile = document.getElementById(elementID);
        const statusField = document.getElementById(elementID + '_status');
        // const nameField = document.getElementById(elementID + '_name');
        if (
            !checklistFile.files[0] ||
            !statusField.value ||
            statusField.value === '' ||
            typeof statusField.value == 'undefined'
            // || (!nameField.value || nameField.value === '' || typeof nameField.value == 'undefined' || nameField.length > 56)
        ) {
            if (!checklistFile.files[0]) {
                toastr['warning']('You need to upload a valid file.', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
            }
            if (
                !statusField.value ||
                statusField.value === '' ||
                typeof statusField.value == 'undefined'
            ) {
                toastr['warning'](
                    'You need to select checklist status.',
                    'Error',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
            }
            // if (!nameField.value || nameField.value === '' || typeof nameField.value == 'undefined' || nameField.length > 56) {
            //     toastr['warning']('A valid name for the checklist is missing (MAX 56 Char).', 'Error', {
            //         closeButton: true,
            //         tapToDismiss: true
            //     });
            // }
        } else {
            formData.append('file', checklistFile.files[0]);
            formData.append('student', studentID);
            formData.append('status', statusField.value);
            // formData.append('name', nameField.value);
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );

            axios
                .post('/api/v1/upload/checklist/' + quizID, formData)
                .then(response => {
                    let res = response.data;
                    // console.log(response);
                    toastr['success'](res.message, 'Successfully Uploaded', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    Student.trainingPlan(studentID, true);
                })
                .then(() => {
                    $(document).trigger('refreshStudentActivity');
                })
                .catch(error => {
                    console.log(error);
                });
            return false;
        }
    };
    LMS.MarkWorkPlacement = function (lessonID, studentID) {
        return axios
            .post('/api/v1/mark/work_placement/' + lessonID, {
                student: studentID,
            })
            .then(response => {
                let res = response.data;
                toastr['success'](res.message, 'Success', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                Student.trainingPlan(studentID, true);
            })
            .then(() => {
                $(document).trigger('refreshStudentActivity');
            })
            .catch(error => {
                console.log(error);
            });
    };
    LMS.MarkLessonComplete = function (lessonID, studentID) {
        axios
            .post('/api/v1/mark/lesson/' + lessonID, { student: studentID })
            .then(response => {
                let res = response.data;
                toastr['success'](res.message, 'Success', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                Student.trainingPlan(studentID, true);
            })
            .then(() => {
                $(document).trigger('refreshStudentActivity');
            })
            .catch(error => {
                console.log(error);
            });
    };
    LMS.ShowLessonCompetent = function (input) {
        const data = JSON.parse(input);
        const modalHolder = $('#competencies-details');
        const modalBody = `<div class="border-top-light border-top-2 pt-1 mt-1">
                                    <div class="d-flex justify-content-between flex-grow-1">
                                        <span class="flex-grow-1">Lesson: ${data.title} </span>
                                        <small
                                            class="text-muted width-250">Start Date: ${data.start_date} - End Date: ${data.end_date}</small>
                                    </div>
                                    <h5 class="p-1 bg-light text-purple">The student is assessed as competent in all the assessment requirements for this unit of competency.</h5>
                                    <form id="LessonCompetentForm${data.lessonID}" class="my-1">
                                        <label for="endDate${data.lessonID}">Set Lesson End Date: </label>
                                        <input type="text" name="endDate${data.lessonID}"
                                         id="endDate${data.lessonID}"
                                         data-mindate="${data.min_date}"
                                         data-enddate="${data.end_date}"
                                         data-startdate="${data.start_date}"
                                          class="form-control date-picker" required="required" />
                                        <label for="remarks${data.lessonID}">Your Remarks: </label>
                                        <textarea class="form-control mb-1" id="remarks${data.lessonID}"></textarea>
                                        <button class="btn btn-purple"
                                                onclick="LMS.MarkLessonCompetent(${data.lessonID},${data.studentID})">Mark Competent
                                        </button>
                                    </form>
                                </div>`;

        modalHolder.find('.modal-body').html(modalBody);
        let myModalEl = document.getElementById('competencies-details');
        let myModal = new bootstrap.Modal(myModalEl);
        myModal.show();
        //date-picker
        modalHolder.find('.date-picker').each(function () {
            let min_date = $(this).data('mindate');
            let end_date = $(this).data('enddate');

            // Ensure min_date is not less than end_date
            if (
                end_date &&
                min_date &&
                new Date(min_date) < new Date(end_date)
            ) {
                min_date = end_date;
            }
            // console.log(min_date);
            $(this).flatpickr({
                dateFormat: 'Y-m-d',
                minDate: min_date ? min_date : new Date('2022-01-01'),
            });
        });
    };
    LMS.hideLessonCompetent = function () {
        let myModalEl = document.getElementById('competencies-details');
        let myModal = new bootstrap.Modal(myModalEl);
        myModal.hide();
        setTimeout(function () {
            $('#competencies-details')
                .find('.modal-body')
                .html(
                    '<h5 class="p-1 bg-light text-purple">Lesson marked competent successfully.</h5><p>You may close this now</p>'
                );
        }, 5000);
    };
    LMS.MarkLessonCompetent = function (lessonID, studentID) {
        event.preventDefault();
        let remarks = $('#remarks' + lessonID).val();
        let minDate = $('#endDate' + lessonID).data('mindate');
        let lessonEndDate = $('#endDate' + lessonID).data('enddate');
        let endDate = $('#endDate' + lessonID).val();
        //set endDate to which ever is greater minDate or lessonEndDate
        endDate =
            new Date(endDate) < new Date(minDate)
                ? minDate
                : new Date(endDate) < new Date(lessonEndDate)
                ? lessonEndDate
                : endDate;
        // console.log(minDate, lessonEndDate, endDate);

        // console.log(remarks, $("#remarks" + lessonID));
        axios
            .post('/api/v1/competent/lesson/' + lessonID, {
                student: studentID,
                remarks: remarks,
                endDate: endDate,
                minDate: minDate,
                lessonEndDate: lessonEndDate,
            })
            .then(response => {
                // console.log(response);
                if (response.status === 200) {
                    // console.log('success with status 200');
                    toastr['success']('Lesson marked competent', 'Success', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    // $("#competencies-details").removeClass('show').attr('aria-hidden', true).hide();
                    LMS.hideLessonCompetent();
                    if (typeof Student !== 'undefined') {
                        // console.log('training plan');
                        Student.trainingPlan(studentID, true);
                    } else {
                        // console.log('refreshing what');
                    }
                }
            })
            .then(() => {
                // console.log('refreshing');
                // $("#competencies-details").removeClass('show').attr('aria-hidden', true).hide();
                LMS.hideLessonCompetent();
                if (typeof Student !== 'undefined') {
                    // console.log('student activity');
                    $(document).trigger('refreshStudentActivity');
                } else {
                    // console.log('page');
                    location.reload();
                }
            })
            .catch(error => {
                const response = error.response?.data ?? '';
                // console.log(error, response);
                toastr.error(
                    response ? response.errors[0].message : '',
                    'Error!',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                        timeOut: 2000,
                    }
                );
            });
        return false;
    };
    LMS.MarkTopicComplete = function (topicID, studentID) {
        axios
            .post('/api/v1/mark/topic/' + topicID, { student: studentID })
            .then(response => {
                let res = response.data;
                toastr['success'](res.message, 'Success', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                Student.trainingPlan(studentID, true);
            })
            .then(() => {
                $(document).trigger('refreshStudentActivity');
            })
            .catch(error => {
                console.log(error);
            });
    };
    LMS.destroy = (event, type, id) => {
        // console.log(type, id);
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
                        .delete('/api/v1/' + type + '/' + id)
                        .then(response => {
                            let res = response.data;
                            // console.log(res);

                            toastr.success(res.message, 'Success!', {
                                closeButton: true,
                                tapToDismiss: true,
                                timeOut: 2000,
                            });
                            $(event.target).parents('tr').remove();
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
                // console.log(error);
                const response = error.response.data;
                toastr.error(response.errors[0].message, 'Error!', {
                    closeButton: true,
                    tapToDismiss: true,
                    timeOut: 2000,
                });
            });
    };
    LMS.UnlockLesson = function (lessonID, studentID) {
        axios
            .post('/account-manager/lessons/' + lessonID + '/unlock', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                student_id: studentID,
            })
            .then(response => {
                if (response.data.success) {
                    toastr['success'](
                        'Lesson unlocked successfully',
                        'Success',
                        {
                            closeButton: true,
                            tapToDismiss: true,
                        }
                    );
                    Student.trainingPlan(studentID, true);
                }
            })
            .catch(error => {
                toastr['error']('Failed to unlock lesson', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                console.log(error);
            });
    };

    LMS.LockLesson = function (lessonID, studentID) {
        axios
            .post('/account-manager/lessons/' + lessonID + '/lock', {
                _token: $('meta[name="csrf-token"]').attr('content'),
                student_id: studentID,
            })
            .then(response => {
                if (response.data.success) {
                    toastr['success']('Lesson locked successfully', 'Success', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    Student.trainingPlan(studentID, true);
                }
            })
            .catch(error => {
                toastr['error']('Failed to lock lesson', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                console.log(error);
            });
    };
    return LMS;
})(LMS || {});
