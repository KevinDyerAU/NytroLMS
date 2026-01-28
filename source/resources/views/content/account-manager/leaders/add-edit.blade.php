@extends('layouts/contentLayoutMaster')

@section('title', $action['name'] . ' Leader')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
                    <form method='POST' action='{{ $action['url'] }}' class="form form-vertical">
                        @if (strtolower($action['name']) === 'edit')
                            @method('PUT')
                            <input type='hidden' value='{{ md5($leader->id) }}' name='v'>
                        @endif

                        @csrf
                        @include('content.account-manager.leaders.modal-body', [
                            'action' => $action,
                            'leader' => $leader ?? [],
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/cleave.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/addons/cleave-phone.us.js')) }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script>
        // Format icon
        function iconFormat(icon) {
            return $(icon.element).data('icon') + ' ' + icon.text;
        }

        // Password toggle functionality
        function togglePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Client-side validation
        function validateForm() {
            const form = document.querySelector('form');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = `${field.placeholder || field.name} is required.`;
                    field.parentNode.appendChild(feedback);
                    isValid = false;
                }
            });

            // Email validation
            const email = document.getElementById('email');
            if (email && email.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    email.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Please enter a valid email address.';
                    email.parentNode.appendChild(feedback);
                    isValid = false;
                }
            }

            // Password confirmation
            const password = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            if (password && passwordConfirmation && password.value !== passwordConfirmation.value) {
                passwordConfirmation.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Passwords do not match.';
                passwordConfirmation.parentNode.appendChild(feedback);
                isValid = false;
            }

            return isValid;
        }

        $(function() {
            @if (strtolower($action['name']) === 'edit')
                //$('#email').prop('disabled', true);
            @endif

            // Initialize Select2 for selects
            $('.select2').each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control"></div>');
                $this.select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                });
            });

            // Password toggle event listeners
            document.getElementById('togglePassword')?.addEventListener('click', () => {
                togglePasswordVisibility('password', 'togglePassword');
            });

            document.getElementById('togglePasswordConfirmation')?.addEventListener('click', () => {
                togglePasswordVisibility('password_confirmation', 'togglePasswordConfirmation');
            });

            // Form submission with validation
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return false;
                }

                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                const spinner = submitBtn.querySelector('.spinner-border');
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
            });

            // Cancel button handler
            document.getElementById('cancel')?.addEventListener('click', function() {
                window.location =
                    '{{ strtolower($action['name']) === 'edit' ? route('account_manager.leaders.show', $leader) : route('account_manager.leaders.index') }}';
            });
        });
    </script>
@endsection
