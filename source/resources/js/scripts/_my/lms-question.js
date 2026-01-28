var Question = (function (Question) {
    // CKEditor 5 configuration removed - now using TinyMCE
    Question.Remove = (Qid, sync = false) => {
        // console.log('removing ', Qid);
        //Confirm before deleting from server
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-outline-danger ms-1',
            },
            buttonsStyling: false,
        })
            .then(function (result) {
                if (result.isConfirmed) {
                    if (sync === true) {
                        axios
                            .delete('/api/v1/questions/' + Qid)
                            .then(response => {
                                console.log(response);
                                if (result.value) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'The Question has been deleted.',
                                        customClass: {
                                            confirmButton: 'btn btn-success',
                                        },
                                    });
                                }
                            })
                            .catch(error => {
                                console.log(error);
                                toastr['error'](
                                    'Unable to delete question.',
                                    'Error!',
                                    {
                                        positionClass: 'toast-bottom-center',
                                        rtl: false,
                                    }
                                );
                            });
                    }
                    
                    let questions = $('#questions'),
                        questionsNav = questions.find('.question-navigation'),
                        questionsContent = questions.find('.question-content');

                    $('#question-' + Qid)
                        .fadeOut('slow')
                        .remove();
                    $('#questionNo' + Qid)
                        .parent()
                        .fadeOut('slow')
                        .remove();
                    questionsNav
                        .find('li.nav-item:last > a')
                        .addClass('active')
                        .attr('aria-expanded', 'true');
                    questionsContent
                        .find('.tab-pane:last')
                        .addClass('active')
                        .attr('aria-expanded', 'true');
                    if (questionsNav.find('li.nav-item').length < 1) {
                        $('#saveQuiz').hide();
                    }
                }
            })
            .catch(error => {
                console.log('error', error);
                if (error.response.status === 422) {
                    toastr['error'](error.response.data.message, 'Error!', {
                        positionClass: 'toast-bottom-center',
                        rtl: false,
                    });
                } else {
                    toastr['error']('Unable to delete question.', 'Error!', {
                        positionClass: 'toast-bottom-center',
                        rtl: false,
                    });
                }
            });
        // if (confirm('Are you sure you want to delete this Question? ' + (sync ? ' You wont be able to recover it once saved.' : ''))) {
        //     if (sync) {
        //         axios.delete('/api/v1/questions/' + Qid).then(response => {
        //             console.log(response.message);
        //             toastr['info']('Question deleted successfully!', 'Success!', {
        //                 positionClass: 'toast-bottom-center',
        //                 rtl: false
        //             });
        //         }).catch(error => {
        //             console.log(error);
        //             toastr['error']('Unable to delete question.', 'Error!', {
        //                 positionClass: 'toast-bottom-center',
        //                 rtl: false
        //             });
        //         });
        //     }
        //     let questions = $('#questions'),
        //         questionsNav = questions.find('.question-navigation'),
        //         questionsContent = questions.find('.question-content');
        //
        //     $('#question-' + Qid).fadeOut('slow').remove();
        //     $('#questionNo' + Qid).parent().fadeOut('slow').remove();
        //     questionsNav.find('li.nav-item:last > a').addClass('active').attr('aria-expanded', 'true');
        //     questionsContent.find('.tab-pane:last').addClass('active').attr('aria-expanded', 'true');
        //     if (questionsNav.find('li.nav-item').length < 1) {
        //         $('#saveQuiz').hide();
        //     }
        // }
    };
    Question.Add = () => {
        let questions = $('#questions'),
            questionsNav = questions.find('.question-navigation'),
            questionsContent = questions.find('.question-content');
        let lastNavItem = { index: 0 },
            maxValue = 0;
        questionsNav.find('li.nav-item').each(function () {
            // console.log($(this).find('input'));
            let attr = $(this).attr('id');
            let val = parseInt(attr.substring('item-'.length));
            if (val > maxValue) {
                maxValue = val;
            }
            // console.log(attr, val, maxValue);
            lastNavItem.index = parseInt($(this).find('input').val()) + 1;
            $(this)
                .find('a')
                .attr('aria-expanded', 'false')
                .removeClass('active');
        });
        let newIndex = parseInt(maxValue) + 1;
        // console.log(lastNavItem, maxValue, newIndex);
        $('#saveQuiz').show();
        let totalQuestions = questionsNav.find('li.nav-item').length;
        Question.populateNav(
            newIndex,
            lastNavItem.index,
            questionsNav,
            totalQuestions
        );
        Question.populateContent(
            questionsContent,
            newIndex,
            lastNavItem.index,
            totalQuestions
        );
        Question.initSelect2();
    };
    Question.populateNav = (newIndex, index, questionsNav, totalQuestions) => {
        let questionsNavItem = `
        <li class='nav-item' id="item-${newIndex}">
            <a
                class='nav-link active'
                id='questionNo${newIndex}'
                data-bs-toggle='pill'
                href='#question-${newIndex}'
                aria-expanded='true'
                role='tab'
            >
                <span class='fw-bold'>Question #${totalQuestions + 1}</span>
                <input type='hidden' name='question[${newIndex}][order]' value='${index}'>
            </a>
        </li>`;
        questionsNav.append(questionsNavItem);
    };
    Question.generateUUID = () => {
        let d = new Date().getTime();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
            /[xy]/g,
            function (c) {
                let r = (d + Math.random() * 16) % 16 | 0;
                d = Math.floor(d / 16);
                return (c == 'x' ? r : (r & 0x3) | 0x8).toString(16);
            }
        );
    };
    Question.populateContent = (
        questionsContent,
        newIndex,
        index,
        totalQuestions
    ) => {
        questionsContent.find('.tab-pane').each(function () {
            $(this).attr('aria-expanded', 'false').removeClass('active');
        });
        const questionUUID = Question.generateUUID();
        let questionsContentItem = `<div role='tabpanel' class='tab-pane active'
                                 id='question-${newIndex}'
                                 aria-labelledby='question'
                                 aria-expanded='true'>
                                     <div class='card'>
                                          <div class='card-body'>
                                          <h4 class='card-title'>
                                            <input type='hidden' name='question[${newIndex}][slug]' id='slug_${newIndex}'
                                                   value='${questionUUID}' />
                                            <span>Question No. ${
                                                totalQuestions + 1
                                            }</span>
                                            <button onclick='Question.Remove(${newIndex})'
                                                    type='button' class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> <span>Delete</span>
                                            </button>
                                        </h4>
                                            <div class='row'>
                                                <div class='d-flex flex-column'>
                                                  <label class='form-check-label mb-50' for='question[${newIndex}][required]'>Is Required?</label>
                                                  <div class='form-check form-switch form-check-primary'>
                                                    <input type='checkbox' class='form-check-input' name='question[${newIndex}][required]' id='question${newIndex}_required' value='1' checked />
                                                    <label class='form-check-label' for='question[${newIndex}][required]'>
                                                      <span class='switch-icon-left'><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                                                      <span class='switch-icon-right'><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></span>
                                                    </label>
                                                  </div>
                                                </div>
                                                <div class='col-md-6 col-12'>
                                                    <div class='mb-1'>
                                                        <label for='question[${newIndex}][title]' class='form-label required'>Question Title</label>
                                                        <input type='text' name='question[${newIndex}][title]' id='title_${newIndex}' class='form-control' aria-label='title' tabindex='1' autofocus />
                                                    </div>
                                                </div>
                                                <div class='col-md-6 col-12'>
                                                    <label class='form-label required' for='answer_type'>Question Type:</label>
                                                    <select data-placeholder='Select Question Type'
                                                            class='select2 form-select'
                                                            id='answer_type_${newIndex}'
                                                            name='question[${newIndex}][answer_type]' tabindex='2'
                                                            onchange='Question.typeSelected(this, ${newIndex})'>
                                                        <option value='ESSAY'>Essay</option>
                                                        <option value='FILE'>Upload</option>
                                                        <option value='SCQ'>Single Choice</option>
                                                        <option value='MCQ'>Multiple Choice</option>
                                                        <option value='SORT'>Sorting Choice</option>
                                                        <option value='MATRIX'>Matrix Choice</option>
                                                        <option value='BLANKS'>Fill in the Blank</option>
                                                        <option value='ASSESSMENT'>Assessment (Survey)</option>
                                                        <option value='SINGLE'>Short Answer</option>
                                                        <option value='TABLE'>Table</option>
                                                    </select>
                                                </div>
                                                <div class='col-12'>
                                                  <div class='mb-1' id='handle_content_${newIndex}'>
                                                    <label for='question[${newIndex}][content]' class='form-label required'>Question Content</label>
                                                    <textarea name='question[${newIndex}][content]' id='content_${newIndex}' class='form-control' tabindex='3'></textarea>
                                                  </div>
                                                </div>
                                            </div>
                                            <div class='row' id='extras_${newIndex}'>
                                            </div>
                                      </div>
                                    </div>
                                </div>`;
        questionsContent.append(questionsContentItem);

        // Initialize CKEditor 5 for the new element
        if (typeof window.initCKEditor5ById === 'function') {
            window.initCKEditor5ById('content_' + newIndex, ckEditorOptions);
        } else {
            CKEDITOR.replace('content_' + newIndex, ckEditorOptions);
        }
    };
    Question.initSelect2 = () => {
        $('.select2').each(function () {
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
    };
    Question.typeSelected = (type, index) => {
        let questionTab = $('#extras_' + index);
        // console.log(type.value, index, questionTab);
        switch (type.value) {
            case 'ESSAY':
                Question.showESSAYInput(index, questionTab);
                break;
            case 'FILE':
                Question.showFILEInput(index, questionTab);
                break;
            case 'SORT':
                Question.showSORTInput(index, questionTab);
                break;
            case 'MATRIX':
                Question.showMATRIXInput(index, questionTab);
                break;
            case 'SCQ':
                Question.showSCQInput(index, questionTab);
                break;
            case 'MCQ':
                Question.showMCQInput(index, questionTab);
                break;
            case 'TABLE':
                Question.showTABLEInput(index, questionTab);
                break;
            default:
                questionTab.html('');
                break;
        }
    };

    Question.showESSAYInput = (index, tab) => {
        let content = ``;
        tab.html(content);
    };
    Question.showBLANKSInput = (index, tab) => {
        let content = ``;
        tab.html(content);
    };
    Question.showASSESSMENTInput = (index, tab) => {
        let content = ``;
        tab.html(content);
    };
    Question.showFILEInput = (index, tab) => {
        let content = `<div class='col-md-6 col-12'>
                          <div class='mb-1'>
                            <label for='question[${index}][options][file][types_allowed]' class='form-label required'>Input File Type</label>
                            <input type='text' name='question[${index}][options][file][types_allowed]' id='files_options_${index}'
                            value='pdf,doc,docx,zip'  tabindex='4'
                            class='form-control' aria-label='Allowed File Types'/>
                          </div>
                        </div>`;
        tab.html(content);
    };
    Question.showSCQInput = (index, tab) => {
        let content = `<div class='col-12'>
                          <div class='row d-flex align-items-start' id='scq_options_${index}_holder'>
                              <div class='border-bottom-light col-md-6 col-12 option-container' id='option_${index}_1_holder' data-option='1'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][scq][1]' class='form-label required'>Add an Option</label>
                                        <input type='text' name='question[${index}][options][scq][1]'  tabindex='4' id='scq_options_${index}_1' class='form-control' aria-label='Single Choice Option'/>
                                      </div>
                                  </div>
                                  <div class='form-check form-check-inline option-correct col'>
                                      <input
                                        class='form-check-input'
                                        type='radio'
                                        name='question[${index}][correct_answer]'
                                        id='scq_correct_${index}_1'
                                        value='1'  tabindex='5'
                                      />
                                      <label class='form-check-label' for='question[${index}][correct_answer]'>Is Correct?</label>
                                  </div>
                              </div>
                          </div>
                          <div class='col-md-2 col-12 mb-50 mt-2' id='scq_options_${index}_add' >
                              <div class='mb-1'>
                                  <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect' onclick='Question.AddOption(${index}, "scq_options_${index}_holder","scq")'>
                                      <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg>Add More Options</span>
                                  </button>
                              </div>
                          </div>
                      </div>`;
        tab.html(content);
    };
    Question.showMCQInput = (index, tab) => {
        let content = `<div class='col-12'>
                          <div class='row d-flex align-items-start' id='mcq_options_${index}_holder'>
                              <div class='border-bottom-light col-md-6 col-12 option-container' id='option_${index}_1_holder' data-option='1'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][mcq][1]' class='form-label required'>Add an Option</label>
                                        <input type='text' name='question[${index}][options][mcq][1]'  tabindex='4' id='mcq_options_${index}_1' class='form-control' aria-label='Single Choice Option'/>
                                      </div>
                                  </div>
                                  <div class='form-check form-check-inline option-correct col'>
                                      <input
                                        class='form-check-input'
                                        type='checkbox'
                                        name='question[${index}][correct_answer][1]'
                                        id='mcq_correct_${index}_1'
                                        value='1'  tabindex='5'
                                      />
                                      <label class='form-check-label' for='question[${index}][correct_answer][1]'>Is Correct?</label>
                                  </div>
                              </div>
                          </div>
                          <div class='col-md-2 col-12 mb-50 mt-2' id='mcq_options_${index}_add' >
                              <div class='mb-1'>
                                  <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect' onclick='Question.AddOption(${index}, "mcq_options_${index}_holder", "mcq")'>
                                      <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg>Add More Options</span>
                                  </button>
                              </div>
                          </div>
                      </div>`;
        tab.html(content);
    };
    Question.showSORTInput = (index, tab) => {
        let content = `<div class='col-12'>
                           <div class='row d-flex align-items-start' id='sort_options_${index}_holder'>
                              <div class='border-bottom-light col-md-6 col-12 option-container' id='sort_option_${index}_1_holder' data-option='1'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][sort][1]' class='form-label required'>Add an Option</label>
                                        <div class='form-inline option-answer col-12 col-md-10'>
                                            <input type='text' name='question[${index}][options][sort][1]'  tabindex='4' id='sort_options_${index}_1_text' class='form-control' aria-label='Option Text'/>
                                        </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class='col-md-2 col-12 mb-50 mt-2' id='sort_options_${index}_add' >
                              <div class='mb-1'>
                                  <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect' onclick='Question.AddSortOption(${index}, "sort_options_${index}_holder")'>
                                      <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg>Add More Options</span>
                                  </button>
                              </div>
                          </div>
                      </div>`;
        tab.html(content);
    };
    Question.showMATRIXInput = (index, tab) => {
        let content = `<div class='col-12'>
                           <div class='row d-flex align-items-start' id='matrix_options_${index}_holder'>
                              <div class='border-bottom-light col-md-6 col-12 option-container' id='matrix_option_${index}_1_holder' data-option='1'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][matrix][1]' class='form-label required'>Add a Criterion</label>
                                        <input type='text' name='question[${index}][options][matrix][1]'  tabindex='4' id='matrix_options_${index}_1_question' class='form-control' aria-label='Answer Criterion'/>
                                      </div>
                                  </div>
                                  <div class='form-inline option-answer col-12 col-md-10'>
                                        <label for='question[${index}][correct_answer][1]' class='form-label required'>Correct Answer</label>
                                        <input type='text' name='question[${index}][correct_answer][1]' tabindex='4' id='matrix_correct_${index}_1_answer' class='form-control' aria-label='Correct Answer'/>
                                  </div>
                              </div>
                          </div>
                          <div class='col-md-2 col-12 mb-50 mt-2' id='matrix_options_${index}_add' >
                              <div class='mb-1'>
                                  <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect' onclick='Question.AddMatrixOption(${index}, "matrix_options_${index}_holder")'>
                                      <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg>Add More Options</span>
                                  </button>
                              </div>
                          </div>
                      </div>`;
        tab.html(content);
    };
    Question.showSINGLEInput = (index, tab) => {
        let content = ``;
        tab.html(content);
    };
    Question.showTABLEInput = function (index, questionTab) {
        questionTab.html(`
            <div class='col-12'>
                <div class='mb-1'>
                    <div class='d-flex justify-content-between align-items-center mb-1'>
                        <h5>Table Structure</h5>
                        <div>
                            <button type='button' class='btn btn-primary btn-sm me-1' onclick="Question.addTableCol(${index})">
                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg> Add Column
                            </button>
                            <button type='button' class='btn btn-primary btn-sm' onclick="Question.addTableRow(${index})">
                                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-plus me-50 font-small-4'><line x1='12' y1='5' x2='12' y2='19'></line><line x1='5' y1='12' x2='19' y2='12'></line></svg> Add Row
                            </button>
                        </div>
                    </div>

                    <!-- Table Question Title Field -->
                    <div class='mb-2'>
                        <label for='table-question-title-${index}' class='form-label'>Table Question Title (optional)</label>
                        <input type='text'
                                class='form-control'
                                id='table-question-title-${index}'
                                name='question[${index}][table_question_title]'
                                placeholder='e.g. Questions' />
                        <small class='text-muted'>If left empty, will default to "Question"</small>
                    </div>
                    <!-- Input Type Selection -->
                    <div class='mb-2'>
                        <h6>Input Type</h6>
                        <div class="d-flex gap-2">
                            <div class="form-check form-check-primary">
                                <input type="checkbox" class="form-check-input input-type-checkbox" name="table-input-type-${index}"
                                       id="radio-${index}" value="radio" checked
                                       onchange="Question.handleInputTypeChange(this, ${index})">
                                <label class="form-check-label" for="radio-${index}">Radio Buttons</label>
                            </div>
                            <div class="form-check form-check-primary">
                                <input type="checkbox" class="form-check-input input-type-checkbox" name="table-input-type-${index}"
                                       id="checkbox-${index}" value="checkbox"
                                       onchange="Question.handleInputTypeChange(this, ${index})">
                                <label class="form-check-label" for="checkbox-${index}">Checkboxes</label>
                            </div>
                            <div class="form-check form-check-primary">
                                <input type="checkbox" class="form-check-input input-type-checkbox" name="table-input-type-${index}"
                                       id="text-${index}" value="text"
                                       onchange="Question.handleInputTypeChange(this, ${index})">
                                <label class="form-check-label" for="text-${index}">Text Input</label>
                            </div>
                            <div class="form-check form-check-primary">
                                <input type="checkbox" class="form-check-input input-type-checkbox" name="table-input-type-${index}"
                                       id="textarea-${index}" value="textarea"
                                       onchange="Question.handleInputTypeChange(this, ${index})">
                                <label class="form-check-label" for="textarea-${index}">Textarea Input</label>
                            </div>
                        </div>
                    </div>

                    <!-- Column Configuration -->
                    <div class='mb-2'>
                        <h6>Columns</h6>
                        <div id='table-columns-container-${index}' class='mb-2'></div>
                    </div>

                    <!-- Row Configuration -->
                    <div class='mb-1'>
                        <h6>Rows</h6>
                        <div id='table-rows-container-${index}' class='mb-1'></div>
                    </div>
                    <input type='hidden' name='question[${index}][table_structure]'
                           id='table-structure-${index}'
                           value='{"input_type":"radio","columns":[],"rows":[],"table_question_title":""}'>
                </div>
            </div>
        `);
    };

    Question.AddMatrixOption = (index, id) => {
        let count =
            $('#' + id)
                .find('.option-container:last')
                .data('option') ?? 0;
        let ncount = parseInt(count) + 1;
        $('#' + id)
            .append(`<div class='border-bottom-light col-md-6 col-12 option-container' id='matrix_option_${index}_${ncount}_holder' data-option='${ncount}'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][matrix][${ncount}]' class='form-label required'>Add a Criterion</label>
                                        <input type='text' name='question[${index}][options][matrix][${ncount}]'  tabindex='4' id='matrix_options_${index}_${ncount}_question' class='form-control' aria-label='Answer Criterion'/>
                                      </div>
                                  </div>
                                  <div class='form-inline option-answer'>
                                        <label for='question[${index}][correct_answer][${ncount}]' class='form-label required'>Correct Answer</label>
                                        <div class="d-flex flex-row">
                                        <input type='text' name='question[${index}][correct_answer][${ncount}]' tabindex='4' id='matrix_correct_${index}_${ncount}_answer' class='form-control' aria-label='Correct Answer'/>

                                        <button onclick='Question.RemoveOption("matrix_option_${index}_${ncount}_holder")'
                                            type='button' class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> <span>Delete</span>
                                        </button>
                                        </div>
                                  </div>
                              </div>`);
    };
    Question.AddSortOption = (index, id) => {
        let count =
            $('#' + id)
                .find('.option-container:last')
                .data('option') ?? 0;
        let ncount = parseInt(count) + 1;
        $('#' + id)
            .append(`<div class='border-bottom-light col-md-6 col-12 option-container' id='sort_option_${index}_${ncount}_holder' data-option='${ncount}'>
                                  <div class='option col'>
                                      <div class='mb-1'>
                                        <label for='question[${index}][options][sort][${ncount}]' class='form-label required'>Add an Option</label>

                                        <div class="d-flex flex-row">
                                            <input type='text' name='question[${index}][options][sort][${ncount}]'  tabindex='4' id='sort_options_${index}_${ncount}_text' class='form-control' aria-label='Option Text'/>
                                            <button onclick='Question.RemoveOption("sort_option_${index}_${ncount}_holder")'
                                                type='button' class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> <span>Delete</span>
                                            </button>
                                        </div>
                                      </div>
                                  </div>
                              </div>`);
    };
    Question.AddOption = (index, id, type = 'scq') => {
        let count =
            $('#' + id)
                .find('.option-container:last')
                .data('option') ?? 0;
        let ncount = parseInt(count) + 1;
        // console.log($('#' + id).find('.option-correct:last').find('input').val(), ncount);
        $('#' + id)
            .append(`<div class='border-bottom-light col-md-6 col-12 option-container' data-option='${ncount}' id='option_${index}_${ncount}_holder'>
                                    <div class='option col'>
                                  <div class='mb-1'>
                                    <label for='question[${index}][options][${type}][${ncount}]' class='form-label required'>Add an Option</label>
                                    <input type='text' name='question[${index}][options][${type}][${ncount}]'  tabindex='${ncount}' id='${type}_options_${index}_${ncount}' class='form-control' aria-label='${type} option' autofocus/>
                                  </div>
                              </div>
                              <div class='form-check form-check-inline option-correct col'>
                                      <input
                                        class='form-check-input'
                                        type='${
                                            type === 'mcq'
                                                ? 'checkbox'
                                                : 'radio'
                                        }'
                                        name='question[${index}][correct_answer]${
            type === 'mcq' ? '[' + ncount + ']' : ''
        }'
                                        id='${type}_correct_${index}_${ncount}'
                                        value='${ncount}'   tabindex='${
            ncount + 1
        }'
                                      />
                                      <label class='form-check-label' for='question[${index}][correct_answer]${
            type === 'mcq' ? '[' + ncount + ']' : ''
        }'>Is Correct?</label>
                                        <button onclick='Question.RemoveOption("option_${index}_${ncount}")'
                                            type='button' class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> <span>Delete</span>
                                        </button>
                                  </div>`);
    };
    Question.RemoveOption = id => {
        console.log('removing ', id);
        $('#' + id)
            .fadeOut('slow')
            .remove();
    };

    // Table Question Functions
    Question.addTableCol = function (questionId) {
        const container = document.getElementById(
            `table-columns-container-${questionId}`
        );
        const newIndex = container.children.length;

        const columnDiv = document.createElement('div');
        columnDiv.className = 'row mb-1 table-column';
        columnDiv.innerHTML = `
            <div class='col-10'>
                <input type='text' class='form-control column-header'
                       placeholder='Column Title'
                       onchange="Question.updateTableStructure(${questionId})"
                       required>
            </div>
            <div class='col-2'>
                <button type='button' class='btn btn-danger btn-sm' onclick="Question.removeTableCol(${questionId}, ${newIndex})">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Remove
                </button>
            </div>
        `;

        container.appendChild(columnDiv);
        Question.updateTableStructure(questionId);
    };

    Question.addTableRow = function (questionId) {
        const container = document.getElementById(
            `table-rows-container-${questionId}`
        );
        const newIndex = container.children.length;

        const rowDiv = document.createElement('div');
        rowDiv.className = 'row mb-1 table-row';
        rowDiv.innerHTML = `
            <div class='col-10'>
                <input type='text' class='form-control'
                       placeholder='Enter row question'
                       onchange="Question.updateTableStructure(${questionId})"
                       required>
            </div>
            <div class='col-2'>
                <button type='button' class='btn btn-danger btn-sm' onclick="Question.removeTableRow(${questionId}, ${newIndex})">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2 me-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Remove
                </button>
            </div>
        `;

        container.appendChild(rowDiv);
        Question.updateTableStructure(questionId);
    };

    Question.removeTableCol = function (questionId, index) {
        const container = document.getElementById(
            `table-columns-container-${questionId}`
        );
        const columns = container.getElementsByClassName('table-column');
        if (columns[index]) {
            columns[index].remove();
            Question.updateTableStructure(questionId);
        }
    };

    Question.removeTableRow = function (questionId, index) {
        const container = document.getElementById(
            `table-rows-container-${questionId}`
        );
        const rows = container.getElementsByClassName('table-row');
        if (rows[index]) {
            rows[index].remove();
            Question.updateTableStructure(questionId);
        }
    };

    Question.handleInputTypeChange = function (checkbox, questionId) {
        // Uncheck all other checkboxes in the same group
        const checkboxes = document.querySelectorAll(
            `input[name="table-input-type-${questionId}"]`
        );
        checkboxes.forEach(cb => {
            if (cb !== checkbox) {
                cb.checked = false;
            }
        });

        // If the clicked checkbox was already checked, uncheck it
        if (!checkbox.checked) {
            checkbox.checked = true; // Ensure at least one is always selected
        }

        // Validate and update table structure
        Question.updateTableStructure(questionId);
    };

    Question.updateTableStructure = function (questionId) {
        // Get the selected input type
        const inputType = document.querySelector(
            `input[name="table-input-type-${questionId}"]:checked`
        )?.value;
        if (
            !inputType ||
            !['radio', 'checkbox', 'text', 'textarea'].includes(inputType)
        ) {
            toastr.error(
                'Invalid column type. Must be radio, checkbox, text or textarea.'
            );
            return;
        }

        // Get all columns
        const columns = Array.from(
            document.querySelectorAll(
                `#table-columns-container-${questionId} .column-header`
            )
        ).map(input => ({
            heading: input.value.trim(),
        }));

        // Get all rows
        const rows = Array.from(
            document.querySelectorAll(
                `#table-rows-container-${questionId} .table-row input`
            )
        ).map(input => ({
            heading: input.value.trim(),
        }));

        // Create the table structure object
        const tableStructure = {
            input_type: inputType,
            columns: columns,
            rows: rows,
        };

        // Update the hidden input
        document.getElementById(`table-structure-${questionId}`).value =
            JSON.stringify(tableStructure);
    };
    return Question;
})(Question || {});

(function (window, undefined) {
    'use strict';

    var saveQuizBtn = document.getElementById('saveQuiz');
    if (saveQuizBtn) {
        saveQuizBtn.addEventListener('click', function (event) {
            let valid = true;
            // Find all question containers by their IDs
            document
                .querySelectorAll('[id^="table-columns-container-"]')
                .forEach(function (colContainer) {
                    const questionId = colContainer.id.replace(
                        'table-columns-container-',
                        ''
                    );
                    // Validate columns
                    const columns = Array.from(
                        colContainer.querySelectorAll('.column-header')
                    ).map(input => input.value.trim());
                    if (columns.some(col => !col)) {
                        toastr.error('All columns must have a header.');
                        valid = false;
                    }
                    // Validate rows
                    const rowInputs = document.querySelectorAll(
                        `#table-rows-container-${questionId} .table-row input`
                    );
                    const rows = Array.from(rowInputs).map(input =>
                        input.value.trim()
                    );
                    if (rows.some(row => !row)) {
                        toastr.error('All rows must have a value.');
                        valid = false;
                    }
                });
            if (!valid) {
                event.preventDefault();
            }
        });
    }
})(window);
