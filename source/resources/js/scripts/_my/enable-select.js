(function (window, undefined) {
    'use strict';
    // Format icon
    function iconFormat(icon) {
        return $(icon.element).data('icon') + ' ' + icon.text;
    }
    let select = $('.select2'),
        selectIcons = $('.select2-icons');

    if (select.length > 0) {
        select.each(function () {
            var $this = $(this);
            $this.wrap(
                '<div class="position-relative form-select-control' +
                    $this.data('class') +
                    '"></div>'
            );
            $this.select2({
                // the following code is used to disable x-scrollbar when click in select input and
                // take 100% width in responsive also
                dropdownAutoWidth: true,
                width: '100%',
                dropdownParent: $this.parent(),
                allowClear: true,
            });
        });
    }

    if (selectIcons.length > 0) {
        selectIcons.each(function () {
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
                templateResult: iconFormat,
                templateSelection: iconFormat,
                escapeMarkup: function (es) {
                    return es;
                },
                allowClear: true,
            });
        });
    }
})(window);
