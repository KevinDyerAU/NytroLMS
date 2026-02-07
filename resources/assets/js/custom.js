(function(window, undefined) {
    'use strict';
    /*
    NOTE:
    ------
    PLACE HERE YOUR OWN JAVASCRIPT CODE IF NEEDED
    WE WILL RELEASE FUTURE UPDATES SO IN ORDER TO NOT OVERWRITE YOUR JAVASCRIPT CODE PLEASE CONSIDER WRITING YOUR SCRIPT HERE.  */
    $('#country').val(15).trigger('change');
    if ($('#phone').length > 0) {
        // if($("#phone").val() === '') {
        //     $('#phone').val('+61');
        // }
        const phoneMask = $('.phone-number-mask');
        if (phoneMask.length) {
            new Cleave(phoneMask, {
                blocks: [3, 3, 3, 4, 5],
                uppercase: true
            });
        }
    }
    // $('#timezone').val('Australia/Sydney').trigger('change');
    var basicToast = document.querySelector('.basic-toast');
    var basicToastBtn = document.querySelector('.toast-basic-toggler');
    if (basicToast) {
        var showBasicToast = new bootstrap.Toast(basicToast, { delay: 2000 });
        if (showBasicToast) {
            showBasicToast.show();
        }
    }
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, { delay: 2000 });
    });

    var stackedToast = document.querySelector('.toast-stacked');
    var stackedToastBtn = document.querySelector('.toast-stacked-toggler');
    if (stackedToast) {
        var showStackedToast = new bootstrap.Toast(stackedToast, { delay: 2000 });
        if (showStackedToast) {
            showStackedToast.show();
        }
    }
})(window);
