// Lesson unlock/lock functionality
$(document).ready(function () {
    // Initialize feather icons for the new buttons
    // if (typeof feather !== 'undefined') {
    //     feather.replace();
    // }

    // Handle unlock button click
    $(document).on('click', '.lesson-unlock', function () {
        const lessonId = $(this).data('lesson-id');
        const button = $(this);

        $.ajax({
            url: `/lessons/${lessonId}/unlock`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                if (response.success) {
                    // Update the release date text
                    const releaseDateText = button
                        .closest('div')
                        .find('i[data-lucide="calendar"]')
                        .parent();
                    releaseDateText.html(
                        `<i data-lucide='calendar'></i> Available On: ${response.release_date}`
                    );

                    // Replace unlock button with lock button
                    button
                        .removeClass('btn-primary lesson-unlock')
                        .addClass('btn-warning lesson-lock')
                        .html('<i data-lucide="lock"></i> Lock');

                    // Reinitialize feather icons
                    // if (typeof feather !== 'undefined') {
                    //     feather.replace();
                    // }

                    // Show success message
                    toastr.success('Lesson unlocked successfully');
                }
            },
            error: function () {
                toastr.error('Failed to unlock lesson');
            },
        });
    });

    // Handle lock button click
    $(document).on('click', '.lesson-lock', function () {
        const lessonId = $(this).data('lesson-id');
        const button = $(this);

        $.ajax({
            url: `/lessons/${lessonId}/lock`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                if (response.success) {
                    // Update the release date text
                    const releaseDateText = button
                        .closest('div')
                        .find('i[data-lucide="calendar"]')
                        .parent();
                    releaseDateText.html(
                        `<i data-lucide='calendar'></i> Available On: ${response.release_date}`
                    );

                    // Replace lock button with unlock button
                    button
                        .removeClass('btn-warning lesson-lock')
                        .addClass('btn-primary lesson-unlock')
                        .html('<i data-lucide="unlock"></i> Unlock');

                    // Reinitialize feather icons
                    // if (typeof feather !== 'undefined') {
                    //     feather.replace();
                    // }

                    // Show success message
                    toastr.success('Lesson locked successfully');
                }
            },
            error: function () {
                toastr.error('Failed to lock lesson');
            },
        });
    });
});
