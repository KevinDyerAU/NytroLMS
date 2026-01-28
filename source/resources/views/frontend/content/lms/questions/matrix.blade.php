<div class="row">
    <div class="mb-1 col-12">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
        <hr />
        @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct')
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <p class="col-lg-6 col-12">Previously marked as "Correct Answer"</p>
                    @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                        <p class="col-lg-6 col-12">
                            Comment: {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                    @endif
                </div>
            </div>
        @else
            @if (!empty($last_attempt[$question->id]))
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-center">
                        <p class="col-lg-6 col-12">Previously marked as "Incorrect Answer"</p>
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12">
                                Comment: {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        @if (empty($last_attempt[$question->id]))
            <p class='text-muted small mb-2'>
                <span class='d-none d-md-inline'>Drag answers from the options below into the answer boxes</span>
                <span class='d-md-none'>Tap an option, then tap a box to place it</span>
            </p>
        @endif

        <div class='mt-2 mb-4'>
            <div class="mb-2">
                <h6 class="mb-1">Available Options:</h6>
                <div class="border rounded p-1 bg-light">
                    <ul class="list-group list-group-flush matrix-source d-flex flex-row flex-wrap gap-1 border-0 mb-0"
                        id="matrix-group{{ $question->id }}" data-destination='matrix_list{{ $question->id }}'>
                        @if (empty($attempted_answers[$question->id]) && empty($last_attempt[$question->id]))
                            @foreach (\Arr::shuffle(json_decode($question->correct_answer, true)) as $matrixId => $value)
                                <li class="list-group-item draggable border rounded p-1 mb-0">
                                    <small class="mb-0">{{ $value }}</small>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border rounded">
                <table class="table table-borderless mb-0 matrix-table d-none d-md-table">
                    @foreach ($question->options['matrix'] as $matrixId => $value)
                        <tr class="matrix-row">
                            <td class="p-2 align-middle" style="width: 50%;">
                                <div class="matrix-table-choice">{!! $value !!}</div>
                            </td>
                            <td class="p-1 align-middle" style="width: 50%;">
                                <ul class="list-group matrix-destination-slot border rounded mb-0 {{ empty($last_attempt[$question->id]) ? 'matrix-sorting-list' : '' }}"
                                    id="matrix_list{{ $question->id }}_{{ $matrixId }}" data-slot-index="{{ $matrixId }}">
                                    @if (!empty($attempted_answers[$question->id][$matrixId]))
                                        <li class="list-group-item matrix-sort-item p-1 border-0" data-index="0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="mb-0">{{ $attempted_answers[$question->id][$matrixId] }}</small>
                                                <button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">
                                                    <svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-x'><line x1='18' y1='6' x2='6' y2='18'></line><line x1='6' y1='6' x2='18' y2='18'></line></svg>
                                                </button>
                                            </div>
                                        </li>
                                    @elseif(!empty($last_attempt[$question->id][$matrixId]))
                                        <li class="list-group-item matrix-sort-item p-1 border-0" data-index="0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="mb-0">{{ $last_attempt[$question->id][$matrixId] }}</small>
                                                <button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">
                                                    <svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-x'><line x1='18' y1='6' x2='6' y2='18'></line><line x1='6' y1='6' x2='18' y2='18'></line></svg>
                                                </button>
                                            </div>
                                        </li>
                                    @else
                                        <li class="list-group-item matrix-empty-slot border-dashed p-2 text-center" data-slot-index="{{ $matrixId }}">
                                            <small class="text-muted">Drop answer here</small>
                                        </li>
                                    @endif
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </table>

                <!-- Mobile layout: stacked -->
                <div class="d-md-none">
                    @foreach ($question->options['matrix'] as $matrixId => $value)
                        <div class="border-bottom p-2">
                            <div class="mb-2">
                                <div class="matrix-table-choice">{!! $value !!}</div>
                            </div>
                            <ul class="list-group matrix-destination-slot border rounded mb-0 {{ empty($last_attempt[$question->id]) ? 'matrix-sorting-list' : '' }}"
                                id="matrix_list{{ $question->id }}_{{ $matrixId }}" data-slot-index="{{ $matrixId }}">
                                @if (!empty($attempted_answers[$question->id][$matrixId]))
                                    <li class="list-group-item matrix-sort-item p-1 border-0" data-index="0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <small class="mb-0">{{ $attempted_answers[$question->id][$matrixId] }}</small>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">
                                                <svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-x'><line x1='18' y1='6' x2='6' y2='18'></line><line x1='6' y1='6' x2='18' y2='18'></line></svg>
                                            </button>
                                        </div>
                                    </li>
                                @elseif(!empty($last_attempt[$question->id][$matrixId]))
                                    <li class="list-group-item matrix-sort-item p-1 border-0" data-index="0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <small class="mb-0">{{ $last_attempt[$question->id][$matrixId] }}</small>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-0 matrix-remove-slot" aria-label="Remove" tabindex="0" style="width: 18px; height: 18px; line-height: 1;">
                                                <svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-x'><line x1='18' y1='6' x2='6' y2='18'></line><line x1='6' y1='6' x2='18' y2='18'></line></svg>
                                            </button>
                                        </div>
                                    </li>
                                @else
                                    <li class="list-group-item matrix-empty-slot border-dashed p-2 text-center" data-slot-index="{{ $matrixId }}">
                                        <small class="text-muted">Tap to place answer</small>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .question-content img {
        max-width: 600px !important;
        height: auto;
    }

    .matrix-source {
        min-height: 40px;
    }

    .matrix-source .list-group-item {
        flex: 0 0 auto;
        min-width: fit-content;
    }

    .matrix-table tr {
        border-bottom: 1px solid #dee2e6;
    }

    .matrix-table tr:last-child {
        border-bottom: none;
    }

    .matrix-table-choice {
        min-height: 40px;
        display: flex;
        align-items: center;
    }

    .matrix-destination-slot {
        min-height: 40px;
        min-width: 100%;
    }

    .matrix-empty-slot {
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .matrix-sort-item {
        min-height: 40px;
    }

    /* Mobile optimizations */
    @media (max-width: 767.98px) {
        .matrix-source .list-group-item {
            cursor: pointer;
            transition: all 0.2s;
        }

        .matrix-source .list-group-item.bg-primary {
            border-color: #0d6efd !important;
        }

        .matrix-empty-slot {
            cursor: pointer;
            transition: all 0.2s;
        }

        .matrix-empty-slot:active {
            background-color: #f8f9fa;
        }

        .matrix-sort-item {
            cursor: pointer;
        }

        .matrix-table-choice {
            font-size: 0.9rem;
        }
    }
</style>
