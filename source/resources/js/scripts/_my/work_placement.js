var WorkPlacement = (function (WorkPlacement) {
    const workPlacementsTab = '.content-work-placements';

    WorkPlacement.init = () => {
        WorkPlacement.setupDatePicker();
        WorkPlacement.formHandling();
        WorkPlacement.setupForm();
    };

    WorkPlacement.setupForm = () => {
        let consultationCheckbox = document.getElementById(
            'consultation_completed'
        );
        const formElements = document.querySelectorAll(
            '#work-placement-form input, #work-placement-form textarea, #work-placement-form .date-picker'
        );

        if (consultationCheckbox) {
            const newCheckbox = consultationCheckbox.cloneNode(true);
            consultationCheckbox.parentNode.replaceChild(
                newCheckbox,
                consultationCheckbox
            );
            consultationCheckbox = newCheckbox;

            consultationCheckbox.addEventListener('change', function () {
                formElements.forEach(el => {
                    if (
                        el.id !== 'course_id' &&
                        el.id !== 'consultation_completed'
                    ) {
                        el.disabled = !this.checked;
                        if (el._flatpickr && el._flatpickr.altInput) {
                            el._flatpickr.altInput.disabled = !this.checked;
                        }
                    }
                });
            });

            formElements.forEach(el => {
                if (
                    el.id !== 'course_id' &&
                    el.id !== 'consultation_completed'
                ) {
                    el.disabled = !consultationCheckbox.checked;
                    if (el._flatpickr && el._flatpickr.altInput) {
                        el._flatpickr.altInput.disabled =
                            !consultationCheckbox.checked;
                    }
                }
            });
            if (document.getElementById('course_id')) {
                document.getElementById('course_id').disabled = false;
            }
            consultationCheckbox.disabled = false;
        }
    };

    WorkPlacement.formHandling = () => {
        const wpForm = $('#work-placement-form');

        wpForm.on('submit', function (e) {
            e.preventDefault();
        });
        if (wpForm.length) {
            wpForm.validate({
                ignore: [],
                rules: {
                    course_id: { required: true },
                    course_start_date: { required: true },
                    course_end_date: { required: true },
                },
                messages: {
                    course_id: { required: 'Please select a course.' },
                    course_start_date: {
                        required: 'Missing course start date.',
                    },
                    course_end_date: { required: 'Missing course end date.' },
                },
                errorPlacement: function (error, element) {
                    if (element.is('select.select2')) {
                        error.insertAfter(element.next('.select2-container'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function (form, event) {
                    event.preventDefault();
                    const submitButton = $('#work-placement-submit');
                    submitButton.prop('disabled', true).text('Processing...');

                    WorkPlacement.save()
                        .then(() => {
                            submitButton
                                .prop('disabled', false)
                                .text(
                                    wpForm.find('.modal-title').text() ===
                                        'Add Work Placement'
                                        ? 'Proceed'
                                        : 'Update'
                                );
                        })
                        .catch(() => {
                            submitButton
                                .prop('disabled', false)
                                .text(
                                    wpForm.find('.modal-title').text() ===
                                        'Add Work Placement'
                                        ? 'Proceed'
                                        : 'Update'
                                );
                        });
                },
            });
        }
    };

    WorkPlacement.show = (user_id, reload = false) => {
        const subjectSpinner = $('#student-work-placements > .spinner-border');
        const subjectTab = $('#student-work-placements-tab');

        if (subjectSpinner.length) {
            subjectSpinner.show();
        }

        if (subjectTab.hasClass('loaded') && !reload) {
            subjectSpinner.hide();
            return false;
        }

        axios
            .get('/api/v1/work-placements/' + user_id)
            .then(response => {
                subjectSpinner.hide();
                if (response.status === 200) {
                    subjectTab
                        .find(workPlacementsTab)
                        .html(response.data.data.html);

                    if (!$.fn.DataTable.isDataTable('#work-placements-table')) {
                        if ($('#work-placements-table').length) {
                            const table = $('#work-placements-table').DataTable(
                                {
                                    processing: true,
                                    serverSide: true,
                                    paging: false,
                                    columnDefs: [
                                        {
                                            targets: 0,
                                            visible: true,
                                            render: function (
                                                data,
                                                type,
                                                full,
                                                meta
                                            ) {
                                                return (
                                                    '<a onclick="WorkPlacement.edit(event, ' +
                                                    full.id +
                                                    ')" ' +
                                                    'href="#" class="item-view me-1 text-primary" title="Work Placement: ' +
                                                    full.id +
                                                    '">' +
                                                    feather.icons['eye'].toSvg({
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
                                        url:
                                            '/api/v1/work-placements/data/' +
                                            user_id,
                                        dataSrc: 'data',
                                        error: function (xhr, error, thrown) {
                                            console.error('Error:', error);
                                            console.log(
                                                'XHR:',
                                                xhr.responseText
                                            );
                                        },
                                        data: function (d) {},
                                    },
                                    columns: [
                                        {
                                            data: 'id',
                                            title: '',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'user.name',
                                            title: 'Student',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'course.title',
                                            title: 'Course',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'course_start_date',
                                            title: 'Course Start',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'course_end_date',
                                            title: 'Course End',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'consultation_completed',
                                            title: 'Consultation Completed',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'consultation_completed_on',
                                            title: 'Consultation Completed On',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'wp_commencement_date',
                                            title: 'WP Commencement Date',
                                            orderable: false,
                                            searchable: false,
                                        },
                                        {
                                            data: 'wp_end_date',
                                            title: 'WP End Date',
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
                                    language: {
                                        emptyTable:
                                            'No work placements available',
                                        processing:
                                            '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>',
                                    },
                                    searching: false,
                                    ordering: false,
                                }
                            );
                        }
                    }
                    subjectSpinner.hide();
                    subjectTab.addClass('loaded');
                }
            })
            .catch(error => {
                subjectSpinner.hide();
                const response = error.response?.data;
                toastr.error(
                    response?.errors?.[0]?.message ||
                        'Error loading work placements.',
                    'Error',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
            });
    };

    WorkPlacement.delete = (event, id) => {
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
        }).then(function (result) {
            if (result.isConfirmed) {
                axios
                    .delete('/api/v1/work-placements/' + id)
                    .then(response => {
                        toastr.success(response.data.message, 'Success!', {
                            closeButton: true,
                            tapToDismiss: true,
                            timeOut: 2000,
                        });
                        $('#work-placements-table').DataTable().ajax.reload();
                    })
                    .catch(error => {
                        const response = error.response?.data;
                        toastr.error(
                            response?.errors?.[0]?.message ||
                                'Error deleting work placement.',
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

    WorkPlacement.showModal = () => {
        const $work = $('#work-placement-form');
        if ($work.length === 0) {
            console.error('Work Placement form not found in the DOM.');
            return;
        }
        $work.find('.modal-title').text('Add Work Placement');
        $work.find('button[type="submit"]').text('Proceed');
        $work.find('#work_placement_id').remove();
        $work[0].reset();
        $('#selected_course .start_date').text('');
        $('#selected_course .end_date').text('');
        $('#work-placement-form .date-picker').each(function () {
            if (
                this._flatpickr &&
                typeof this._flatpickr.clear === 'function'
            ) {
                this._flatpickr.clear();
            }
        });
        $('#work-placement-sidebar').modal('show');
        $('#work-placement-sidebar').one('shown.bs.modal', () => {
            WorkPlacement.setupDatePicker();
            WorkPlacement.setupForm();
            $('#consultation_completed')
                .prop('checked', false)
                .trigger('change');
        });
    };

    WorkPlacement.edit = (event, id) => {
        axios
            .get('/api/v1/work-placements/show/' + id)
            .then(response => {
                const wp = response.data.data;
                const $work = $('#work-placement-form');
                $('#work-placement-sidebar').modal('show');
                $('#work-placement-sidebar').one('shown.bs.modal', () => {
                    WorkPlacement.setupDatePicker();
                    const setDateIfFlatpickr = (selector, value) => {
                        const el = document.querySelector(selector);
                        if (el && value && document.body.contains(el)) {
                            if (!el._flatpickr) {
                                $(el).flatpickr({
                                    altInput: true,
                                    altFormat: 'd-m-Y', // Updated to d-m-Y
                                    dateFormat: 'Y-m-d',
                                });
                            }
                            try {
                                // Parse DD-MM-YYYY to Date object
                                if (
                                    typeof value === 'string' &&
                                    /^\d{2}-\d{2}-\d{4}$/.test(value)
                                ) {
                                    const [day, month, year] = value
                                        .split('-')
                                        .map(Number);
                                    value = new Date(year, month - 1, day);
                                }
                                el._flatpickr.setDate(value, true, 'Y-m-d');
                            } catch (e) {
                                console.warn(
                                    `Failed to set date for ${selector}:`,
                                    e
                                );
                            }
                        }
                    };
                    $('#work-placement-sidebar')
                        .find('.modal-title')
                        .text('Update Work Placement');
                    $work.find('button[type="submit"]').text('Update');

                    if ($('#work_placement_id').length > 0) {
                        $('#work_placement_id').val(wp.id);
                    } else {
                        $(
                            '<input type="hidden" id="work_placement_id" name="id" value="' +
                                wp.id +
                                '" />'
                        ).insertAfter(
                            $('#work-placement-form').find('#course_id')
                        );
                    }

                    $('#course_id')
                        .val(wp.course_id || '')
                        .trigger('change');
                    $('#consultation_completed').prop(
                        'checked',
                        wp.consultation_completed || false
                    );
                    setDateIfFlatpickr(
                        '#consultation_completed_on',
                        wp.consultation_completed_on
                    );
                    setDateIfFlatpickr(
                        '#wp_commencement_date',
                        wp.wp_commencement_date
                    );
                    setDateIfFlatpickr('#wp_end_date', wp.wp_end_date);
                    $('#employer_name').val(wp.employer_name || '');
                    $('#employer_email').val(wp.employer_email || '');
                    $('#employer_phone').val(wp.employer_phone || '');
                    $('#employer_address').val(wp.employer_address || '');
                    $('#employer_notes').val(wp.employer_notes || '');

                    $('#selected_course .start_date').text(
                        wp.course_start_date || ''
                    );
                    $('#selected_course .end_date').text(
                        wp.course_end_date || ''
                    );
                    $("input[name='course_start_date']").val(
                        wp.course_start_date || ''
                    );
                    $("input[name='course_end_date']").val(
                        wp.course_end_date || ''
                    );

                    WorkPlacement.setupForm();
                    $('#consultation_completed').trigger('change');
                });
            })
            .catch(error => {
                console.error('Error in WorkPlacement.edit:', error);
                const message =
                    error.response?.data?.errors?.[0]?.message ||
                    error.message ||
                    'Error loading work placement data.';
                toastr.error(message, 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
            });
    };

    WorkPlacement.cancelEditing = () => {
        const $work = $('#work-placement-form');
        $work.find('.modal-title').text('Add Work Placement');
        $work.find('button[type="submit"]').text('Proceed');
        $work.find('#work_placement_id').remove();
        $work[0].reset();
        $('#selected_course .start_date').text('');
        $('#selected_course .end_date').text('');
        $('#work-placement-sidebar').modal('hide');
        $('#course_id').prop('disabled', false);
    };

    WorkPlacement.save = () => {
        const form = $('#work-placement-form');
        const data = {
            user_id: $('#user_id').val() || null,
            id: $('#work_placement_id').val() || null,
            course_id: $('#course_id').val() || null,
            course_start_date:
                $("input[name='course_start_date']").val() || null,
            course_end_date: $("input[name='course_end_date']").val() || null,
            consultation_completed:
                $('#consultation_completed').is(':checked') || null,
            consultation_completed_on:
                $('#consultation_completed_on').val() || null,
            wp_commencement_date: $('#wp_commencement_date').val() || null,
            wp_end_date: $('#wp_end_date').val() || null,
            employer_name: $('#employer_name').val() || null,
            employer_email: $('#employer_email').val() || null,
            employer_phone: $('#employer_phone').val() || null,
            employer_address: $('#employer_address').val() || null,
            employer_notes: $('#employer_notes').val() || null,
            company_id: null,
            leader_id: null,
        };
        if (!data.course_id) {
            toastr['warning']('Course is required.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
            return Promise.reject('Course is required.');
        }

        const method = data.id ? 'put' : 'post';
        const url = data.id
            ? '/api/v1/work-placements/' + data.id
            : '/api/v1/work-placements';

        return axios({ method, url, data })
            .then(response => {
                toastr['success'](response.data.message, 'Success', {
                    closeButton: true,
                    tapToDismiss: true,
                });
                form[0].reset();
                $('#work_placement_id').remove();
                $('#work-placements-table').DataTable().ajax.reload();
                WorkPlacement.cancelEditing();
            })
            .catch(error => {
                const response = error.response?.data;
                toastr.error(
                    response?.errors?.[0]?.message ||
                        'Error saving work placement.',
                    'Error',
                    {
                        closeButton: true,
                        tapToDismiss: true,
                    }
                );
                throw error;
            });
    };

    WorkPlacement.setupDatePicker = () => {
        if (typeof flatpickr !== 'undefined') {
            const wp_datepicker = $('#work-placement-form .date-picker');
            if (wp_datepicker.length) {
                wp_datepicker.each(function () {
                    $(this).flatpickr({
                        altInput: true,
                        altFormat: 'd-m-Y', // Updated to d-m-Y
                        dateFormat: 'Y-m-d',
                    });
                });
            } else {
                console.warn(
                    'No .date-picker elements found in #work-placement-form'
                );
            }
        } else {
            console.warn('flatpickr is not loaded');
        }
    };

    return WorkPlacement;
})(WorkPlacement || {});
