/**
 * LMS Quiz Module
 * Handles quiz functionality including question validation, answer submission, and navigation
 * Supports multiple question types: Essay, Single Choice, Multiple Choice, Sorting, Matrix, Fill in Blanks, Assessment, Single Input, File Upload, and Table
 */
var Quiz = (function (Quiz) {
    // Initialize quiz stepper for navigation between questions
    let lmsQuiz = document.getElementById('lms-quiz'),
        numberedStepper = lmsQuiz
            ? new Stepper(lmsQuiz, {
                  linear: false,
                  animation: true,
              })
            : null;

    /**
     * Check if all questions except the current one are answered
     * Updates the button text and styling accordingly
     */
    Quiz.updateButtonState = function () {
        if (!lmsQuiz) return;

        const allSteps = lmsQuiz.querySelectorAll('.step.questionStep');
        const submittedSteps = lmsQuiz.querySelectorAll(
            '.step.questionStep.submitted'
        );
        const totalQuestions = allSteps.length;
        const answeredQuestions = submittedSteps.length;

        // Find the currently active question
        const activeContent = lmsQuiz.querySelector('.content.active');
        if (!activeContent) return;

        // Get the button for the current question
        const currentButton = activeContent.querySelector('.btn-next');
        if (!currentButton) return;

        // Get the current question ID from the button
        const currentQuestionId = currentButton.getAttribute('data-questionID');
        const lastQuestionId = currentButton.getAttribute('data-lastQuestion');

        // Check if the current question is already answered
        let currentQuestionAnswered = false;
        allSteps.forEach(step => {
            const stepQid = step.getAttribute('data-qid');
            if (
                stepQid === currentQuestionId &&
                step.classList.contains('submitted')
            ) {
                currentQuestionAnswered = true;
            }
        });

        // Always keep as "Submit Question" with default styling and ensure arrow is present
        const buttonText = currentButton.querySelector('span');
        if (buttonText && !buttonText.textContent.includes('Submit Question')) {
            buttonText.textContent = 'Submit Question';
        }
        // Ensure primary styling
        currentButton.classList.remove('btn-success');
        currentButton.classList.add('btn-primary');

        // Ensure arrow is present for navigation
        const existingArrow = currentButton.querySelector(
            'i[data-lucide="arrow-right"], svg[data-lucide="arrow-right"]'
        );
        if (!existingArrow) {
            // Add arrow if it doesn't exist
            const arrowElement = document.createElement('i');
            arrowElement.setAttribute('data-lucide', 'arrow-right');
            arrowElement.className = 'align-middle ms-sm-25 ms-0';
            currentButton.appendChild(arrowElement);
            // Re-initialize Lucide icons for the new element
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    };

    /**
     * Find the first unanswered question step
     * @returns {number} The step number (1-indexed) of the first unanswered question
     */
    function findFirstUnansweredStep() {
        if (!lmsQuiz) return 1;

        const allSteps = lmsQuiz.querySelectorAll('.step.questionStep');
        const submittedSteps = lmsQuiz.querySelectorAll(
            '.step.questionStep.submitted'
        );

        let targetStep = 1; // Default to first question

        if (submittedSteps.length > 0) {
            // User has answered at least one question
            // Find the first unanswered question
            let foundUnanswered = false;
            allSteps.forEach((step, index) => {
                if (!foundUnanswered && !step.classList.contains('submitted')) {
                    targetStep = index + 1; // Steps are 1-indexed in stepper
                    foundUnanswered = true;
                }
            });

            // If all questions are answered, go to the last one
            if (!foundUnanswered) {
                targetStep = allSteps.length;
            }
        }

        return targetStep;
    }

    // Ensure all stepper steps are clickable (remove disabled state)
    if (numberedStepper && lmsQuiz) {
        // Remove disabled classes from all step triggers
        const stepTriggers = lmsQuiz.querySelectorAll('.step-trigger');
        stepTriggers.forEach(trigger => {
            trigger.classList.remove('disabled');
            trigger.removeAttribute('disabled');
        });

        // Navigate to the first unanswered question on initial load
        numberedStepper.to(findFirstUnansweredStep());

        // Update button state on initial load
        setTimeout(Quiz.updateButtonState, 100);

        // Listen for stepper navigation events to update button state
        lmsQuiz.addEventListener('shown.bs-stepper', function (event) {
            Quiz.updateButtonState();
        });
    }

    /**
     * Submit quiz answer to the server
     * @param {Object} payload - Answer data to submit
     * @param {string} quizID - ID of the quiz
     * @param {string} topicUrl - URL to redirect to after quiz completion
     * @param {boolean} isFile - Whether the answer contains file upload
     */
    Quiz.submitAnswer = (payload, quizID, topicUrl, isFile = false) => {
        // console.log('Submit Question', payload, isFile);

        // Configure axios request settings
        let axiosConfig = {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            timeout: 10000,
            retry: 3,
            retryDelay: 10000,
        };

        // Adjust headers for file uploads
        if (isFile) {
            axiosConfig.headers['Content-Type'] = 'multipart/form-data';
            axiosConfig.headers.Accept = 'application/json';
        }

        // Extract course_id from URL parameters and add to payload
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get('course_id');

        // Add course_id to payload if present
        if (courseId) {
            payload.course_id = courseId;
        }

        // Submit answer to server via API
        axios
            .post('/api/v1/attempt/' + quizID, payload, axiosConfig)
            .then(response => {
                let res = response.data;
                // console.log(res);

                // Handle successful submission
                if (res.success === true) {
                    // Show success notification
                    toastr['success'](res.message, 'Success', {
                        closeButton: true,
                        tapToDismiss: true,
                    });

                    // Mark submitted questions as completed in the UI
                    let submitted_answers = res.data.submitted_answers;
                    $('.step.questionStep').each(function () {
                        let qId = $(this).data('qid');
                        if (submitted_answers.indexOf(qId) !== -1) {
                            // console.log(qId, submitted_answers);
                            $(this).addClass('submitted');
                        }
                    });

                    // Update button state after marking submitted answers
                    setTimeout(Quiz.updateButtonState, 100);

                    // Check if this is the last question in the quiz
                    if (res.data.next_step.last === 1) {
                        // Quiz completed - handle redirect

                        // Handle quiz completion redirects
                        if (res.data.intended_url) {
                            // Special redirect for PTR quizzes or quizzes with specific redirect URLs
                            window.location.href = res.data.intended_url;
                        } else {
                            // Standard quiz completion - redirect to topic or dashboard
                            if (topicUrl.trim() !== '') {
                                window.location.href = topicUrl;
                            } else {
                                window.location.href = '/frontend/dashboard';
                            }
                        }
                    } else {
                        // Not the last question OR last question but still have unanswered questions
                        // Navigate to the first unanswered question
                        if (numberedStepper) {
                            numberedStepper.to(findFirstUnansweredStep());
                        }
                    }
                } else {
                    // Handle server-side validation errors
                    toastr['warning']('Unable to submit your answer', 'Error', {
                        closeButton: true,
                        tapToDismiss: true,
                    });
                }
            })
            .catch(error => {
                // Handle network and server errors
                // console.log(error.response);
                if (
                    typeof error.response != 'undefined' &&
                    error.response.data
                ) {
                    // Server responded with error data
                    const response = error.response.data;
                    let message = '\n\r Reason(s): ';
                    $.each(response.message, function (key, value) {
                        message += '\n\r' + key + ': ' + value;
                    });
                    toastr['warning'](
                        'Unable to submit your answer: ' + message,
                        'Error',
                        {
                            closeButton: true,
                            tapToDismiss: true,
                        }
                    );
                }

                // Log different types of errors for debugging
                if (error.response) {
                    // The request was made and the server responded with a status code
                    // that falls out of the range of 2xx
                    console.log(error.response.data);
                    console.log(error.response.status);
                    console.log(error.response.headers);
                } else if (error.request) {
                    // The request was made but no response was received
                    // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                    // http.ClientRequest in node.js
                    console.log(error.request);
                } else {
                    // Something happened in setting up the request that triggered an Error
                    console.log('Error', error.message);
                }
                // console.log(error.config);
                // console.log(error);
                // window.jsErrorCall(error, error.errorLine);
            });
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit essay question answers
     * @param {Object} editor - TinyMCE editor instance
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateESSAY = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);
        if (editor) {
            // console.log(editor);
            // Extract content from TinyMCE
            var questionVal = tinymce.get(editor).getContent();
            // console.log('question Value', questionVal, questionVal.trim(), questionVal.length, questionVal.trim().length);

            // Validate that answer is not empty
            if (questionVal.length > 0) {
                let data = {
                    answer: questionVal,
                    question: questionID,
                    quiz: quizID,
                    user: userID,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                };
                Quiz.submitAnswer(data, quizID, topicUrl);
            } else {
                // TinyMCE validation
                toastr['warning']('Answer is required.', 'Error', {
                    closeButton: true,
                    tapToDismiss: true,
                });
            }
        }
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit Single Choice Question (SCQ) answers
     * @param {Object} editor - Not used for SCQ
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateSCQ = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);
        // Get selected radio button value
        let questionVal = $(
            'input[name="answer[' + questionID + ']"]:checked'
        ).val();

        // Validate that an option is selected
        if (!questionVal) {
            toastr['warning']('Select an option for your answer ', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            // console.log(questionVal);
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit Multiple Choice Question (MCQ) answers
     * @param {Object} editor - Not used for MCQ
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateMCQ = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);
        let questionVal = {};
        // let checkedValues = $('input[name="answer[' + questionID + ']"]:checked');

        // Collect all checked options with their MCQ IDs
        $('input[name="answer[' + questionID + ']"]:checked').each(function () {
            const key = $(this).attr('data-mcqId');
            questionVal[key] = $(this).val();
        });

        // Validate that at least one option is selected
        if (!questionVal) {
            toastr['warning']('Select an option for your answer ', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            // console.log(questionVal);
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit Sorting question answers
     * @param {Object} editor - Not used for sorting
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateSORT = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log(editor, token, questionID, quizID, userID, $("#sorting-group" + questionID));

        // Get the ordered list of items from the sorting group
        let list = $('#sorting-group' + questionID)
            .find('li.list-group-item')
            .find('h5');
        let questionVal = [];

        // Extract text content from each sorted item
        list.each(function () {
            questionVal.push($(this).text());
        });
        // console.log(list, questionVal);

        let data = {
            answer: questionVal,
            question: questionID,
            quiz: quizID,
            user: userID,
            _token: $('meta[name="csrf-token"]').attr('content'),
        };
        Quiz.submitAnswer(data, quizID, topicUrl);
    };
    /**
     * Validate and submit Matrix question answers
     * @param {Object} editor - Not used for matrix
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateMATRIX = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // Ensure questionID is a string for selector
        const questionIDStr = String(questionID);
        // Get all matrix destination slots for this question
        const destinationSlots = $('[id^="matrix_list' + questionIDStr + '_"]');
        let questionVal = {};
        let hasAnswers = false;
        let emptySlots = [];

        // Extract answers from each destination slot
        destinationSlots.each(function () {
            const $slot = $(this);
            // Try to get slot index from data attribute first, then from ID
            let slotIndex = $slot.data('slot-index');
            if (slotIndex === undefined || slotIndex === null) {
                // Extract from ID: matrix_list{questionID}_{slotIndex}
                const slotId = $slot.attr('id');
                if (slotId) {
                    const match = slotId.match(new RegExp('matrix_list' + questionIDStr + '_(\\d+)'));
                    if (match && match[1]) {
                        slotIndex = parseInt(match[1], 10);
                    }
                }
            } else {
                // Ensure it's a number if it came from data attribute
                slotIndex = parseInt(slotIndex, 10);
            }

            // Check for matrix-sort-item - check all possible locations
            // The slot is a <ul>, items are <li> children
            let $sortItem = $slot.children('li.matrix-sort-item').first();
            // If not found as direct child, try find (descendants)
            if ($sortItem.length === 0) {
                $sortItem = $slot.find('li.matrix-sort-item').first();
            }
            // If still not found, try just the class
            if ($sortItem.length === 0) {
                $sortItem = $slot.find('.matrix-sort-item').first();
            }
            // Last resort: check if any list item exists that has content (might not have class yet)
            if ($sortItem.length === 0) {
                const $anyItem = $slot.children('li').not('.matrix-empty-slot').first();
                // Check if it has a small tag with text, or any meaningful content
                if ($anyItem.length > 0) {
                    const hasSmall = $anyItem.find('small').length > 0;
                    const hasText = $anyItem.text().trim().length > 0;
                    const isEmptyPlaceholder = $anyItem.text().trim().toLowerCase().includes('drop') ||
                                              $anyItem.text().trim().toLowerCase().includes('tap');
                    if ((hasSmall || hasText) && !isEmptyPlaceholder) {
                        $sortItem = $anyItem;
                    }
                }
            }

            if ($sortItem.length > 0) {
                // Extract text from small tag (or fallback to text content)
                let answerText = $sortItem.find('small').text().trim();
                if (!answerText) {
                    // Fallback: get text but exclude button text
                    const $clone = $sortItem.clone();
                    $clone.find('button').remove();
                    answerText = $clone.text().trim();
                }
                if (!answerText) {
                    // Final fallback: get all text content
                    answerText = $sortItem.text().trim();
                    // Remove any button text that might have been included
                    $sortItem.find('button').each(function() {
                        const buttonText = $(this).text().trim();
                        if (buttonText) {
                            answerText = answerText.replace(buttonText, '').trim();
                        }
                    });
                    // Remove SVG content if any
                    answerText = answerText.replace(/\s+/g, ' ').trim();
                }

                if (answerText && !isNaN(slotIndex) && slotIndex >= 0) {
                    // Use numeric key for consistency with backend
                    questionVal[slotIndex] = answerText;
                    hasAnswers = true;
                }
            } else {
                // Track empty slots for error message
                if (!isNaN(slotIndex) && slotIndex >= 0) {
                    emptySlots.push(slotIndex + 1); // Show 1-based index to user
                }
            }
        });

        // Debug logging (temporary - remove after fixing)
        const debugInfo = {
            questionID: questionIDStr,
            destinationSlots: destinationSlots.length,
            questionVal,
            hasAnswers,
            emptySlots,
            slotsChecked: destinationSlots.map(function() {
                const $s = $(this);
                const $item = $s.children('li.matrix-sort-item').first();
                const $itemFind = $s.find('.matrix-sort-item').first();
                const $anyLi = $s.children('li').not('.matrix-empty-slot').first();
                return {
                    id: $s.attr('id'),
                    slotIndex: $s.data('slot-index'),
                    slotIndexFromId: (() => {
                        const id = $s.attr('id');
                        const match = id ? id.match(new RegExp('matrix_list' + questionIDStr + '_(\\d+)')) : null;
                        return match ? match[1] : null;
                    })(),
                    hasItemChildren: $item.length > 0,
                    hasItemFind: $itemFind.length > 0,
                    hasAnyLi: $anyLi.length > 0,
                    childrenCount: $s.children().length,
                    allChildren: $s.children().map(function() {
                        return {
                            tag: this.tagName,
                            classes: this.className,
                            text: $(this).text().trim().substring(0, 50)
                        };
                    }).get(),
                    itemText: $item.length > 0 ? $item.find('small').text().trim() : null,
                    itemHtml: $item.length > 0 ? $item[0].outerHTML.substring(0, 200) : null
                };
            }).get()
        };
        console.log('Matrix validation:', debugInfo);

        // Additional check: if we found slots but no answers, do a final sweep
        // This handles edge cases where items might be in the DOM but not detected
        if (!hasAnswers && destinationSlots.length > 0) {
            destinationSlots.each(function() {
                const $slot = $(this);
                const $allItems = $slot.children('li');
                $allItems.each(function() {
                    const $item = $(this);
                    // Skip empty placeholders
                    if ($item.hasClass('matrix-empty-slot')) return;
                    // Check if this item has any text content
                    const itemText = $item.find('small').text().trim() || $item.text().trim();
                    if (itemText && !itemText.toLowerCase().includes('drop') && !itemText.toLowerCase().includes('tap')) {
                        // Found an item with content - try to extract slot index
                        let slotIdx = $slot.data('slot-index');
                        if (slotIdx === undefined || slotIdx === null) {
                            const slotId = $slot.attr('id');
                            if (slotId) {
                                const match = slotId.match(new RegExp('matrix_list' + questionIDStr + '_(\\d+)'));
                                if (match && match[1]) {
                                    slotIdx = parseInt(match[1], 10);
                                }
                            }
                        } else {
                            slotIdx = parseInt(slotIdx, 10);
                        }
                        if (slotIdx !== undefined && !isNaN(slotIdx) && slotIdx >= 0 && !questionVal[slotIdx]) {
                            questionVal[slotIdx] = itemText;
                            hasAnswers = true;
                        }
                    }
                });
            });
        }

        if (hasAnswers && Object.keys(questionVal).length > 0) {
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        } else {
            // Show error if no matrix items found
            const errorMsg = emptySlots.length > 0
                ? 'Please fill all answer slots. Missing answers for slot(s): ' + emptySlots.join(', ')
                : 'Missing your answer. Please drag answers into the answer boxes.';
            toastr['warning'](errorMsg, 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        }
        if (event) {
            event.preventDefault();
        }
        return false;
    };

    /**
     * Validate and submit Fill in the Blanks question answers
     * @param {Object} editor - Not used for blanks
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateBLANKS = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);

        // Collect all blank input values
        let questionVal = $('input[name^="answer[' + questionID + ']"]')
            .map(function () {
                return $(this).val();
            })
            .get();
        console.log(questionVal, questionID);

        // Validate that answers are provided
        if (!questionVal) {
            toastr['warning']('Answer is required.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };

    /**
     * Validate and submit Assessment question answers
     * @param {Object} editor - Not used for assessment
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateASSESSMENT = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);

        // Get selected assessment value
        let questionVal = $(
            'input[name="answer[' + questionID + ']"]:checked'
        ).val();

        // Validate that an answer is selected
        if (!questionVal) {
            toastr['warning']('Answer is required.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };

    /**
     * Validate and submit Single Input question answers
     * @param {Object} editor - Not used for single input
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateSINGLE = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);

        // Get single input value
        let questionVal = $('input[name="answer[' + questionID + ']"]').val();

        // Validate that answer is provided
        if (!questionVal) {
            toastr['warning']('Answer is required.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit File Upload question answers
     * @param {Object} editor - Not used for file upload
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateFILE = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        // console.log('Next Question', questionID, quizID, userID);
        // const questionInput = $('input[name="answer[' + questionID + ']"]');
        // let formats = questionInput.data('format');
        // // console.log(formats);
        // if(typeof formats == "undefined" || formats.length < 1){
        //     formats = "pdf|doc|docx|zip|jpg|jpeg|xls|xlsx|ppt|pptx|png";
        // }
        // console.log('validating #quizHolder', questionID);
        // questionInput.valid();
        // console.log('validated');
        // event.preventDefault();
        // return false;

        // Prepare FormData for file upload
        let formData = new FormData();
        const imageFile = document.getElementById('file_' + questionID);
        let fileUpload = null;

        // Handle both hidden file inputs and regular file inputs
        if (imageFile.getAttribute('type') === 'hidden') {
            fileUpload = imageFile.value;
        } else {
            fileUpload = imageFile.files[0];
        }
        // console.log(fileUpload);

        // Validate that a file is selected
        if (!fileUpload) {
            toastr['warning']('You need to upload file.', 'Error', {
                closeButton: true,
                tapToDismiss: true,
            });
        } else {
            // Build FormData with file and metadata
            formData.append('file', fileUpload);
            formData.append('answer', '');
            formData.append('question', questionID);
            formData.append('quiz', quizID);
            formData.append('user', userID);
            formData.append(
                '_token',
                $('meta[name="csrf-token"]').attr('content')
            );
            // for (var key of formData.entries()) {
            //     console.log(key[0] + ', ' + key[1]);
            // }
            Quiz.submitAnswer(formData, quizID, topicUrl, true);
        }
        event.preventDefault();
        return false;
    };
    /**
     * Validate and submit Table question answers
     * Supports multiple input types: text inputs, textareas, checkboxes, and radio buttons
     * @param {Object} editor - Not used for table
     * @param {string} token - CSRF token
     * @param {string} questionID - Question ID
     * @param {string} quizID - Quiz ID
     * @param {string} userID - User ID
     * @param {string} topicUrl - Topic URL for redirect
     */
    Quiz.validateTABLE = (
        editor,
        token,
        questionID,
        quizID,
        userID,
        topicUrl
    ) => {
        let questionVal = {};
        let hasTextInputs = false;

        // Check if we have textarea or text inputs for this question
        hasTextInputs =
            $(
                'textarea[name^="answer[' +
                    questionID +
                    ']"], input[type="text"][name^="answer[' +
                    questionID +
                    ']"]'
            ).length > 0;

        if (hasTextInputs) {
            // Handle textarea and text inputs - extract row/column data
            $(
                'textarea[name^="answer[' +
                    questionID +
                    ']"], input[type="text"][name^="answer[' +
                    questionID +
                    ']"]'
            ).each(function () {
                const name = $(this).attr('name');
                const value = $(this).val();
                const matches = name.match(/\[(\d+)\]\[(\d+)\]$/);
                if (matches) {
                    const rowIndex = matches[1];
                    const colIndex = matches[2];
                    if (!questionVal[rowIndex]) {
                        questionVal[rowIndex] = {};
                    }
                    questionVal[rowIndex][colIndex] = value;
                }
            });
        } else {
            // Check if this is a checkbox question
            const isCheckbox =
                $('input[type="checkbox"][name^="answer[' + questionID + ']"]')
                    .length > 0;

            if (isCheckbox) {
                // Handle checkbox inputs - multiple selections per row
                $(
                    'input[type="checkbox"][name^="answer[' +
                        questionID +
                        ']"]:checked'
                ).each(function () {
                    const name = $(this).attr('name');
                    // Extract row and column indices from name like "answer[19][1][2]"
                    const matches = name.match(/\[(\d+)\]\[(\d+)\]\[(\d+)\]$/);
                    if (matches) {
                        const rowIndex = matches[2];
                        if (!questionVal[rowIndex]) {
                            questionVal[rowIndex] = [];
                        }
                        questionVal[rowIndex].push(matches[3]); // Store column index
                    }
                });
            } else {
                // Handle radio inputs - single selection per row
                $(
                    'input[type="radio"][name^="answer[' +
                        questionID +
                        ']"]:checked'
                ).each(function () {
                    const name = $(this).attr('name');
                    // Extract row index from name like "answer[15][0]"
                    const rowIndex = name.match(/\[(\d+)\]$/)[1];
                    questionVal[rowIndex] = $(this).val();
                });
            }
        }

        // Validate that answers are provided
        if (Object.keys(questionVal).length === 0) {
            toastr['warning'](
                'Please provide answers for the question',
                'Error',
                {
                    closeButton: true,
                    tapToDismiss: true,
                }
            );
        } else {
            let data = {
                answer: questionVal,
                question: questionID,
                quiz: quizID,
                user: userID,
                _token: $('meta[name="csrf-token"]').attr('content'),
            };
            Quiz.submitAnswer(data, quizID, topicUrl);
        }
        event.preventDefault();
        return false;
    };
    /**
     * Navigate to previous question in the quiz
     * @param {number} order - The step order to navigate to
     */
    Quiz.previous = order => {
        // console.log('Previous Question');
        if (order >= 0) {
            // let currStep = parseInt($('#lms-quiz').data('last-question'));
            // // console.log(currStep);
            // var stepper = new Stepper(document.getElementById('lms-quiz'));
            // stepper.to(currStep - 1);
            // console.log(order, currStep, currStep - 1);
            if (numberedStepper) {
                numberedStepper.to(order);
            }
        }
        event.preventDefault();
        return false;
    };
    return Quiz;
})(Quiz || {});

/**
 * Bootstrap Stepper and Select2 Initialization
 * Handles stepper navigation visual feedback and select2 dropdown initialization
 */
(function (window, undefined) {
    'use strict';

    // Initialize Bootstrap Stepper components
    var bsStepper = document.querySelectorAll('.bs-stepper'),
        select = $('.select2');

    // Add visual feedback for stepper navigation
    if (typeof bsStepper !== 'undefined' && bsStepper !== null) {
        for (var el = 0; el < bsStepper.length; ++el) {
            bsStepper[el].addEventListener('show.bs-stepper', function (event) {
                var index = event.detail.indexStep;
                var numberOfSteps = $(event.target).find('.step').length - 1;
                var line = $(event.target).find('.step');

                // Mark completed steps as crossed
                for (var i = 0; i < index; i++) {
                    line[i].classList.add('crossed');

                    for (var j = index; j < numberOfSteps; j++) {
                        line[j].classList.remove('crossed');
                    }
                }

                // Handle reset to first step
                if (event.detail.to == 0) {
                    for (var k = index; k < numberOfSteps; k++) {
                        line[k].classList.remove('crossed');
                    }
                    line[0].classList.remove('crossed');
                }
            });
        }
    }

    // Initialize Select2 dropdowns with custom styling
    select.each(function () {
        var $this = $(this);
        $this.wrap('<div class="position-relative form-select-control"></div>');
        $this.select2({
            placeholder: 'Select value',
            dropdownParent: $this.parent(),
            allowClear: true,
        });
    });
})(window);
