(function (window, undefined) {
    'use strict';
    let timeZone = 'Australia/Sydney';
    moment.tz.setDefault(timeZone);

    // console.log(moment.tz());
    var select = $('.select2'),
        date = new Date(),
        sourceItem = $('.source-item'),
        btnAddNewItem = $('.btn-add-new '),
        datepicker = $('.date-picker');

    const canBackDate = $('#assign-course-form').data('backdate');

    select.each(function () {
        var $this = $(this);
        $this.wrap(
            '<div class="position-relative form-select-control' +
                $this.data('class') +
                '"></div>'
        );
        $this.select2({
            dropdownAutoWidth: true,
            width: '100%',
            dropdownParent: $this.parent(),
            allowClear: true,
        });
        $this.on('select2:select', function (e) {
            const data = e.params.data;
            let parentDiv = $(data.element).parent().parent().parent().parent();
            parentDiv.find('.date-picker').prop('disabled', false);
        });
    });

    if (sourceItem.length) {
        $('#assign-course-form').on('submit', function (e) {
            // console.log('preventing submission');
            e.preventDefault();
        });
        sourceItem.repeater({
            isFirstItemUndeletable: true,
            show: function () {
                $(this).slideDown();
                $('#assign-course-form .select2-container').remove();
                $('#assign-course-form .select2').each(function () {
                    var $this = $(this);
                    $this.addClass('xyz');
                    $this.removeClass('select2-hidden-accessible');
                    $this.wrap(
                        '<div class="position-relative form-select-control' +
                            $this.data('class') +
                            '"></div>'
                    );
                    $this.select2({
                        dropdownAutoWidth: true,
                        width: '100%',
                        dropdownParent: $this.parent(),
                        allowClear: true,
                        // allowClear: true
                    });
                    $this.on('select2:select', function (e) {
                        const data = e.params.data;
                        let parentDiv = $(data.element)
                            .parent()
                            .parent()
                            .parent()
                            .parent();
                        parentDiv.find('.date-picker').prop('disabled', false);
                    });
                });
                $('#assign-course-form .date-picker').each(function () {
                    const backDateAllowed = $(this).data('backdate');
                    $(this).flatpickr({
                        minDate:
                            canBackDate ||
                            (typeof backDateAllowed != 'undefined' &&
                                backDateAllowed)
                                ? new Date('2022-01-01')
                                : new Date(),
                        // dateFormat: 'Y-m-d',
                        altFormat: 'DD-MM-YYYY',
                        altInput: true,
                        // altFormat: 'LLL',
                        dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                        parseDate(dateString, format) {
                            let timezonedDate = new moment.tz(
                                dateString,
                                format,
                                timeZone
                            );

                            return new Date(
                                timezonedDate.year(),
                                timezonedDate.month(),
                                timezonedDate.date(),
                                timezonedDate.hour(),
                                timezonedDate.minute(),
                                timezonedDate.second()
                            );
                        },
                        formatDate(date, format) {
                            return moment
                                .tz(
                                    [
                                        date.getFullYear(),
                                        date.getMonth(),
                                        date.getDate(),
                                        date.getHours(),
                                        date.getMinutes(),
                                        date.getSeconds(),
                                    ],
                                    timeZone
                                )
                                .locale('en-GB')
                                .format(format);
                        },

                        onChange: function (selectedDates, dateStr, instance) {
                            let parentDiv = $(instance.element)
                                .parent()
                                .parent();
                            let course_length = parentDiv
                                .find('.select2')
                                .find(':selected')
                                .data('length');
                            let selectedDate = selectedDates[0];
                            // console.log(selectedDate);
                            selectedDate.setDate(
                                selectedDate.getDate() + course_length
                            );
                            // console.log(selectedDate, dateStr)
                            parentDiv
                                .find('.date-picker-end')
                                .prop('disabled', false);
                            parentDiv.find('.date-picker-end').flatpickr({
                                defaultDate: selectedDate,
                                // dateFormat: 'Y-m-d',
                                altFormat: 'DD-MM-YYYY',
                                altInput: true,
                                // altFormat: 'LLL',
                                dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                                parseDate(dateString, format) {
                                    let timezonedDate = new moment.tz(
                                        dateString,
                                        format,
                                        timeZone
                                    );

                                    return new Date(
                                        timezonedDate.year(),
                                        timezonedDate.month(),
                                        timezonedDate.date(),
                                        timezonedDate.hour(),
                                        timezonedDate.minute(),
                                        timezonedDate.second()
                                    );
                                },
                                formatDate(date, format) {
                                    return moment
                                        .tz(
                                            [
                                                date.getFullYear(),
                                                date.getMonth(),
                                                date.getDate(),
                                                date.getHours(),
                                                date.getMinutes(),
                                                date.getSeconds(),
                                            ],
                                            timeZone
                                        )
                                        .locale('en-GB')
                                        .format(format);
                                },
                            });
                        },
                    });
                });
            },
            hide: function (e) {
                $(this).slideUp().remove();
            },
            // isFirstItemUndeletable: true
        });
    }
    if (btnAddNewItem.length) {
        btnAddNewItem.on('click', function () {
            // if (feather) {
            //     // featherSVG();
            //     feather.replace({ width: 14, height: 14 });
            // }
        });
    }
    if (datepicker.length) {
        datepicker.each(function () {
            const backDateAllowed = $(this).data('backdate');
            $(this).flatpickr({
                minDate:
                    canBackDate ||
                    (typeof backDateAllowed != 'undefined' && backDateAllowed)
                        ? new Date('2022-01-01')
                        : new Date(),
                // dateFormat: 'Y-m-d',
                altFormat: 'DD-MM-YYYY',
                altInput: true,
                // altFormat: 'LLL',
                dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                parseDate(dateString, format) {
                    let timezonedDate = new moment.tz(
                        dateString,
                        format,
                        timeZone
                    );

                    return new Date(
                        timezonedDate.year(),
                        timezonedDate.month(),
                        timezonedDate.date(),
                        timezonedDate.hour(),
                        timezonedDate.minute(),
                        timezonedDate.second()
                    );
                },
                formatDate(date, format) {
                    return moment
                        .tz(
                            [
                                date.getFullYear(),
                                date.getMonth(),
                                date.getDate(),
                                date.getHours(),
                                date.getMinutes(),
                                date.getSeconds(),
                            ],
                            timeZone
                        )
                        .locale('en-GB')
                        .format(format);
                },
                onChange: function (selectedDates, dateStr, instance) {
                    let parentDiv = $(instance.element).parent().parent();
                    let course_length = parentDiv
                        .find('.select2')
                        .find(':selected')
                        .data('length');
                    let selectedDate = selectedDates[0];
                    // console.log(selectedDate);
                    selectedDate.setDate(
                        selectedDate.getDate() + course_length
                    );
                    // console.log(selectedDate, dateStr)
                    parentDiv.find('.date-picker-end').prop('disabled', false);
                    parentDiv.find('.date-picker-end').flatpickr({
                        defaultDate: selectedDate,
                        // dateFormat: 'Y-m-d',
                        altFormat: 'DD-MM-YYYY',
                        altInput: true,
                        // altFormat: 'LLL',
                        dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                        parseDate(dateString, format) {
                            let timezonedDate = new moment.tz(
                                dateString,
                                format,
                                timeZone
                            );

                            return new Date(
                                timezonedDate.year(),
                                timezonedDate.month(),
                                timezonedDate.date(),
                                timezonedDate.hour(),
                                timezonedDate.minute(),
                                timezonedDate.second()
                            );
                        },
                        formatDate(date, format) {
                            return moment
                                .tz(
                                    [
                                        date.getFullYear(),
                                        date.getMonth(),
                                        date.getDate(),
                                        date.getHours(),
                                        date.getMinutes(),
                                        date.getSeconds(),
                                    ],
                                    timeZone
                                )
                                .locale('en-GB')
                                .format(format);
                        },
                    });

                    $('#course').on('select2:select', function (e) {
                        $('#course_start_at').val('');
                        $('#course_ends_at').val('');
                    });
                    let courseLength = $('#course')
                        .find(':selected')
                        .data('length');
                    let startDate = new Date($('#course_start_at').val());
                    // console.log(courseLength, $('#course_start_at').val(), startDate.getDate());

                    $('#course_ends_at').flatpickr({
                        defaultDate: startDate.setDate(
                            startDate.getDate() + courseLength
                        ),
                        // dateFormat: 'Y-m-d',
                        altFormat: 'DD-MM-YYYY',
                        altInput: true,
                        // altFormat: 'LLL',
                        dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                        parseDate(dateString, format) {
                            let timezonedDate = new moment.tz(
                                dateString,
                                format,
                                timeZone
                            );

                            return new Date(
                                timezonedDate.year(),
                                timezonedDate.month(),
                                timezonedDate.date(),
                                timezonedDate.hour(),
                                timezonedDate.minute(),
                                timezonedDate.second()
                            );
                        },
                        formatDate(date, format) {
                            return moment
                                .tz(
                                    [
                                        date.getFullYear(),
                                        date.getMonth(),
                                        date.getDate(),
                                        date.getHours(),
                                        date.getMinutes(),
                                        date.getSeconds(),
                                    ],
                                    timeZone
                                )
                                .locale('en-GB')
                                .format(format);
                        },
                    });
                },
            });
        });
        $('.date-picker-end').each(function () {
            let minDate = $(this).data('mindate');
            // console.log(minDate);
            $(this).flatpickr({
                defaultDate: minDate,
                // dateFormat: 'Y-m-d',
                altFormat: 'DD-MM-YYYY',
                altInput: true,
                // altFormat: 'LLL',
                dateFormat: 'YYYY-MM-DD\\\\THH:mm:ssZ',

                parseDate(dateString, format) {
                    let timezonedDate = new moment.tz(
                        dateString,
                        format,
                        timeZone
                    );

                    return new Date(
                        timezonedDate.year(),
                        timezonedDate.month(),
                        timezonedDate.date(),
                        timezonedDate.hour(),
                        timezonedDate.minute(),
                        timezonedDate.second()
                    );
                },
                formatDate(date, format) {
                    return moment
                        .tz(
                            [
                                date.getFullYear(),
                                date.getMonth(),
                                date.getDate(),
                                date.getHours(),
                                date.getMinutes(),
                                date.getSeconds(),
                            ],
                            timeZone
                        )
                        .locale('en-GB')
                        .format(format);
                },
            });
        });
    }

    var jqForm = $('#assign-course-form');
    if (jqForm.length) {
        // Handle Proceed button click to show confirmation modal
        $('#proceed-btn').on('click', function (e) {
            e.preventDefault();

            // Validate form first
            if (jqForm.valid()) {
                // Show confirmation modal
                $('#confirm-notification-modal').modal('show');
            }
        });

        // Handle Yes button in confirmation modal
        $('#confirm-yes').on('click', function () {
            // Set notify_leader to 1 (yes)
            $('#notify_leader').val('1');

            // Ensure selected email courses are added to the form BEFORE submit
            var container = $('#email-course-ids-container');
            if (container && container.length) {
                container.empty();
                $('.modal-email-course:checked').each(function () {
                    var val = $(this).val();
                    if (val) {
                        $('<input />', {
                            type: 'hidden',
                            name: 'email_course_ids[]',
                            value: val,
                        }).appendTo(container);
                    }
                });
            }

            // Hide confirmation modal
            $('#confirm-notification-modal').modal('hide');
            // Submit the form
            submitForm();
        });

        // Handle No button in confirmation modal
        $('#confirm-notification-modal .btn-secondary').on(
            'click',
            function () {
                // Set notify_leader to 0 (no)
                $('#notify_leader').val('0');
                // Hide confirmation modal
                $('#confirm-notification-modal').modal('hide');
                // Submit the form
                submitForm();
            }
        );

        // Function to submit the form
        function submitForm() {
            const form = jqForm;
            const data = form.serialize();

            axios
                .post(
                    '/api/v1/students/' +
                        jqForm.find('input[name="student_id"]').val() +
                        '/assign_course',
                    data
                )
                .then(response => {
                    toastr['success']('', response.data.message, {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    if (window.location.href.indexOf('edit') > 0) {
                        $('#assign-course-sidebar').modal('hide');
                    } else {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.log(error.response);
                });
        }

        jqForm.validate({
            // debug: true,
            ignore: '',
            // Remove submitHandler since we're handling form submission manually
        });
    }
})(window);
