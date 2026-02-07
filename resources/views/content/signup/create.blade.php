@extends('layouts.contentLayoutMaster')

@section('title', 'Register Student')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/form-validation.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
@endsection

@section('content')

    <div class='row'>
        <div class='col-12 mx-auto'>
            <div class='card'>
                <div class="card-header">
                    <a href="{{ env('APP_URL') }}">
                        <img src="{{ \App\Helpers\Helper::getLogoAsset() }}" width="155" alt="{{ env('APP_NAME') }}"
                             style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
                    </a>
                </div>
                <div class='card-body'>
                    <form method='POST' action='{{ route('signup-store', $data) }}' class="form form-vertical">
                        @csrf
                        @include('content.signup.form')
                    </form>
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
    <script src="{{ asset(mix('vendors/js/pickers/flatpickr/flatpickr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/validation/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/cleave.min.js'))}}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/addons/cleave-phone.us.js'))}}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
@endsection

@section('page-script')
    <!-- Page js files -->
    <script>
        // Format icon
        function iconFormat(icon) {
            return $(icon.element).data('icon') + ' ' + icon.text;
        }

        $(function () {
            let popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));

            let popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            const phoneMask = $('.phone-number-mask'),
                prefixMask = $('.prefix-mask');
            var select = $('.select2'), selectIcons = $('.select2-icons');

            select.each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                $this.select2({
                    // the following code is used to disable x-scrollbar when click in select input and
                    // take 100% width in responsive also
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                });
            });

            // Select With Icon
            selectIcons.each(function () {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                $this.select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent(),
                    templateResult: iconFormat,
                    templateSelection: iconFormat,
                    escapeMarkup: function (es) {
                        return es;
                    },
                    allowClear: true
                });
            });
            $('#country').on('select2:select', function (e) {
                let data = e.params.data;
                let callingCode = $(data.element).data('cc');
                $('#phone').prop('disabled', false);
                countryCode = $(data.element).data('code');

            });
        });
    </script>
@endsection
