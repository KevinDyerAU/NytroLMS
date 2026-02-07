<div class='card'>
    <div class="card-body">
        <h4 class="card-title">
            <input type='hidden' name='question[{{ $question['id'] }}][slug]' id='slug_{{ $question['id'] }}'
                value='{{ $question['slug'] ?? \Str::uuid() }}' />

            <span>Question No. {{ $question['index'] }}</span>

            <button onclick='Question.Remove({{ $question['id'] }}, true)' type="button"
                class="btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light">
                <i data-lucide="x-square" class="me-25"></i> <span>Delete</span>
            </button>
        </h4>
        <div class='row'>
            <div class="d-flex flex-column">
                <label class="form-check-label mb-50" for="question[{{ $question['id'] }}][required]">Is
                    Required?</label>
                <div class="form-check form-switch form-check-primary">
                    <input type="checkbox" class="form-check-input" name="question[{{ $question['id'] }}][required]"
                        id="question{{ $question['id'] }}_required" value='1'
                        {{ isset($question['required']) && $question['required'] === 1 ? 'checked' : '' }} />
                    <label class="form-check-label" for="question[{{ $question['id'] }}][required]">
                        <span class="switch-icon-left"><i data-lucide="check"></i></span>
                        <span class="switch-icon-right"><i data-lucide="x"></i></span>
                    </label>
                </div>
            </div>
            <div class='col-md-6 col-12'>
                <div class='mb-1'>
                    <label for='question[{{ $question['id'] }}][title]' class='form-label required'>Question
                        Title</label>
                    <input type='text' name='question[{{ $question['id'] }}][title]'
                        id='title_{{ $question['id'] }}' value='{{ $question['title'] }}' class='form-control'
                        aria-label='title' tabindex='1' autofocus />
                </div>
            </div>
            <div class='col-md-6 col-12'>
                <label class='form-label required' for='question[{{ $question['id'] }}][answer_type]'>Question
                    Type:</label>
                <select data-placeholder='Select Question Type' class='select2 form-select'
                    id='answer_type_{{ $question['id'] }}' name='question[{{ $question['id'] }}][answer_type]'
                    tabindex='2' onchange='Question.typeSelected(this, {{ $question['id'] }})'>
                    <option value='ESSAY' {{ $question['answer_type'] === 'ESSAY' ? 'selected=selected' : '' }}>Essay
                    </option>
                    <option value='FILE' {{ $question['answer_type'] === 'FILE' ? 'selected=selected' : '' }}>Upload
                    </option>
                    <option value='SCQ' {{ $question['answer_type'] === 'SCQ' ? 'selected=selected' : '' }}>Single
                        Choice
                    </option>
                    <option value='MCQ' {{ $question['answer_type'] === 'MCQ' ? 'selected=selected' : '' }}>Multiple
                        Choice
                    </option>
                    <option value='SORT' {{ $question['answer_type'] === 'SORT' ? 'selected=selected' : '' }}>Sorting
                        Choice
                    </option>
                    <option value='MATRIX' {{ $question['answer_type'] === 'MATRIX' ? 'selected=selected' : '' }}>Matrix
                        Choice
                    </option>
                    <option value='BLANKS' {{ $question['answer_type'] === 'BLANKS' ? 'selected=selected' : '' }}>Fill in
                        the
                        Blank
                    </option>
                    <option value='ASSESSMENT' {{ $question['answer_type'] === 'ASSESSMENT' ? 'selected=selected' : '' }}>
                        Assessment
                        (Survey)
                    </option>
                    <option value='SINGLE' {{ $question['answer_type'] === 'SINGLE' ? 'selected=selected' : '' }}>Short
                        Answer
                    </option>
                    <option value='TABLE' {{ $question['answer_type'] === 'TABLE' ? 'selected=selected' : '' }}>Table
                        Question
                    </option>
                </select>
            </div>
            <div class='col-12'>
                <div class='mb-1' id='handle_content_{{ $question['id'] }}'>
                    <label for='question[{{ $question['id'] }}][content]' class='form-label required'>Question
                        Content</label>
                    <textarea name='question[{{ $question['id'] }}][content]' id='content_{{ $question['id'] }}' class='form-control'
                        tabindex='3'>{!! $question['content'] !!} </textarea>
                </div>
            </div>
        </div>
        <div class='row' id='extras_{{ $question['id'] }}'>
            @switch($question['answer_type'])
                @case('TABLE')
                    <div class='col-12'>
                        <div class='mb-1'>
                            <div class='d-flex justify-content-between align-items-center mb-1'>
                                <h5>Table Structure</h5>
                                <div>
                                    <button type='button' class='btn btn-primary btn-sm me-1'
                                        onclick="Question.addTableCol({{ $question['id'] }})">
                                        <i data-lucide='plus' class='me-25'></i> Add Column
                                    </button>
                                    <button type='button' class='btn btn-primary btn-sm'
                                        onclick="Question.addTableRow({{ $question['id'] }})">
                                        <i data-lucide='plus' class='me-25'></i> Add Row
                                    </button>
                                </div>
                            </div>

                            @php
                                $tableStructure = isset($question['table_structure'])
                                    ? (is_string($question['table_structure'])
                                        ? json_decode($question['table_structure'], true)
                                        : $question['table_structure'])
                                    : ['input_type' => 'radio', 'columns' => [], 'rows' => []];
                            @endphp

                            <!-- Table Question Title Field -->
                            <div class='mb-2'>
                                <label for='table-question-title-{{ $question['id'] }}' class='form-label'>Table Question Title
                                    (optional)</label>
                                <input type='text' class='form-control' id='table-question-title-{{ $question['id'] }}'
                                    name='question[{{ $question['id'] }}][table_question_title]'
                                    value='{{ isset($tableStructure['table_question_title']) ? $tableStructure['table_question_title'] : '' }}'
                                    placeholder='e.g. Questions' />
                                <small class='text-muted'>If left empty, will default to "Question"</small>
                            </div>

                            <!-- Input Type Selection -->
                            <div class='mb-2'>
                                <h6>Input Type</h6>
                                <div class="d-flex gap-2">
                                    <div class="form-check form-check-primary">
                                        <input type="checkbox" class="form-check-input input-type-checkbox"
                                            name="table-input-type-{{ $question['id'] }}" id="radio-{{ $question['id'] }}"
                                            value="radio"
                                            {{ ($tableStructure['input_type'] ?? '') === 'radio' ? 'checked' : '' }}
                                            onchange="Question.handleInputTypeChange(this, {{ $question['id'] }})">
                                        <label class="form-check-label" for="radio-{{ $question['id'] }}">Radio
                                            Buttons</label>
                                    </div>
                                    <div class="form-check form-check-primary">
                                        <input type="checkbox" class="form-check-input input-type-checkbox"
                                            name="table-input-type-{{ $question['id'] }}"
                                            id="checkbox-{{ $question['id'] }}" value="checkbox"
                                            {{ ($tableStructure['input_type'] ?? '') === 'checkbox' ? 'checked' : '' }}
                                            onchange="Question.handleInputTypeChange(this, {{ $question['id'] }})">
                                        <label class="form-check-label"
                                            for="checkbox-{{ $question['id'] }}">Checkboxes</label>
                                    </div>
                                    <div class="form-check form-check-primary">
                                        <input type="checkbox" class="form-check-input input-type-checkbox"
                                            name="table-input-type-{{ $question['id'] }}" id="text-{{ $question['id'] }}"
                                            value="text"
                                            {{ ($tableStructure['input_type'] ?? '') === 'text' ? 'checked' : '' }}
                                            onchange="Question.handleInputTypeChange(this, {{ $question['id'] }})">
                                        <label class="form-check-label" for="text-{{ $question['id'] }}">Text Input</label>
                                    </div>
                                    <div class="form-check form-check-primary">
                                        <input type="checkbox" class="form-check-input input-type-checkbox"
                                            name="table-input-type-{{ $question['id'] }}"
                                            id="textarea-{{ $question['id'] }}" value="textarea"
                                            {{ ($tableStructure['input_type'] ?? '') === 'textarea' ? 'checked' : '' }}
                                            onchange="Question.handleInputTypeChange(this, {{ $question['id'] }})">
                                        <label class="form-check-label" for="textarea-{{ $question['id'] }}">Textarea
                                            Input</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Column Configuration -->
                            <div class='mb-2'>
                                <h6>Columns</h6>
                                <div id='table-columns-container-{{ $question['id'] }}' class='mb-2'>
                                    @foreach ($tableStructure['columns'] ?? [] as $index => $column)
                                        <div class='row mb-1 table-column'>
                                            <div class='col-10'>
                                                <input type='text' class='form-control column-header'
                                                    value="{{ $column['heading'] ?? '' }}" placeholder='Column Title'
                                                    onchange="Question.updateTableStructure({{ $question['id'] }})">
                                            </div>
                                            <div class='col-2'>
                                                <button type='button' class='btn btn-danger btn-sm'
                                                    onclick="Question.removeTableCol({{ $question['id'] }}, {{ $index }})">
                                                    <i data-lucide='trash-2'></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Row Configuration -->
                            <div class='mb-1'>
                                <h6>Rows</h6>
                                <div id='table-rows-container-{{ $question['id'] }}' class='mb-1'>
                                    @foreach ($tableStructure['rows'] ?? [] as $index => $row)
                                        <div class='row mb-1 table-row'>
                                            <div class='col-10'>
                                                <input type='text' class='form-control'
                                                    value="{{ $row['heading'] ?? '' }}" placeholder='Enter row question'
                                                    onchange="Question.updateTableStructure({{ $question['id'] }})">
                                            </div>
                                            <div class='col-2'>
                                                <button type='button' class='btn btn-danger btn-sm'
                                                    onclick="Question.removeTableRow({{ $question['id'] }}, {{ $index }})">
                                                    <i data-lucide='trash-2'></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <input type='hidden' name='question[{{ $question['id'] }}][table_structure]'
                                id='table-structure-{{ $question['id'] }}' value='{{ json_encode($tableStructure) }}'>
                        </div>
                    </div>
                @break

                @case('FILE')
                    @if (is_countable($question['options']) && count($question['options']) > 0)
                        <div class='col-md-6 col-12'>
                            <div class='mb-1'>
                                <label for='question[{{ $question['id'] }}][options][file][types_allowed]'
                                    class='form-label required'>Input
                                    File Type</label>
                                <input type='text' name='question[{{ $question['id'] }}][options][file][types_allowed]'
                                    id='files_options_{{ $question['id'] }}'
                                    value='{{ $question['options']['file']['types_allowed'] }}' tabindex='4'
                                    class='form-control' aria-label='Allowed File Types' />
                            </div>
                        </div>
                    @endif
                @break

                @case('SORT')
                    <div class='col-12'>
                        <p>When creating the question, the order of the answers in the backend will be considered the
                            correct order.</p>
                        <div class='row d-flex align-items-start' id='sort_options_{{ $question['id'] }}_holder'>
                            @if (is_countable($question['options']) && count($question['options']) > 0)
                                @foreach ($question['options']['sort'] as $key => $opt)
                                    <div class='border-bottom-light col-md-6 col-12 option-container'
                                        id='sort_option_{{ $question['id'] }}_{{ $key }}_holder'
                                        data-option='{{ $key }}'>
                                        <div class='option col'>
                                            <div class='mb-1'>

                                                <label
                                                    for='question[{{ $question['id'] }}][options][sort][{{ $key }}]'
                                                    class='form-label required'>{{ $key }}: Add an Option</label>
                                                <div class="d-flex flex-row">
                                                    <input type='text'
                                                        name='question[{{ $question['id'] }}][options][sort][{{ $key }}]'
                                                        tabindex='4' value="{{ $opt }}"
                                                        id='sort_options_{{ $question['id'] }}_{{ $key }}_text'
                                                        class='form-control' aria-label='Option Text' />

                                                    <button
                                                        onclick='Question.RemoveOption("sort_option_{{ $question['id'] }}_{{ $key }}_holder")'
                                                        type='button'
                                                        class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                                        <i data-lucide='x-square' class='me-25'></i>
                                                        <span>Delete</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        {{--                                    <input type='hidden' --}}
                                        {{--                                           name='question[{{ $question['id'] }}][correct_answer][{{ $key }}]' --}}
                                        {{--                                           value="{{ $opt }}" tabindex='4' --}}
                                        {{--                                           id='sort_correct_{{ $question['id'] }}_{{ $key }}_order' --}}
                                        {{--                                           class='form-control' aria-label='Option Order'/> --}}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class='col-md-2 col-12 mb-50 mt-2' id='sort_options_{{ $question['id'] }}_add'>
                            <div class='mb-1'>
                                <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect'
                                    onclick='Question.AddSortOption({{ $question['id'] }}, "sort_options_{{ $question['id'] }}_holder")'>
                                    <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'
                                            stroke-linecap='round' stroke-linejoin='round'
                                            class='feather feather-plus me-50 font-small-4'>
                                            <line x1='12' y1='5' x2='12' y2='19'></line>
                                            <line x1='5' y1='12' x2='19' y2='12'></line>
                                        </svg>Add More Options</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @break

                @case('MATRIX')
                    <div class='col-12'>
                        <p>Each sort element must be unique, and only one-to-one associations are supported.</p>
                        <div class='row d-flex align-items-start' id='matrix_options_{{ $question['id'] }}_holder'>
                            @if (is_countable($question['options']) && count($question['options']) > 0)
                                @foreach ($question['options']['matrix'] as $key => $opt)
                                    @php
                                        $correct_answer = is_array($question['correct_answer'])
                                            ? $question['correct_answer']
                                            : json_decode($question['correct_answer'], true);
                                    @endphp
                                    <div class='border-bottom-light col-md-6 col-12 option-container'
                                        id='matrix_option_{{ $question['id'] }}_{{ $key }}_holder'
                                        data-option='{{ $key }}'>
                                        <div class='option col'>
                                            <div class='mb-1'>
                                                <label
                                                    for='question[{{ $question['id'] }}][options][matrix][{{ $key }}]'
                                                    class='form-label required'>Add a Criterion</label>
                                                <input type='text'
                                                    name='question[{{ $question['id'] }}][options][matrix][{{ $key }}]'
                                                    tabindex='4' value="{{ $opt }}"
                                                    id='matrix_options_{{ $question['id'] }}_{{ $key }}_text'
                                                    class='form-control' aria-label='Answer Criterion' />
                                            </div>
                                        </div>
                                        <div class='form-inline option-answer'>
                                            <label
                                                for='question[{{ $question['id'] }}][correct_answer][{{ $key }}]'
                                                class='form-label required'>Correct Answer</label>
                                            <div class="d-flex flex-row">
                                                <input type='text'
                                                    name='question[{{ $question['id'] }}][correct_answer][{{ $key }}]'
                                                    value="{{ !empty($correct_answer[$key]) ? $correct_answer[$key] : $key }}"
                                                    tabindex='4'
                                                    id='matrix_correct_{{ $question['id'] }}_{{ $key }}_answer'
                                                    class='form-control' aria-label='Correct Answer' />
                                                <button
                                                    onclick='Question.RemoveOption("matrix_option_{{ $question['id'] }}_{{ $key }}_holder")'
                                                    type='button'
                                                    class='btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light'>
                                                    <i data-lucide='x-square' class='me-25'></i> <span>Delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class='col-md-2 col-12 mb-50 mt-2' id='matrix_options_{{ $question['id'] }}_add'>
                            <div class='mb-1'>
                                <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect'
                                    onclick='Question.AddMatrixOption({{ $question['id'] }}, "matrix_options_{{ $question['id'] }}_holder")'>
                                    <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'
                                            stroke-linecap='round' stroke-linejoin='round'
                                            class='feather feather-plus me-50 font-small-4'>
                                            <line x1='12' y1='5' x2='12' y2='19'></line>
                                            <line x1='5' y1='12' x2='19' y2='12'></line>
                                        </svg>Add More Options</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @break

                @case('SCQ')
                    <div class='col-12'>
                        <div class='row d-flex align-items-start' id='scq_options_{{ $question['id'] }}_holder'>
                            @if (is_countable($question['options']) && count($question['options']) > 0)
                                @foreach ($question['options']['scq'] as $key => $scq)
                                    <div class="border-bottom-light col-md-6 col-12 option-container"
                                        data-option="{{ $key }}"
                                        id="option_{{ $question['id'] }}_{{ $key }}">
                                        <div class='option col'>
                                            <div class='mb-1'>
                                                <label
                                                    for='question[{{ $question['id'] }}][options][scq][{{ $key }}]'
                                                    class='form-label required'>Add an Option</label>
                                                <input type='text'
                                                    name='question[{{ $question['id'] }}][options][scq][{{ $key }}]'
                                                    tabindex='{{ 4 + intval($key) }}'
                                                    id='scq_options_{{ $question['id'] }}_{{ $key }}'
                                                    class='form-control' value='{{ $scq }}'
                                                    aria-label='Single Choice Option' />
                                            </div>
                                        </div>
                                        <div class='form-check form-check-inline option-correct col'>
                                            <input class='form-check-input' type='radio'
                                                name='question[{{ $question['id'] }}][correct_answer]'
                                                id='scq_correct_{{ $question['id'] }}_{{ $key }}'
                                                value='{{ $key }}' {{--                                            data-cans='{{ $question['correct_answer'] }}' --}} {{--                                            data-cani='{{ intval($question['correct_answer']) }}' --}}
                                                {{--                                            data-key='{{ intval($key) }}' --}} tabindex='{{ 5 + intval($key) }}'
                                                {{ isset($question['correct_answer']) && intval($question['correct_answer']) === intval($key) ? 'checked' : '' }} />
                                            <label class='form-check-label'
                                                for='question[{{ $question['id'] }}][correct_answer]'>Is
                                                Correct?</label>
                                            <button
                                                onclick='Question.RemoveOption("option_{{ $question['id'] }}_{{ $key }}")'
                                                type="button"
                                                class="btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light">
                                                <i data-lucide="x-square" class="me-25"></i> <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class='col-md-2 col-12 mb-50 mt-2' id='scq_options_{{ $question['id'] }}_add'>
                            <div class='mb-1'>
                                <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect'
                                    onclick='Question.AddOption({{ $question['id'] }}, "scq_options_{{ $question['id'] }}_holder", "scq")'>
                                    <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'
                                            stroke-linecap='round' stroke-linejoin='round'
                                            class='feather feather-plus me-50 font-small-4'>
                                            <line x1='12' y1='5' x2='12' y2='19'></line>
                                            <line x1='5' y1='12' x2='19' y2='12'></line>
                                        </svg>Add More Options</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @break

                @case('MCQ')
                    <div class='col-12'>
                        <div class='row d-flex align-items-start' id='mcq_options_{{ $question['id'] }}_holder'>
                            @if (is_countable($question['options']) && count($question['options']) > 0)
                                @foreach ($question['options']['mcq'] as $key => $mcq)
                                    @php
                                        $correct_answer = is_array($question['correct_answer'])
                                            ? $question['correct_answer']
                                            : json_decode($question['correct_answer'], true);
                                    @endphp
                                    <div class="border-bottom-light col-md-6 col-12 option-container"
                                        data-option="{{ $key }}"
                                        id="option_{{ $question['id'] }}_{{ $key }}">
                                        <div class='option col'>
                                            <div class='mb-1'>
                                                <label
                                                    for='question[{{ $question['id'] }}][options][mcq][{{ $key }}]'
                                                    class='form-label required'>Add an Option</label>
                                                <input type='text'
                                                    name='question[{{ $question['id'] }}][options][mcq][{{ $key }}]'
                                                    tabindex='{{ 4 + intval($key) }}'
                                                    id='mcq_options_{{ $question['id'] }}_{{ $key }}'
                                                    class='form-control' value='{{ $mcq }}'
                                                    aria-label='Single Choice Option' />
                                            </div>
                                        </div>
                                        <div class='form-check form-check-inline option-correct col'>
                                            <input class='form-check-input' type='checkbox'
                                                name='question[{{ $question['id'] }}][correct_answer][{{ $key }}]'
                                                id='mcq_correct_{{ $question['id'] }}_{{ $key }}'
                                                value='{{ $correct_answer[$key] ?? $key }}' {{--                                            data-cans='{{ $question['correct_answer'] }}' --}}
                                                {{--                                            data-cani='{{ intval($question['correct_answer']) }}' --}} {{--                                            data-key='{{ intval($key) }}' --}}
                                                tabindex='{{ 5 + intval($key) }}'
                                                data-qkey="{{ isset($question['correct_answer'][$key]) ? intval($question['correct_answer'][$key]) : '' }}"
                                                data-akey="{{ intval($correct_answer[$key] ?? $key) }}"
                                                data-key="{{ $key }}"
                                                {{ isset($correct_answer[$key]) && intval($correct_answer[$key]) === intval($key) ? 'checked' : '' }} />
                                            <label class='form-check-label'
                                                for='question[{{ $question['id'] }}][correct_answer][{{ $key }}]'>Is
                                                Correct?</label>
                                            <button
                                                onclick='Question.RemoveOption("option_{{ $question['id'] }}_{{ $key }}")'
                                                type="button"
                                                class="btn btn-flat-danger btn-sm border-left-light end waves-effect waves-float waves-light">
                                                <i data-lucide="x-square" class="me-25"></i> <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class='col-md-2 col-12 mb-50 mt-2' id='mcq_options_{{ $question['id'] }}_add'>
                            <div class='mb-1'>
                                <button type='button' class='btn btn-outline-success btn-sm text-nowrap px-1 waves-effect'
                                    onclick='Question.AddOption({{ $question['id'] }}, "mcq_options_{{ $question['id'] }}_holder", "mcq")'>
                                    <span><svg xmlns='http://www.w3.org/2000/svg' width='24' height='24'
                                            viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'
                                            stroke-linecap='round' stroke-linejoin='round'
                                            class='feather feather-plus me-50 font-small-4'>
                                            <line x1='12' y1='5' x2='12' y2='19'></line>
                                            <line x1='5' y1='12' x2='19' y2='12'></line>
                                        </svg>Add More Options</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @break

                @case('BLANKS')
                @break

                @case('ASSESSMENT')
                @break

                @default
                @break

            @endswitch
        </div>
    </div>
</div>
