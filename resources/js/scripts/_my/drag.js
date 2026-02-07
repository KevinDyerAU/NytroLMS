var Drag = (function (Drag) {
    Drag.reorderQuestions = (drake, type, id) => {
        drake.on('drop', (el, target, source, sibling) => {
            const data = $(target)
                .find('li')
                .map(function () {
                    return $(this).attr('id').substring('questionNo-');
                });
        });
    };
    Drag.reorder = (
        drake,
        type,
        id,
        options = { item: null, prefixLength: 0 }
    ) => {
        drake.on('drop', (el, target, source, sibling) => {
            $('#lms_post_organizer_tab > .spinner-border').show();
            const data = $(target)
                .find(options.item)
                .map(function () {
                    return $(this).attr('id').substring(options.prefixLength);
                });
            const subtype = $(target).data('type');
            // console.log(data, type, subtype, id);
            Drag.updateDb(data, type, subtype, id);
        });
    };
    Drag.updateDb = (data, type, subtype, id) => {
        // console.log(data.toArray(), type, subtype, id);
        axios
            .post('/api/v1/' + type + '/' + id + '/reorder/' + subtype, {
                order: data.toArray(),
            })
            .then(response => {
                // console.log(response.data);
                $('#lms_post_organizer_tab > .spinner-border').hide();
            })
            .catch(error => {
                console.log(error);
                $('#lms_post_organizer_tab > .spinner-border').hide();
            });
    };
    return Drag;
})(Drag || {});
