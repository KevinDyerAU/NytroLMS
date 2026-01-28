@extends('frontend/layouts/contentLayoutMaster')

@section('title', 'Student Enrolment Application')
@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/wizard/bs-stepper.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/dragula.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/pickers/pickadate/pickadate.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/pickers/flatpickr/flatpickr.min.css')) }}">
@endsection

@section('page-style')
    <!-- Page css files -->
    <link rel="stylesheet" type="text/css"
        href="{{ asset('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/pages/page-blog.css') }}" />
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/form-wizard.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-drag-drop.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/pickers/form-pickadate.css')) }}">
    <style>
        .step.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .step.disabled .bs-stepper-box,
        .step.disabled .bs-stepper-title,
        .step.disabled .bs-stepper-subtitle {
            color: #9e9e9e;
        }
        .step.disabled button.step-trigger {
            cursor: not-allowed;
        }
    </style>
@endsection
@section('content')
    <!-- Re-enrollment Banner -->
    @if(!empty($requireAgreementRenewal))
        <div class="alert alert-info fs-5 text-center fw-bold" role="alert">
            <i data-lucide="info" class="me-2"></i>
            Re-enrollment: Please verify your details and re-sign the agreement.
        </div>
    @endif

    <!-- Horizontal Wizard -->
    <section class="modern-horizontal-wizard">
        <div class="bs-stepper wizard-modern modern-horizontal-wizard-multi-step">
            <div class="bs-stepper-header" role="tablist">
                @foreach ($steps as $key => $form)
                    <div class="step {{ $key == $step ? 'active' : '' }} {{ !empty($form['disabled']) ? 'disabled' : '' }}"
                        data-target="#{{ $form['slug'] }}"
                        role="tab" id="{{ $form['slug'] }}-trigger">
                        <button type="button" class="step-trigger" {{ !empty($form['disabled']) ? 'disabled' : '' }}>
                            <span class="bs-stepper-box">{{ $key }}</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">{{ $form['title'] }}</span>
                                @if (!empty($form['subtitle']))
                                    <span class="bs-stepper-subtitle">{{ $form['subtitle'] }}</span>
                                @endif
                            </span>
                        </button>
                    </div>
                    @if (!$loop->last)
                        <div class="line">
                            <i data-lucide="chevron-right" class="font-medium-2"></i>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="bs-stepper-content">
                @include('frontend.content.onboard.step-' . $step, [
                    'step' => $step,
                    'step_detail' => $steps[$step],
                    'enrolment' => isset($enrolment['step-' . $step]) ? $enrolment['step-' . $step] : [],
                    'requireAgreementRenewal' => $requireAgreementRenewal ?? false,
                ])
            </div>
        </div>
    </section>
    <!-- /Horizontal Wizard -->
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset('data/languages.js') }}"></script>
    <script src="{{ asset(mix('vendors/vendor/jquery-autocomplete/jquery.autocomplete.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/wizard/bs-stepper.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/enable-select.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/pickers/flatpickr/flatpickr.min.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/dragula.min.js')) }}"></script>
