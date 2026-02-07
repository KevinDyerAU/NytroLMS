<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Associated Topic:</span>
    <span class='col-sm-8'>
        <a href='{{ route('lms.'.\Str::plural($post['parent']).'.show', $post['content']->topic->id) }}'>{{ $post['content']->topic->title }}</a>
    </span>
</div>
@if( !empty($related['lvl1']))
    <div class='row mb-2 mx-25'>
        <div class="clearfix divider divider-secondary divider-center ">
            <span class="divider-text text-dark"> Questions</span>
        </div>
        <ul class="list-group list-group-flush">
            @foreach( $related['lvl1'] as $question)
                <li class="list-group-item">
                    <div class="card-body">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 text-primary">({{ $question->answer_type }}) {{ '#'.($loop->index + 1).': '.$question['title'] }}
                            </h5>
                        </div>
                        <div class="card-text">
                            <div class='question'>{!! $question['content'] !!}</div>
                            @if($question['answer_type'] === 'SCQ')
                                <ul class='list-unstyled d-flex flex-column gap-1'>
                                    @foreach($question->options['scq'] as  $k => $q)
                                        <li class='col-12'>
                                            <p data-k="{{ $k }}" data-a="{{ $question->correct_answer }}" class='@if(isset($question->correct_answer) && intval($k) === intval($question->correct_answer)) text-success @endif'>
                                                {{ $k }}:{!! $q !!}
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($question['answer_type'] === 'MCQ')
                                <ul class='list-unstyled d-flex flex-column gap-1'>
                                    @foreach($question->options['mcq'] as  $k => $q)
                                        <li class='col-12'>
                                            <p data-k="{{ $k }}" data-a="{{ $question->correct_answer }}" class='@if(isset($question->correct_answer) && intval($k) === intval($question->correct_answer)) text-success @endif'>
                                                {{ $k }}:{!! $q !!}
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($question['answer_type'] === 'SORT')
                                <ul class='list-unstyled d-flex flex-column gap-1'>
                                    @foreach($question->options['sort'] as  $k => $q)
                                        <li class='col-12'>
                                            <p data-k="{{ $k }}" data-a="{{ $question->correct_answer }}" class='@if(isset($question->correct_answer) && intval($k) === intval($question->correct_answer)) text-success @endif'>
                                                {{ $k }}:{!! $q !!}
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif($question['answer_type'] === 'TABLE')
                                @php
                                    $table = $question->table_structure;
                                @endphp
                                @if(!empty($table['columns']) && !empty($table['rows']))
                                    <table class="table table-bordered my-2">
                                        <thead>
                                        <tr>
                                            <th style="width: 40%">{{ $table['table_question_title'] ?? 'Questions' }}</th>
                                            @foreach($table['columns'] as $col)
                                                <th>{{ $col['heading'] ?? '' }}</th>
                                            @endforeach
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($table['rows'] as $rowIndex => $row)
                                            <tr>
                                                <th style="width: 40%;">{{ $row['heading'] ?? '' }}</th>
                                                @foreach($table['columns'] as $colIndex => $col)
                                                    <td>
                                                        @if($table['input_type'] === 'checkbox')
                                                            <input type="checkbox" name="table_input[{{ $rowIndex }}][{{ $colIndex }}]" />
                                                        @elseif($table['input_type'] === 'radio')
                                                            <input type="radio" name="table_input[{{ $rowIndex }}][{{ $colIndex }}]" />
                                                        @elseif($table['input_type'] === 'textarea')
                                                            <textarea name="table_input[{{ $rowIndex }}][{{ $colIndex }}]" class="w-100" rows="5"></textarea>
                                                        @else
                                                            <input type="text" name="table_input[{{ $rowIndex }}][{{ $colIndex }}]" class="w-100" />
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            @elseif($question['answer_type'] === 'MATRIX')
                                <div class="d-flex flex-row">
                                @if( !empty($question->options['matrix']) && is_iterable($question->options['matrix']))
                                    <ul class='list-unstyled d-flex flex-column col-5 gap-1'>
                                        @foreach( $question->options['matrix'] as  $k => $q)
                                            <li class='col-12'>{{ $k }}:{!! $q !!}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if( !empty($question->correct_answer) && is_iterable(json_decode($question->correct_answer, true)))
                                    <ul class='list-unstyled d-flex flex-column col-5 offset-1 gap-1'>
                                        @foreach( json_decode($question->correct_answer, true) as  $k => $q)
                                            <li class='col-12'>{{ $k }}:{!! $q !!}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
