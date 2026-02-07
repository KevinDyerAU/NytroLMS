(function (window, $) {
    'use strict';

    function StudentFormValidator(form) {
        this.$form = $(form);
        this.$requiredFields = this.$form.find('[required]');
        this.initialise();
    }

    StudentFormValidator.prototype.initialise = function () {
        var self = this;

        this.$form.attr('novalidate', true);

        this.$requiredFields.each(function () {
            var $field = $(this);
            if (!$field.data('clientValidationBound')) {
                $field.on(
                    'input.clientValidation change.clientValidation',
                    function () {
                        self.validateField(this);
                    }
                );
                $field.data('clientValidationBound', true);
            }
        });

        this.$form.on('submit.clientValidation', function (event) {
            var isValid = self.validateForm();
            if (!isValid) {
                event.preventDefault();
                event.stopImmediatePropagation();
                self.focusFirstInvalid();
            }
        });

        var $clearButton = this.$form.find('#clear-form');
        if ($clearButton.length) {
            $clearButton.on('click.clientValidation', function (event) {
                event.preventDefault();
                self.clearForm();
            });
        }
    };

    StudentFormValidator.prototype.validateForm = function () {
        var self = this;
        var formValid = true;

        this.$requiredFields.each(function () {
            if (!self.validateField(this)) {
                formValid = false;
            }
        });

        return formValid;
    };

    StudentFormValidator.prototype.validateField = function (field) {
        var $field = $(field);

        if ($field.prop('disabled')) {
            return true;
        }

        if ($field.attr('type') === 'hidden' && !this.isSelect2($field)) {
            return true;
        }

        if (!this.isSelect2($field) && !$field.is(':visible')) {
            return true;
        }

        // Skip validation for fields inside hidden modals
        if ($field.closest('.modal:not(.show)').length > 0) {
            return true;
        }

        var value = this.getFieldValue($field);
        var hasValue = value !== null && value !== '';

        if (!hasValue) {
            this.showError($field);
            return false;
        }

        this.clearError($field);
        return true;
    };

    StudentFormValidator.prototype.getFieldValue = function ($field) {
        if ($field.is(':checkbox')) {
            return $field.is(':checked') ? '1' : '';
        }

        if ($field.is(':radio')) {
            var name = $field.attr('name');
            if (!name) {
                return '';
            }
            var $checked = this.$form.find(
                'input[name="' + name + '"]:checked'
            );
            return $checked.length ? $checked.val() : '';
        }

        var value = $field.val();

        if (Array.isArray(value)) {
            var filtered = value.filter(function (item) {
                return item !== null && String(item).trim() !== '';
            });
            return filtered.length ? filtered.join(',') : '';
        }

        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return String(value).trim();
    };

    StudentFormValidator.prototype.showError = function ($field) {
        $field = this.asJQuery($field);

        var message =
            $field.data('validationMessage') ||
            this.getFieldLabel($field) ||
            'This field is required.';
        if (message && message !== 'This field is required.') {
            message = message.replace(/\s*\*/g, '').trim();
            if (!/required\.$/i.test(message)) {
                message = message + ' is required.';
            }
        }

        var $feedback = this.getFeedbackElement($field);

        $feedback.text(message).addClass('d-block');

        if (this.isSelect2($field)) {
            this.toggleSelect2State($field, true);
        } else {
            $field.addClass('is-invalid');
        }
    };

    StudentFormValidator.prototype.clearError = function ($field) {
        $field = this.asJQuery($field);

        if (this.isSelect2($field)) {
            this.toggleSelect2State($field, false);
        } else {
            $field.removeClass('is-invalid');
        }

        var $feedback = this.getFeedbackElement($field, false);
        if ($feedback.length) {
            $feedback.removeClass('d-block').text('');
        }
    };

    StudentFormValidator.prototype.getFeedbackElement = function (
        $field,
        createIfMissing
    ) {
        if (typeof createIfMissing === 'undefined') {
            createIfMissing = true;
        }

        var $wrapper = $field.closest('.mb-1');
        var $existing = $wrapper.find(
            '.invalid-feedback[data-client-feedback="true"]'
        );

        if ($existing.length || !createIfMissing) {
            return $existing;
        }

        var $feedback = $(
            '<div class="invalid-feedback" data-client-feedback="true"></div>'
        );

        if (this.isSelect2($field)) {
            var $select2 = $field.next('.select2');
            if ($select2.length) {
                $feedback.insertAfter($select2);
            } else {
                $feedback.insertAfter($field);
            }
        } else {
            $feedback.insertAfter($field);
        }

        return $feedback;
    };

    StudentFormValidator.prototype.toggleSelect2State = function (
        $field,
        isInvalid
    ) {
        var $select2 = $field.next('.select2');
        if (!$select2.length) {
            return;
        }

        var $selection = $select2.find('.select2-selection');
        if (isInvalid) {
            $field.addClass('is-invalid');
            $selection.addClass('is-invalid');
            $select2.addClass('select2-container-invalid');
        } else {
            $field.removeClass('is-invalid');
            $selection.removeClass('is-invalid');
            $select2.removeClass('select2-container-invalid');
        }
    };

    StudentFormValidator.prototype.isSelect2 = function ($field) {
        return $field.hasClass('select2-hidden-accessible');
    };

    StudentFormValidator.prototype.asJQuery = function (field) {
        return field instanceof $ ? field : $(field);
    };

    StudentFormValidator.prototype.getFieldLabel = function ($field) {
        var $wrapper = $field.closest('.mb-1, .form-group');
        var labelText = '';

        if ($wrapper.length) {
            var $label = $wrapper.find('label').first();
            if ($label.length) {
                labelText = $label.text().trim();
            }
        }

        if (!labelText) {
            labelText =
                $field.attr('aria-label') || $field.attr('placeholder') || '';
        }

        return labelText;
    };

    StudentFormValidator.prototype.focusFirstInvalid = function () {
        var $target = this.$form.find('.is-invalid:visible').first();
        if ($target.length) {
            $target.trigger('focus');
            return;
        }

        var $select2 = this.$form.find('.select2 .is-invalid').first();
        if ($select2.length) {
            $select2.trigger('focus');
        }
    };

    StudentFormValidator.prototype.clearForm = function () {
        var self = this;

        this.$form.find('input, textarea').each(function () {
            var $field = $(this);

            if (
                $field.attr('type') === 'hidden' ||
                $field.is('[name="_token"], [name="_method"], [name="v"]')
            ) {
                return;
            }

            if ($field.is(':checkbox') || $field.is(':radio')) {
                $field.prop('checked', false);
            } else if ($field.is('[type="file"]')) {
                $field.val('');
            } else {
                $field.val('');
            }

            self.clearError($field);
        });

        this.$form.find('select').each(function () {
            var $select = $(this);

            if ($select.prop('multiple')) {
                $select.val([]);
            } else {
                $select.val('');
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change');
            }

            self.clearError($select);
        });
    };

    function initialise(context) {
        var $context = context ? $(context) : $(document);
        var $forms = $context.find('form').filter(function () {
            return $(this).find('[data-client-validate]').length > 0;
        });

        $forms.each(function () {
            var $form = $(this);
            if (!$form.data('studentFormValidator')) {
                $form.data(
                    'studentFormValidator',
                    new StudentFormValidator(this)
                );
            }
        });
    }

    window.StudentFormValidation = {
        init: initialise,
    };

    $(document).ready(function () {
        initialise(document);
    });

    $(document).on('shown.bs.modal', function (event) {
        initialise(event.target);
    });
})(window, window.jQuery);