@endsection
@section('page-script')
    <script src="{{ asset(mix('vendors/js/dobdropdown/jquery.date-dropdowns.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/lms-quiz.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/lms-question.js')) }}"></script>
    <script>
        function toggleOtherField(stepfield, field, condition, onVal = '', hiddenfield) {
            const step = $('#' + stepfield);
            $('#' + hiddenfield).hide();
            // console.log(onVal, condition, onVal.indexOf(step.find('#' + field).val()));
            if ((onVal == null || typeof onVal == 'undefined' || onVal == '') && step.find('#' + field).val() != '') {
                // console.log('non here');
                $('#' + hiddenfield).show();
            } else if (condition == 'eq' && onVal.indexOf(step.find('#' + field).val()) > -1) {
                // console.log('eq here');
                if (onVal === step.find('#' + field).val()) {
                    $('#' + hiddenfield).show();
                } else {
                    $('#' + hiddenfield).hide();
                }
            } else if (condition == 'nq' && onVal.indexOf(step.find('#' + field).val()) < 0) {
                // console.log('neq here');
                if (onVal != step.find('#' + field).val()) {
                    $('#' + hiddenfield).show();
                } else {
                    $('#' + hiddenfield).hide();
                }
            }
            step.find('#' + field).on('select2:select', function(e) {
                // console.log('change occurred for: ' + field + ' ' + hiddenfield);
                var data = e.params.data;
                $('#' + hiddenfield).fadeOut();
                // console.log(onVal, step.find('#' + field).val());
                // console.log(onVal.indexOf(step.find('#' + field).val()), onVal.indexOf($(data.element).val()));
                if (field == 'document2_type') {
                    $('#invalid_document2_type').hide();
                    if ($(this).val() == $('#step-4').find('#document1_type').val() && $('#step-4').find(
                            '#usi_number').val() != '') {
                        $('#invalid_document2_type').show();
                        $(this).val(null).trigger('change');
                        return false;
                    }
                }
                if (onVal == null && step.find('#' + field).val() != '') {
                    $('#' + hiddenfield).fadeIn();
                } else if (condition == 'eq' && onVal.indexOf($(data.element).val()) > -1) {
                    $('#' + hiddenfield).fadeIn();
                } else if (condition == 'nq' && onVal.indexOf($(data.element).val()) < 0) {
                    $('#' + hiddenfield).fadeIn();
                }
            });
        }

        let languagesArray = $.map(languages, function(value, key) {
            return {
                value: value,
                data: key
            };
        });

        $(function() {
            var ckEditorOptions = {
                extraPlugins: 'notification',
                removePlugins: 'exportpdf,scayt',
                filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
                filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
                filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
                filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}'
            };
            CKEDITOR.replaceAll('content-ckeditor', ckEditorOptions);
            CKEDITOR.on("instanceReady", function(event) {
                $(".content-ckeditor.hidden").each(function() {
                    let idCKEditor = $(this).prop('id');
                    window.CKEDITOR.instances[idCKEditor].setReadOnly(true);
                });

                event.editor.on("beforeCommandExec", function(event) {
                    // Show the paste dialog for the paste buttons and right-click paste
                    if (event.data.name == "paste") {
                        event.editor._.forcePasteDialog = true;
                    }
                    // Don't show the paste dialog for Ctrl+Shift+V
                    if (event.data.name == "pastetext" && event.data.commandData.from ==
                        "keystrokeHandler") {
                        event.cancel();
                    }
                })
            });
            if ($('.sorting-list').length) {
                $('.sorting-list').each(function(index) {
                    let id = $(this).attr('id');
                    let sorting_drake = dragula([$(this)[0]]);
                });
            }
            if ($('.matrix-source').length) {
                $('.matrix-source').each(function(index) {
                    let destination = $(this).data('destination');
                    console.log($("#" + destination)[0]);
                    let matrix_drake = dragula([$(this)[0], $("#" + destination)[0]], {
                        revertOnSpill: true
                    });

                });
            }

            $("#quizDetailsTrigger").click(function() {
                if ($(this).attr('aria-expanded') === "true") {
                    $(this).text("Hide Quiz Instructions").addClass('btn-dark').removeClass('btn-info');
                } else {
                    $(this).text("View Quiz Instructions").removeClass('btn-dark').addClass('btn-info');
                }
            });
            // Note: Removed Reinitialisation of lms-quiz stepper as it is handled by lms-quiz.js which automatically navigates to the first unanswered question
        });

        $(document).ready(function() {
            $('#language_other').autocomplete({
                // serviceUrl: '/autosuggest/service/url',
                lookup: languagesArray,
                lookupFilter: function(suggestion, originalQuery, queryLowerCase) {
                    var re = new RegExp('\\b' + $.Autocomplete.utils.escapeRegExChars(queryLowerCase),
                        'gi');
                    return re.test(suggestion.value);
                },
                onSelect: function(suggestion) {
                    // console.log('You selected: ' + suggestion.value + ', ' + suggestion.data);
                    $('#language_other').val(suggestion.value);
                },
                onInvalidateSelection: function() {
                    console.log('You selected: none');
                }
            });

            $('#dob').dateDropdowns({
                required: true,
                daySuffixes: false,
                minAge: 15,
                monthFormat: "short",
                wrapperClass: "date-dropdowns d-flex flex-row",
                dropdownClass: "form-select me-25",
                submitFormat: 'yyyy-mm-dd'
            });

            $('#invalid_document2_type').hide();

            var select = $('.select2'),
                selectIcons = $('.select2-icons'),
                basicPickr = $('.flatpickr-basic'),
                dobPickr = $('.flatpickr-dob'),
                modernWizard = document.querySelector('.modern-horizontal-wizard-multi-step'),
                step1 = $('#step-1'),
                step2 = $('#step-2'),
                step3 = $('#step-3'),
                step4 = $('#step-4'),
                step5 = $('#step-5');

            if (basicPickr.length) {
                basicPickr.flatpickr({
                    altInput: true,
                    altFormat: 'm-d-Y',
                    dateFormat: 'Y-m-d'
                });
            }

            if (dobPickr.length) {
                dobPickr.flatpickr({
                    maxDate: new Date().fp_incr(-3650),
                    altInput: true,
                    altFormat: 'd-m-Y',
                    dateFormat: 'Y-m-d'
                });
            }
            toggleOtherField('step-1', 'language', 'eq', 'other', 'show_if_language_other');
            toggleOtherField('step-1', 'has_disability', 'eq', 'yes', 'show_if_has_disability');
            // toggleOtherField('step-1', 'industry2', 'eq', '9', 'show_if_industry2_other');

            toggleOtherField('step-2', 'additional_qualification', 'eq', 'yes', 'show_if_additional_qualification');
            toggleOtherField('step-2', 'certificate_any', 'nq', ['1', ''], 'show_certificate_any_details');

            toggleOtherField('step-4', 'document1_type', 'is', null, 'show_document1');
            toggleOtherField('step-4', 'document2_type', 'is', null, 'show_document2');

            if (!$('#same_address').is(':checked') && $("#residence_address").val() == '') {
                $('#postal_address').val('');
                $('#postal_address_postcode').val('');
            }

            $('#step-1').find('#same_address').on('click', function() {
                $('#postal_address').val('');
                $('#postal_address_postcode').val('');

                if ($(this).is(':checked') && $("#residence_address").val() != '') {
                    $('#postal_address').val($('#residence_address').val());
                    $('#postal_address_postcode').val($('#residence_address_postcode').val());
                }
            });
        });
    </script>
@endsection
