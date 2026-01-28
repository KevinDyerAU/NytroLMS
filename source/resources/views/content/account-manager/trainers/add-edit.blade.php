@extends('layouts/contentLayoutMaster')

@section('title',$action['name'].' Trainer')

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('content')
    <div class='row'>
        <div class='col-md-6 col-12 mx-auto'>
            <div class='card'>
                <div class='card-body'>
                    <form method='POST' action='{{ $action['url'] }}'
                          class="form form-vertical">
                        @if(strtolower($action['name']) === 'edit')
                            @method('PUT')
                            <input type='hidden' value='{{ md5($trainer->id) }}' name='v'>
                        @endif

                        @csrf
                        @include('content.account-manager.trainers.modal-body', ['action'=>$action, 'trainer'=>$trainer ?? []])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/cleave.min.js'))}}"></script>
    <script src="{{ asset(mix('vendors/js/forms/cleave/addons/cleave-phone.us.js'))}}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script>
        // Format icon
        function iconFormat(icon) {
            return $(icon.element).data('icon') + ' ' + icon.text;
        }

        $(function() {
            @if(strtolower($action['name']) === 'edit')
            //$('#email').prop('disabled', true);
            @endif
            const phoneMask = $('.phone-number-mask'),
                prefixMask = $('.prefix-mask');
            var select = $('.select2'),
                selectIcons = $('.select2-icons');

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
            selectIcons.each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') + '"></div>');
                $this.select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent(),
                    templateResult: iconFormat,
                    templateSelection: iconFormat,
                    escapeMarkup: function(es) {
                        return es;
                    }
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

            $('#cancel').on('click', function() {
                window.location = '{{ (strtolower($action['name']) === 'edit')?route('account_manager.trainers.show',$trainer):route('account_manager.trainers.index') }}';
            });
        });
    </script>
@endsection
