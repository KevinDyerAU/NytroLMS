<div class="row">
    <div class="mb-1 col-12">
        <p class="question-content">{!! preg_replace('/\s*style\s*=\s*["\'](?!.*(?:height|width)\s*:)[^"\']*["\']/i', '', $question->content) !!}</p>
        <hr />
        @if (!empty($last_attempt[$question->id]) && $last_attempt_evaluation[$question->id]['status'] === 'correct')
            <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                <div class="alert-body d-flex align-items-center">
                    <p class="col-lg-6 col-12">Previously marked as "Correct Answer"</p>
                    @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                        <p class="col-lg-6 col-12"> Comment:
                            {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                    @endif
                </div>
            </div>
        @else
            @if (!empty($last_attempt[$question->id]))
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <div class="alert-body d-flex align-items-center">
                        <p class="col-lg-6 col-12">Previously marked as "Incorrect Answer"</p>
                        @if (!empty($last_attempt_evaluation[$question->id]['comment']))
                            <p class="col-lg-6 col-12"> Comment:
                                {{ strip_tags($last_attempt_evaluation[$question->id]['comment']) }} </p>
                        @endif
                    </div>
                </div>
            @endif
        @endif
        @if (empty($last_attempt[$question->id]))
            <p class='text-muted small mb-3'>
                <span class='d-none d-md-inline'>Drag items to reorder or use the arrow buttons to move items up and down</span>
            </p>
        @endif
        <div class='col-12 col-md-8 col-lg-6 mt-3 mb-5'>
            <div class="row">
                <ul class="list-group {{ !empty($last_attempt[$question->id]) ? '' : 'sorting-list' }}"
                    id="sorting-group{{ $question->id }}" data-type='sort_choice'>
                    @if (!empty($attempted_answers[$question->id]))
                        @foreach ($attempted_answers[$question->id] as $sortId => $value)
                            <li class="list-group-item draggable sort-item" data-index="{{ $loop->index }}">
                                <div class="d-flex align-items-center">
                                    <div class="sort-handle me-2 d-flex flex-column" role="button" tabindex="0" aria-label="Move item">
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 mb-1 sort-move-up" aria-label="Move up" tabindex="0" {{ $loop->first ? 'disabled' : '' }}>
                                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-arrow-up'><line x1='12' y1='19' x2='12' y2='5'></line><polyline points='5 12 12 5 19 12'></polyline></svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 sort-move-down" aria-label="Move down" tabindex="0" {{ $loop->last ? 'disabled' : '' }}>
                                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-arrow-down'><line x1='12' y1='5' x2='12' y2='19'></line><polyline points='19 12 12 19 5 12'></polyline></svg>
                                        </button>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0 py-2">{!! $value !!}</h5>
                                    </div>
                                    <div class="sort-handle-drag ms-2 d-flex align-items-center text-muted" aria-label="Drag handle">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-move'><polyline points='5 9 2 12 5 15'></polyline><polyline points='9 5 12 2 15 5'></polyline><polyline points='15 19 12 22 9 19'></polyline><polyline points='19 9 22 12 19 15'></polyline><line x1='2' y1='12' x2='22' y2='12'></line><line x1='12' y1='2' x2='12' y2='22'></line></svg>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    @elseif(!empty($last_attempt[$question->id]))
                        @foreach ($last_attempt[$question->id] as $sortId => $value)
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0 py-2">{{ $value }}</h5>
                                    </div>
                                    <div class="sort-handle-drag ms-2 d-flex align-items-center text-muted" aria-label="Drag handle">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-move'><polyline points='5 9 2 12 5 15'></polyline><polyline points='9 5 12 2 15 5'></polyline><polyline points='15 19 12 22 9 19'></polyline><polyline points='19 9 22 12 19 15'></polyline><line x1='2' y1='12' x2='22' y2='12'></line><line x1='12' y1='2' x2='12' y2='22'></line></svg>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    @else
                        @foreach (\Arr::shuffle($question->options['sort']) as $sortId => $value)
                            <li class="list-group-item draggable sort-item" data-index="{{ $loop->index }}">
                                <div class="d-flex align-items-center">
                                    <div class="sort-handle me-2 d-flex flex-column" role="button" tabindex="0" aria-label="Move item">
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 mb-1 sort-move-up" aria-label="Move up" tabindex="0" {{ $loop->first ? 'disabled' : '' }}>
                                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-arrow-up'><line x1='12' y1='19' x2='12' y2='5'></line><polyline points='5 12 12 5 19 12'></polyline></svg>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 sort-move-down" aria-label="Move down" tabindex="0" {{ $loop->last ? 'disabled' : '' }}>
                                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-arrow-down'><line x1='12' y1='5' x2='12' y2='19'></line><polyline points='19 12 12 19 5 12'></polyline></svg>
                                        </button>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0 py-2">{{ $value }}</h5>
                                    </div>
                                    <div class="sort-handle-drag ms-2 d-flex align-items-center text-muted" aria-label="Drag handle">
                                        <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-move'><polyline points='5 9 2 12 5 15'></polyline><polyline points='9 5 12 2 15 5'></polyline><polyline points='15 19 12 22 9 19'></polyline><polyline points='19 9 22 12 19 15'></polyline><line x1='2' y1='12' x2='22' y2='12'></line><line x1='12' y1='2' x2='12' y2='22'></line></svg>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
