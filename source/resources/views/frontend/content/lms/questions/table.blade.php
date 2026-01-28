@php
    $tableStructure = $question->table_structure;
    $inputType = $tableStructure['input_type'] ?? 'radio';
    $columns = $tableStructure['columns'] ?? [];
    $rows = $tableStructure['rows'] ?? [];
@endphp

<div class="question-container mb-2">
    <div class="question-content mb-2">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="table-question-{{ $question->id }}">
            <thead>
                <tr>
                    <th style="width: 40%">{{ $tableStructure['table_question_title'] ?? 'Questions' }}</th>
                    @foreach ($tableStructure['columns'] as $col)
                        <th>{{ $col['heading'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $rowIndex => $row)
                    <tr data-row-id="{{ $rowIndex }}">
                        <td>{{ $row['heading'] }}</td>
                        @foreach ($columns as $colIndex => $column)
                            <td>
                                @switch($inputType)
                                    @case('radio')
                                        <div class="form-check">
                                            <input type="radio" class="form-check-input"
                                                name="answer[{{ $question->id }}][{{ $rowIndex }}]"
                                                value="{{ $colIndex }}" data-row="{{ $rowIndex }}"
                                                data-row-heading="{{ $row['heading'] }}"
                                                data-col-heading="{{ $column['heading'] }}"
                                                data-question="{{ $question->id }}"
                                                id="q{{ $question->id }}_r{{ $rowIndex }}_c{{ $colIndex }}"
                                                @if (isset($attempted_answers[$rowIndex]) &&
                                                        intval($attempted_answers[$rowIndex]['user_response']) === intval($colIndex)) {{ 'checked=checked' }}
                                                @elseif(
                                                    !isset($attempted_answers[$question->id]) &&
                                                        (isset($last_attempt[$question->id][$rowIndex]) &&
                                                            intval($last_attempt[$question->id][$rowIndex]['user_response']) === intval($colIndex)))
                                                {{ 'checked=checked' }} @endif>
                                            <label class="form-check-label"
                                                for="q{{ $question->id }}_r{{ $rowIndex }}_c{{ $colIndex }}"></label>
                                        </div>
                                    @break

                                    @case('checkbox')
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                name="answer[{{ $question->id }}][{{ $rowIndex }}][{{ $colIndex }}]"
                                                value="1" data-row="{{ $rowIndex }}"
                                                data-row-heading="{{ $row['heading'] }}"
                                                data-col-heading="{{ $column['heading'] }}"
                                                data-question="{{ $question->id }}"
                                                id="q{{ $question->id }}_r{{ $rowIndex }}_c{{ $colIndex }}"
                                                @if (isset($attempted_answers[$rowIndex]) && $attempted_answers[$rowIndex][$colIndex]['user_response']) {{ 'checked=checked' }}
                                                @elseif(
                                                    !isset($attempted_answers[$question->id]) &&
                                                        isset($last_attempt[$question->id][$rowIndex][$colIndex]) &&
                                                        $last_attempt[$question->id][$rowIndex][$colIndex]['user_response']
                                                )
                                                {{ 'checked=checked' }} @endif>
                                            <label class="form-check-label"
                                                for="q{{ $question->id }}_r{{ $rowIndex }}_c{{ $colIndex }}"></label>
                                        </div>
                                    @break

                                    @case('text')
                                        <input type="text" class="form-control"
                                            name="answer[{{ $question->id }}][{{ $rowIndex }}][{{ $colIndex }}]"
                                            value="{{ $attempted_answers[$question->id][$rowIndex][$colIndex]['user_response'] ?? ($last_attempt[$question->id][$rowIndex][$colIndex]['user_response'] ?? '') }}"
                                            placeholder="Enter your answer">
                                    @break

                                    @case('textarea')
                                        <textarea name="answer[{{ $question->id }}][{{ $rowIndex }}][{{ $colIndex }}]" class="form-control"
                                            rows="5" style="resize: both;" placeholder="Enter your answer">{{ $attempted_answers[$question->id][$rowIndex][$colIndex]['user_response'] ?? ($last_attempt[$question->id][$rowIndex][$colIndex]['user_response'] ?? '') }}</textarea>
                                    @break
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($question->required)
        <div class="invalid-feedback">
            This question is required.
        </div>
    @endif
</div>

@push('styles')
    <style>
        .table th,
        .table td {
            vertical-align: middle;
        }

        .form-check {
            margin: 0;
            display: flex;
            justify-content: center;
        }

        .form-check-input {
            margin: 0;
        }

        .table td textarea {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            margin: 0;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            display: block;
        }

        .table td {
            padding: 0 !important;
        }

        .table td:has(textarea) {
            padding: 0 !important;
        }
    </style>
@endpush
