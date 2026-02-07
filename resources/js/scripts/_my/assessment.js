var Assessment = (function (Assessment) {
    let baseAPIURL = '/api/v1/assessments/';
    Assessment.MarkAnswer = (questionID, attemptID, Type) => {
        Assessment.postAttempt(attemptID, 'answer', {
            question: questionID,
            status: Type,
        });
    };
    Assessment.SubmitComment = (editor, questionID, attemptID) => {
        let comment = editor.getData().trim();
        // console.log(comment, comment.length);
        if (comment.length < 1) {
            $('#comment_' + questionID + '_alert').fadeIn();
        } else {
            $('#comment_' + questionID + '_alert').fadeOut();
            Assessment.postAttempt(attemptID, 'answer', {
                question: questionID,
                comment: comment,
            });
        }
    };
    Assessment.ToggleCommentAnswer = commentDiv => {
        $('#' + commentDiv).slideToggle();
    };

    Assessment.MarkAttempt = (editor, attemptID, Type) => {
        let feedback = editor.getData().trim();
        if (feedback.length < 1) {
            $('#feedback_' + attemptID + '_alert').fadeIn();
        } else {
            $('#feedback_' + attemptID + '_alert').fadeOut();
            Assessment.postAttempt(
                attemptID,
                'feedback',
                { status: Type, feedback: feedback },
                true
            );
        }
    };
    Assessment.EmailResults = attemptID => {
        // console.log(attemptID);
        Assessment.postAttempt(attemptID, 'email');
    };
    Assessment.returnToStudent = attemptID => {
        Assessment.postAttempt(attemptID, 'return', {}, true);
    };

    Assessment.postAttempt = async (attempt, type, data, redirect = false) => {
        if (data) {
            data.assisted = $('#assisted').is(':checked') ? 1 : 0;
        }
        // console.log(attempt, type, data);
        return await axios
            .post(baseAPIURL + attempt + '/' + type, data)
            .then(response => {
                let res = response.data;
                // console.log(res);
                if (res.success === true) {
                    toastr['info'](res.message, 'Information', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                    if (res.data.results) {
                        let $ex = `<div class='alert alert-${
                            res.data.results[data.question].status === 'correct'
                                ? 'info'
                                : 'danger'
                        } p-1 me-1'><p>Marked as
                            <strong>${
                                res.data.results[data.question].status
                            }</strong>`;
                        if (res.data.results[data.question].comment) {
                            $ex += `, with comments:
                                <span>${
                                    res.data.results[data.question].comment
                                }</span>`;
                        }
                        $ex += `</p></div>`;
                        $('#existing' + data.question).html($ex);
                    }

                    if (redirect) {
                        const status = new URLSearchParams(
                            window.location.search
                        ).get('status');
                        const redirectTo = new URLSearchParams(
                            window.location.search
                        ).get('redirect');
                        if (redirectTo) {
                            console.log(redirectTo.toLowerCase(), res.data);
                        }
                        if (
                            redirectTo &&
                            redirectTo.toLowerCase() === 'student'
                        ) {
                            window.location.href =
                                '/account-manager/students/' +
                                res.data.user_id +
                                '#student-assessments' +
                                (status ? '?status=' + status : '');
                        } else {
                            window.location.href =
                                '/assessments' +
                                (status ? '?status=' + status : '');
                        }
                    }
                } else {
                    toastr['warning']('Unable to submit ' + type, 'Error', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                }
            })
            .catch(error => {
                console.log(error);
                if (typeof error.response !== 'undefined') {
                    const response = error.response.data;
                    let message = '\n\r Reason(s): ';
                    $.each(
                        response.message ? response.message : response.errors,
                        function (key, value) {
                            message +=
                                '\n\r' +
                                (typeof key !== 'number' ? key + ': ' : '') +
                                (value.message ? value.message : value);
                        }
                    );
                    toastr['warning'](
                        'Unable to submit ' + type + ':' + message,
                        'Error',
                        {
                            closeButton: true,
                            tapToDismiss: true,
                        }
                    );
                }
            });
    };

    return Assessment;
})(Assessment || {});

(function (window, undefined) {
    'use strict';
})(window);
