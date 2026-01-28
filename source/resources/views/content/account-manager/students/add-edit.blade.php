@extends('layouts/contentLayoutMaster')

@section('title', $action['name'] . ' Student')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection
@section('content')
    @if (strtolower($action['name']) === 'edit' && auth()->user()->can('manage students'))
        @if ($student->isActive())
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="alert-body d-flex align-items-center">Student: {{ $student->name }} is set Active.&nbsp;
                    <a href="{{ route('account_manager.students.deactivate', $student) }}" class="text-danger"> Click here
                        to Deactivate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @else
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="alert-body d-flex align-items-center">Student: {{ $student->name }} is set Inactive.&nbsp;
                    <a href="{{ route('account_manager.students.activate', $student) }}" class="text-success"> Click here to
                        Activate.</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endif

    <div class='row'>
        <div class='col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
                    <form method='POST' action='{{ $action['url'] }}' class="form form-vertical" data-client-validate>
                        @if (strtolower($action['name']) === 'edit')
                            @method('PUT')
                            <input type='hidden' value='{{ md5($student->id) }}' name='v'>
                        @endif

                        @csrf
                        @include('content.account-manager.students.modal-body', [
    'action' => $action,
    'student' => $student ?? [],
])
                    </form>
                    @if (strtolower($action['name']) === 'edit' && !auth()->user()->isLeader())
                        @if (!empty($student))
                            @include('content.account-manager.students.modal-assign-course')
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset('vendors/js/forms/repeater/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/cleave.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/addons/cleave-phone.us.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('js/scripts/_my/assign_course.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/tabs.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/student-form-validation.js')) }}"></script>
    <script>
        // Format icon
        function iconFormat(icon) {
            return $(icon.element).data('icon') + ' ' + icon.text;
        }

        $(function() {
            let popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));

            let popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            @if (strtolower($action['name']) === 'create')
                // Clear all form fields and validation errors on page load
                const $form = $('form.form-vertical');
                const validator = $form.length ? $form.data('studentFormValidator') : null;

                // Clear all input fields
                $form.find('input[type="text"], input[type="email"], input[type="tel"], textarea').val('');
                $form.find('input[type="date"]').val('');

                // Clear all select fields
                $form.find('select').each(function() {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.val(null).trigger('change');
                    } else {
                        $select.val('');
                    }
                });

                // Clear validation errors
                if (validator) {
                    $form.find('input, select, textarea').each(function() {
                        validator.clearError($(this));
                    });
                }

                // Remove any existing validation error classes and messages
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').remove();
                $form.find('[data-client-feedback="true"]').remove();
            @endif

            @if (strtolower($action['name']) === 'edit')
                //$('#email').prop('disabled', true);
                // $('#leaders').prop('disabled', false);
            @endif

            const phoneMask = $('.phone-number-mask'),
                prefixMask = $('.prefix-mask');
            var selectIcons = $('.select2-icons');

            // select.each(function() {
            //     var $this = $(this);
            //     $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
            //     $this.select2({
            //         // the following code is used to disable x-scrollbar when click in select input and
            //         // take 100% width in responsive also
            //         dropdownAutoWidth: true,
            //         width: '100%',
            //         dropdownParent: $this.parent()
            //     });
            // });
            // Select With Icon
            selectIcons.each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') +
                    '"></div>');
                $this.select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent(),
                    templateResult: iconFormat,
                    templateSelection: iconFormat,
                    escapeMarkup: function(es) {
                        return es;
                    },
                    allowClear: true
                });
            });
            $('#country').on('select2:select', function(e) {
                let data = e.params.data;
                let callingCode = $(data.element).data('cc');
                // if($("#phone").val() === '') {
                //     $('#phone').val(callingCode).prop('disabled', false);
                // }
                $('#phone').prop('disabled', false);

                // if (phoneMask.length) {
                //     new Cleave(phoneMask, {
                //         blocks: [3, 3, 3, 4, 5],
                //         uppercase: true
                //     });
                // }

                countryCode = $(data.element).data('code');

            });

            $('#company').on('select2:select', function(e) {
                const data = e.params.data;
                $('#leaders').find('option').remove();
                axios.get('/api/v1/companies/' + data.id).then(response => {
                    const res = response.data;
                    const company = res.data;
                    if (res.status === 'success' && company.id > 0) {
                        let leaders = company.leaders.data;
                        // console.log(leaders);
                        if (leaders.length > 0) {
                            $('#leaders').append('<option></option>');
                            $.each(leaders, function(index, leader) {
                                // console.log(leader);
                                $('#leaders').append('<option value=' + leader.id + '>' +
                                    leader.name + ' &lt;' + leader.email +
                                    '&gt;</option>');
                                // $('#leaders').prop('disabled', false);
                            });
                        }
                    }
                }).catch(error => {
                    console.log(error);
                });

            });


            $('#cancel').on('click', function() {
                window.location =
                    '{{ strtolower($action['name']) === 'edit' ? route('account_manager.students.show', $student) : route('account_manager.students.index') }}';
            });

            // Email validation - check if email already exists
            $('#email').on('blur', function() {
                const email = $(this).val();
                const $emailField = $(this);

                // Always remove any existing email notifications first
                $emailField.siblings('.email-notification').remove();

                if (email && email.includes('@')) {
                    // Check if user is a leader (existing_student dropdown only shows for leaders)
                    const isLeader = $('#existing_student').length > 0;

                    // Show spinner while checking
                    $emailField.after(
                        '<div class="form-text email-notification text-info"><small><i class="fas fa-spinner fa-spin"></i> Checking if account already exists...</small></div>'
                    );

                    // For leaders, first check if email belongs to a student in their company
                    if (isLeader) {
                        axios.get('/api/v1/email-student-in-company/' + encodeURIComponent(email))
                            .then(response => {
                                if (response.data.found) {
                                    // Remove spinner
                                    $emailField.siblings('.email-notification').remove();

                                    // Add info notification about student in company
                                    $emailField.after(
                                        '<div class="form-text email-notification text-info"><small><i class="fas fa-info-circle"></i> Note: This email corresponds to a student from your company: ' + response.data.student_id + ' ' + response.data.student_name + '. If you proceed, the admin team will need to process this manually. Please use the Existing Student Dropdown.</div>'
                                    );
                                } else {
                                    // Student not found in company, continue with regular email check
                                    checkEmailAlreadyRegistered(email, $emailField);
                                }
                            })
                            .catch(error => {
                                console.error('Email company check error:', error);
                                // Continue with regular email check on error
                                checkEmailAlreadyRegistered(email, $emailField);
                            });
                    } else {
                        // Not a leader, just check if email already exists
                        checkEmailAlreadyRegistered(email, $emailField);
                    }
                }
                // If email is invalid format, no notification is shown
            });

            // Function to check if email is already registered
            function checkEmailAlreadyRegistered(email, $emailField) {
                axios.get('/api/v1/email-already-registered/' + encodeURIComponent(email))
                    .then(response => {
                        // Remove spinner
                        $emailField.siblings('.email-notification').remove();

                        if (response.data.exists) {
                            // Add warning notification
                            $emailField.after(
                                '<div class="form-text email-notification text-warning"><small><i class="fas fa-info-circle"></i>Note: This student already exists. On submission of this form, a notification will be sent to the Admin team to review this enrolment.</small></div>'
                            );
                        }
                        // If email doesn't exist, no notification is shown
                    })
                    .catch(error => {
                        // Remove spinner on error
                        $emailField.siblings('.email-notification').remove();
                        console.error('Email validation error:', error);
                    });
            }

            // Function to check course enrollment
            function checkCourseEnrollment() {
                const studentId = $('#existing_student').val();
                const courseId = $('#course').val();
                const $courseField = $('#course');
                // Find the parent div that contains the field (the one with mb-1, mb-0, mb-md-1 classes)
                const $courseContainer = $courseField.parent();

                // Always remove any existing course enrollment notifications first
                $courseContainer.siblings('.course-enrollment-notification').remove();


                // Only check if both student and course are selected
                if (studentId && courseId) {
                    axios.get('/api/v1/course-already-enrolled/' + studentId + '/' + courseId)
                        .then(response => {
                            if (response.data.exists) {
                                // Add warning notification
                                $courseContainer.after(
                                    '<div class="form-text course-enrollment-notification text-warning mt-1"><small><i class="fas fa-info-circle"></i> Note: This student is or was previously enrolled in this course. On submission of this form, a notification will be sent to the Admin team to review this enrolment.</small></div>'
                                );
                            }
                            // If course is not enrolled, no notification is shown
                        })
                        .catch(error => {
                            console.error('Course enrollment validation error:', error);
                        });
                }
            }

            // Handle existing student selection
            $('#existing_student').on('select2:select', function(e) {
                const studentId = e.params.data.id;
                const selectedOption = $(this).find('option[value="' + studentId + '"]');
                const isInactive = selectedOption.data('is-inactive') === '1' || selectedOption.data('is-inactive') === 1;
                // Clear email warnings when switching students
                $('#email').siblings('.email-notification').remove();

                if (studentId) {
                    // Clear P.O number, Course, and Employment service when changing student
                    $('#purchase_order').val('');
                    $('#course').val(null).trigger('change');
                    $('#employment_service').val(null).trigger('change');

                    // Get validator instance to clear validation errors
                    const $form = $('#existing_student').closest('form');
                    const validator = $form.length ? $form.data('studentFormValidator') : null;

                    // Clear validation errors for cleared fields
                    if (validator) {
                        validator.clearError($('#purchase_order'));
                        validator.clearError($('#course'));
                        validator.clearError($('#employment_service'));
                    }

                    // Remove course enrollment notification
                    $('#course').parent().siblings('.course-enrollment-notification').remove();

                    // Show loading state
                    $('#existing_student').prop('disabled', true);

                    // Fetch student data
                    axios.get('/api/v1/student/' + studentId)
                        .then(response => {
                            const student = response.data.data;

                            // Populate form fields
                            $('#first_name').val(student.first_name);
                            $('#last_name').val(student.last_name);
                            $('#preferred_name').val(student.preferred_name);
                            $('#email').val(student.email);
                            $('#phone').val(student.phone);
                            $('#address').val(student.address);
                            // Purchase Order Number is NOT autopopulated - user must enter it
                            $('#preferred_language').val(student.preferred_language);
                            $('input[name="language"]').val(student.language);

                            // Populate schedule BEFORE disabling
                            // Employment Service is NOT autopopulated - user must enter it
                            if (student.schedule) {
                                $('#schedule').val(student.schedule).trigger('change');
                            }

                            // Disable fields and remove required attribute to prevent validation and clicking
                            // (except company, course, course dates, purchase_order, and semester restriction)
                            const disabledTextFields = $('#first_name, #last_name, #preferred_name, #email, #phone, #address, #preferred_language');
                            disabledTextFields
                                .prop('disabled', true)
                                .prop('required', false)
                                .removeAttr('required')
                                .addClass('bg-light')
                                .css('pointer-events', 'none')
                                .css('cursor', 'not-allowed');

                            // Clear any validation errors on disabled fields
                            if (validator) {
                                disabledTextFields.each(function() {
                                    validator.clearError($(this));
                                });
                            }

                            // Clear email warnings after populating form (in case they persisted)
                            $('#email').siblings('.email-notification').remove();

                            // Make select fields disabled but add hidden inputs to submit values
                            // Employment Service remains editable (not disabled)
                            const disabledSelectFields = $('#schedule, #trainers, #leaders');
                            disabledSelectFields
                                .prop('disabled', true)
                                .prop('required', false)
                                .removeAttr('required');

                            // Clear any validation errors on disabled select fields
                            if (validator) {
                                disabledSelectFields.each(function() {
                                    validator.clearError($(this));
                                });
                            }

                            // Add hidden inputs to ensure values are submitted for disabled fields
                            if (student.schedule) {
                                $('#schedule').after('<input type="hidden" name="schedule" value="' +
                                    student.schedule + '">');
                            }
                            // Add hidden inputs for email and phone to ensure they're submitted when disabled
                            if (student.email) {
                                $('#email').after('<input type="hidden" name="email" value="' +
                                    student.email + '">');
                            }
                            if (student.phone) {
                                $('#phone').after('<input type="hidden" name="phone" value="' +
                                    student.phone + '">');
                            }
                            // Employment Service is NOT autopopulated - user must enter it
                            // Ensure language field is set with correct key
                            console.log('Student language value:', student.language);
                            // Map language value to valid key if needed
                            const languageKey = student.language === 'English' ? 'en' : student
                                .language;
                            $('input[name="language"]').val(languageKey);

                            // Add visual styling to disabled selects and prevent clicking
                            // Employment Service remains editable (not styled as disabled)
                            $('#schedule, #trainers, #leaders').next(
                                    '.select2-container')
                                .find('.select2-selection')
                                .addClass('bg-light')
                                .css('pointer-events', 'none')
                                .css('cursor', 'not-allowed');

                            // Handle study type if field exists
                            if ($('#study_type').length) {
                                const $studyType = $('#study_type');
                                $studyType.val(student.study_type)
                                    .prop('disabled', true)
                                    .prop('required', false)
                                    .removeAttr('required');
                                $studyType.next('.select2-container')
                                    .find('.select2-selection')
                                    .css('pointer-events', 'none')
                                    .css('cursor', 'not-allowed');

                                // Clear any validation errors on disabled study type field
                                if (validator) {
                                    validator.clearError($studyType);
                                }
                            }

                            // Handle company selection (editable)
                            if (student.companies && student.companies.length > 0) {
                                $('#company').val(student.companies[0]).trigger('change');

                                // Load leaders for the selected company
                                setTimeout(() => {
                                    if (student.leaders && student.leaders.length > 0) {
                                        const $leaders = $('#leaders');
                                        $leaders.val(student.leaders[0]).trigger('change');
                                        $leaders.prop('disabled', true)
                                            .prop('required', false)
                                            .removeAttr('required');
                                        $leaders.next('.select2-container')
                                            .find('.select2-selection')
                                            .addClass('bg-light')
                                            .css('pointer-events', 'none')
                                            .css('cursor', 'not-allowed');

                                        // Clear any validation errors on disabled leaders field
                                        if (validator) {
                                            validator.clearError($leaders);
                                        }
                                    }
                                }, 500);
                            }

                            // Handle trainers
                            if (student.trainers && student.trainers.length > 0) {
                                $('#trainers').val(student.trainers).trigger('change');
                            }

                        })
                        .catch(error => {
                            console.error('Error fetching student data:', error);
                            toastr.error('Failed to load student data');
                        })
                        .finally(() => {
                            $('#existing_student').prop('disabled', false);
                            // Check course enrollment after student data is loaded
                            checkCourseEnrollment();
                        });
                }
            });

            // Check for pre-selected inactive student on page load (after Select2 initializes)
            setTimeout(function() {
                const initialStudentId = $('#existing_student').val();
                if (initialStudentId) {
                    const initialOption = $('#existing_student').find('option[value="' + initialStudentId + '"]');
                    const isInitialInactive = initialOption.data('is-inactive') === '1' || initialOption.data('is-inactive') === 1;
                }
            }, 100);

            // Clear form when existing student is cleared
            $('#existing_student').on('select2:clear', function(e) {
                // Clear all form fields
                $('#first_name, #last_name, #preferred_name, #email, #phone, #address, #preferred_language')
                    .val('');
                $('#purchase_order').val('');
                $('#company, #leaders, #trainers, #schedule, #employment_service').val(null).trigger(
                    'change');
                if ($('#study_type').length) {
                    $('#study_type').val('')
                        .prop('disabled', false)
                        .prop('required', true)
                        .attr('required', 'required');
                    $('#study_type').next('.select2-container')
                        .find('.select2-selection')
                        .css('pointer-events', 'auto')
                        .css('cursor', '');
                }

                // Re-enable all fields and restore required attributes
                $('#first_name, #last_name, #preferred_name, #email, #phone, #address, #preferred_language')
                    .prop('disabled', false)
                    .prop('required', true)
                    .attr('required', 'required')
                    .removeClass('bg-light')
                    .css('pointer-events', 'auto')
                    .css('cursor', '');
                // Purchase Order Number remains editable (not read-only)

                // Re-enable all select fields, restore required attributes, and remove hidden inputs
                $('#schedule, #employment_service, #trainers, #leaders')
                    .prop('disabled', false)
                    .prop('required', true)
                    .attr('required', 'required');
                $('input[type="hidden"][name="schedule"], input[type="hidden"][name="employment_service"], input[type="hidden"][name="email"], input[type="hidden"][name="phone"]')
                    .remove();

                // Remove visual styling from selects and restore clickability
                $('#schedule, #employment_service, #trainers, #leaders').next('.select2-container')
                    .find('.select2-selection')
                    .removeClass('bg-light')
                    .css('pointer-events', 'auto')
                    .css('cursor', '');

                // Reset submit button for new student
                $('#submit-form').removeClass('btn-primary').addClass('btn-success');
                $('#submit-form span:not(.spinner-border)').text('Create');

                // Remove email warnings when student is cleared
                $('#email').siblings('.email-notification').remove();

                // Remove course enrollment warning when student is cleared
                $('#course').parent().siblings('.course-enrollment-notification').remove();
            });

            // Check course enrollment when course is selected
            $('#course').on('select2:select', function(e) {
                checkCourseEnrollment();
            });

            // Remove course enrollment warning when course is cleared
            $('#course').on('select2:clear', function(e) {
                $(this).parent().siblings('.course-enrollment-notification').remove();
            });

        });
    </script>
@endsection
