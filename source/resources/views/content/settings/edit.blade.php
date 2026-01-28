@extends('layouts/contentLayoutMaster')

@section('title', $title)

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('content')

    <div class='row'>
        <div class='col-md-12 col-12 mx-auto'>
            @includeWhen(strtolower($type) === 'site', 'content.settings.site', ['settings' => $settings])
            @includeWhen(strtolower($type) === 'menu', 'content.settings.menu', ['settings' => $settings])
            @includeWhen(strtolower($type) === 'featured-images', 'content.settings.featured-images', [
                'settings' => $settings,
            ])
        </div>
    </div>
@endsection

@section('vendor-script')
    <script src="{{ asset('vendors/js/forms/repeater/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset('/vendor/laravel-filemanager/js/stand-alone-button.js') }}"></script>
@endsection
@section('page-script')
    <script>
        $(function() {

            const route_prefix = "/laravel-filemanager";
            $('#lfm_logo').filemanager('image', {
                prefix: route_prefix
            });
            $('#lfm_featured_image_student').filemanager('image', {
                prefix: route_prefix
            });
            $('#lfm_featured_image_leader').filemanager('image', {
                prefix: route_prefix
            });


            var select = $('.select2');

            select.each(function() {
                var $this = $(this);
                $this.wrap('<div class="position-relative form-select-control' + $this.data('class') +
                    '"></div>');
                $this.select2({
                    // the following code is used to disable x-scrollbar when click in select input and
                    // take 100% width in responsive also
                    dropdownAutoWidth: true,
                    width: '100%',
                    dropdownParent: $this.parent()
                });
            });
            $('#footer-menu-repeater').repeater({
                initEmpty: false,
                show: function() {
                    $(this).slideDown(); // Animation for new items
                    // Feather Icons
                    // if (feather) {
                    //     feather.replace({ width: 14, height: 14 });
                    // }
                    // let checkbox = $(this).find('.form-check-input');
                    // checkbox.attr('name', checkbox.attr('name').slice(0, -2));
                    // console.log(checkbox);

                },
                hide: function(remove) {
                    if (confirm('Are you sure you want to remove this item?')) {
                        $(this).slideUp(remove); // Animation for removing items
                    }
                },
                isFirstItemUndeletable: true // Prevent the first item from being deleted
            });
            $('#sidebar-menu-repeater').repeater({
                initEmpty: false,
                show: function() {
                    $(this).slideDown(); // Animation for new items
                    // Feather Icons
                    // if (feather) {
                    //     feather.replace({ width: 14, height: 14 });
                    // }
                    // let checkbox = $(this).find('.form-check-input');
                    // checkbox.attr('name', checkbox.attr('name').slice(0, -2));
                    // console.log(checkbox);

                },
                hide: function(remove) {
                    if (confirm('Are you sure you want to remove this item?')) {
                        $(this).slideUp(remove); // Animation for removing items
                    }
                },
                isFirstItemUndeletable: true // Prevent the first item from being deleted
            });
        });
    </script>
@endsection
