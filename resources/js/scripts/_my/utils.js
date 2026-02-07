var Utils = (function (Utils) {
    Utils.switchFields = (fieldVal, checkVal, condition) => {
        switch (condition) {
            case 'eq':
                // console.log('eq',fieldVal, checkVal, condition, !checkVal === fieldVal);
                return fieldVal !== '' && checkVal === fieldVal;
            case 'neq':
            case 'nq':
            case 'ne':
                // console.log('neq', fieldVal, checkVal, typeof fieldVal, typeof checkVal, condition, !(checkVal !== fieldVal));
                return fieldVal !== '' && checkVal !== fieldVal;
            default:
                // console.log(fieldVal, checkVal, condition);
                return (
                    (checkVal == null ||
                        typeof checkVal === 'undefined' ||
                        checkVal === '') &&
                    fieldVal !== ''
                );
        }
    };
    Utils.toggleRelatedFields = (
        field,
        relatedfield,
        condition,
        onVal = null
    ) => {
        // console.log($('#' + field).val(), onVal, condition, Utils.switchFields($('#' + field).val(), onVal, condition));
        if (Utils.switchFields($('#' + field).val(), onVal, condition)) {
            $('#' + relatedfield).show();
        }
        $('#' + field).on('select2:select', function (e) {
            var data = e.params.data;
            $('#' + relatedfield).hide();
            // console.log($(data.element).val(), onVal, condition, Utils.switchFields($(data.element).val(), onVal, condition));
            if (Utils.switchFields($(data.element).val(), onVal, condition)) {
                $('#' + relatedfield).show();
            }
        });
    };
    Utils.removeImage = (imageDiv, id) => {
        axios
            .delete('/api/v1/images/' + id)
            .then(response => {
                // console.log(response.data, response.data.success);
                if (response.data.success === true) {
                    $('#' + imageDiv)
                        .fadeOut('slow')
                        .remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.data.message,
                        customClass: {
                            confirmButton: 'btn btn-success',
                        },
                    });
                }
            })
            .catch(error => {
                console.log(error);
                toastr['error']('Unable to delete image.', 'Error!', {
                    positionClass: 'toast-bottom-center',
                    rtl: false,
                });
            });
        return false;
    };
    Utils.getLanguages = () => {
        let term = $('#language_other').val();
        console.log(term);
        axios
            .get('/data/languages.json?term=' + term)
            .then(data => {
                // return data;
                // return $.map(data, function (item) {
                //     let val = JSON.parse(item);
                //     console.log(val);
                //     return {
                //         label: val.value,
                //         value: val.key
                //     }
                // });

                // Filter the JSON data based on the search term
                let filteredData = $.grep(data, function (item) {
                    console.log(
                        item.label.toLowerCase(),
                        term.toLowerCase(),
                        item.label.toLowerCase().indexOf(term.toLowerCase())
                    );
                    return (
                        item.label.toLowerCase().indexOf(term.toLowerCase()) !==
                        -1
                    );
                });
                console.log(filteredData);
                // Pass the filtered data to the autocomplete widget
                return filteredData;
            })
            .catch(error => {
                console.log(error);
            });
    };
    return Utils;
})(Utils || {});
