@extends('frontend/layouts/contentLayoutMaster')

@section('title', $attempt->quiz->title)

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
@endsection

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
@endsection

@section('content-sidebar')
    {{--    @include('frontend.content.lms.sidebar') --}}
@endsection

@section('content')
    <section class='review-quiz-attempt'>
        @if ($attempt)
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><small>Submitted by:</small> {{ $attempt->user->name }}</h4>
                    <small class="text-secondary">Submitted at: {{ $attempt->submitted_at }}</small>
                </div>
                <div class="card-body blockUI">
                    <ul class="list-group">
                        @foreach ($questions as $question)
                            <li href="#" class="list-group-item" data-question="{{ $question['id'] }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1 text-primary"><i data-lucide='target'></i>
                                        {{ '#' . ($loop->index + 1) . ': ' . $question['title'] }}
                                        @if ( $question['is_deleted'] )
                                            <span class="badge bg-danger ms-1">Deleted</span>
                                        @endif
                                    </h5>
                                </div>
                                <div class="card-text">
                                    @if ($question['answer_type'] === 'ASSESSMENT')
                                        <div class='question'>{!! \Helper::populateAssessment($question['id'], $question['content']) !!}</div>
                                    @elseif($question['answer_type'] === 'BLANKS')
                                        <div class='question'>{!! \Helper::populateInput($question['id'], $question['content']) !!}</div>
                                    @else
                                        <div class='question'>{!! $question['content'] !!}</div>
                                    @endif
                                    @if ($question['answer_type'] === 'SCQ')
                                        <ul class='list-unstyled d-flex flex-column'>
                                            @foreach ($options[$question['id']]['scq'] as $k => $q)
                                                <li class='col-lg-6 col-12'>
                                                    <p class=''>
                                                        {{ $k }}:{!! $q !!}
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($question['answer_type'] === 'MCQ')
                                        <ul class='list-unstyled d-flex flex-column'>
                                            @foreach ($options[$question['id']]['mcq'] as $k => $q)
                                                <li class='col-lg-6 col-12'>
                                                    <p class=''>
                                                        {{ $k }}:{!! $q !!}
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($question['answer_type'] === 'SORT')
                                        <ul class='list-unstyled d-flex flex-column'>
                                            @foreach ($options[$question['id']]['sort'] as $k => $q)
                                                <li class='col-lg-6 col-12'>
                                                    <p class=''>
                                                        {{ $k }}:{!! $q !!}
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($question['answer_type'] === 'MATRIX')
                                        <ul class='list-unstyled d-flex flex-column'>
                                            @foreach ($options[$question['id']]['matrix'] as $k => $q)
                                                <li class='col-lg-6 col-12'>
                                                    <p class=''>
                                                        {{ $k }}:{!! $q !!}
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($question['answer_type'] === 'TABLE')
                                        @php
                                            $tableStructure = is_string($question['table_structure'])
                                                ? json_decode($question['table_structure'], true)
                                                : $question['table_structure'];
                                            $inputType = $tableStructure['input_type'] ?? 'radio';
                                        @endphp
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40%">
                                                        {{ $tableStructure['table_question_title'] ?? 'Questions' }}</th>
                                                    @foreach ($tableStructure['columns'] as $column)
                                                        <th>{{ $column['heading'] }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tableStructure['rows'] as $rowIndex => $row)
                                                    <tr>
                                                        <td style="width: 40%"><strong>{{ $row['heading'] }}</strong></td>
                                                        @foreach ($tableStructure['columns'] as $colIndex => $column)
                                                            <td>
                                                                @if (isset($attempt->submitted_answers[$question['id']]))
                                                                    @php
                                                                        $answers =
                                                                            $attempt->submitted_answers[
                                                                                $question['id']
                                                                            ][$rowIndex] ?? [];
                                                                        $isSelected = false;
                                                                        if ($inputType === 'radio') {
                                                                            if (
                                                                                isset($answers['user_response']) &&
                                                                                $answers['user_response'] !== '' &&
                                                                                $answers['user_response'] === $colIndex
                                                                            ) {
                                                                                $isSelected = true;
                                                                            }
                                                                        } elseif ($inputType === 'checkbox') {
                                                                            foreach ($answers as $ans) {
                                                                                if (
                                                                                    $ans['user_response'] ===
                                                                                    (string) $colIndex
                                                                                ) {
                                                                                    $isSelected = true;
                                                                                }
                                                                            }
                                                                        } else {
                                                                            // text/textarea
                                                                            foreach ($answers as $ans) {
                                                                                if (
                                                                                    isset($ans['column']) &&
                                                                                    $ans['column'] ===
                                                                                        $column['heading'] &&
                                                                                    isset($ans['user_response'])
                                                                                ) {
                                                                                    $isSelected = $ans['user_response'];
                                                                                }
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    @if ($inputType === 'radio')
                                                                        @if ($isSelected)
                                                                            <span class="badge bg-primary">Selected</span>
                                                                        @endif
                                                                    @elseif($inputType === 'checkbox')
                                                                        @if ($isSelected)
                                                                            <span class="badge bg-primary">Selected</span>
                                                                        @endif
                                                                    @else
                                                                        @if ($isSelected)
                                                                            {!! $isSelected !!}
                                                                        @endif
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                    @if ($question['answer_type'] !== 'TABLE')
                                        <div class='answer mb-1 mt-1'>
                                            <h5 class="mb-1 text-primary"><i data-lucide='pen-tool'></i> Answer: </h5>
                                            @if ($question['answer_type'] === 'FILE')
                                                @if (isset($attempt->submitted_answers[$question['id']]))
                                                    <a href='{{ Storage::url($attempt->submitted_answers[$question['id']]) }}'
                                                        target='_blank' class='btn btn-outline-secondary btn-sm'>View
                                                        File</a>
                                                @endif
                                            @elseif($question['answer_type'] === 'SORT')
                                                @if (isset($attempt->submitted_answers[$question['id']]) && is_array($attempt->submitted_answers[$question['id']]))
                                                    @foreach ($attempt->submitted_answers[$question['id']] as $answerKey => $answer)
                                                        {!! $answerKey +
                                                            1 .
                                                            ': ' .
                                                            $question['options'][\Str::lower($question['answer_type'])][$answerKey + 1] .
                                                            '<br/>' !!}
                                                    @endforeach
                                                @endif
                                            @elseif($question['answer_type'] === 'MATRIX')
                                                @if (isset($attempt->submitted_answers[$question['id']]) && is_array($attempt->submitted_answers[$question['id']]))
                                                    @foreach ($attempt->submitted_answers[$question['id']] as $answerKey => $answer)
                                                        {!! $loop->index + 1 . ': ' . $answer . '<br/>' !!}
                                                    @endforeach
                                                @endif
                                            @elseif($question['answer_type'] === 'SCQ')
                                                @if (isset($attempt->submitted_answers[$question['id']]))
                                                    <p
                                                        class='p-1 @if (isset($correct_answers[$question['id']]) &&
                                                                intval($attempt->submitted_answers[$question['id']]) === intval($correct_answers[$question['id']])) alert alert-success @else alert alert-danger @endif'>
                                                        {!! $attempt->submitted_answers[$question['id']] .
                                                            ': ' .
                                                            $question['options'][\Str::lower($question['answer_type'])][$attempt->submitted_answers[$question['id']]] .
                                                            '<br/>' !!}
                                                    </p>
                                                @endif
                                            @else
                                                @if (isset($attempt->submitted_answers[$question['id']]) && is_array($attempt->submitted_answers[$question['id']]))
                                                    @foreach ($attempt->submitted_answers[$question['id']] as $answerKey => $answer)
                                                        @if (!empty($correct_answers[$question['id']]))
                                                            @php $correctAnswers = json_decode($correct_answers[$question['id']], true) @endphp
                                                        @endif
                                                        <p
                                                            class='p-1 @if (isset($correctAnswers[$answerKey]) && intval($answerKey) === intval($correctAnswers[$answerKey])) alert alert-success @else alert alert-danger @endif'>
                                                            {!! $answerKey . ': ' . $question['options'][\Str::lower($question['answer_type'])][$answerKey] . '<br/>' !!}
                                                        </p>
                                                    @endforeach
                                                @else
                                                    <p>{!! $attempt->submitted_answers[$question['id']] ?? '' !!}</p>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex flex-column flex-lg-row align-items-stretch">

                                        @if ($canEvaluate)
                                            <div class="col-lg-6 col-12 align-self-center">
                                                <div class="btn-group" role="group" aria-label="Action Buttons">
                                                    <button type="button" id='correctAnswer{{ $question['id'] }}'
                                                        onclick='Assessment.MarkAnswer({{ $question['id'] . ',' . $attempt->id . ',"correct"' }})'
                                                        class="btn btn-success markAnswer">Correct
                                                    </button>
                                                    <button type="button" id='incorrectAnswer{{ $question['id'] }}'
                                                        onclick='Assessment.MarkAnswer({{ $question['id'] . ',' . $attempt->id . ',"incorrect"' }})'
                                                        class="btn btn-danger markAnswer">Incorrect
                                                    </button>
                                                    <button type="button" id='commentAnswer{{ $question['id'] }}'
                                                        onclick='Assessment.ToggleCommentAnswer("commentHolder_{{ $question['id'] }}")'
                                                        class="btn btn-secondary markAnswer">Comment
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-lg-{{ $canEvaluate ? '6' : '12' }} col-12"
                                            id='existing{{ $question['id'] }}'>
                                            @if (
                                                $evaluation &&
                                                    isset($evaluation->results[$question['id']]) &&
                                                    !empty($evaluation->results[$question['id']]['status']))
                                                <div
                                                    class="alert alert-{{ $evaluation->results[$question['id']]['status'] === 'correct' ? 'info' : 'danger' }} p-1 me-1">
                                                    <p>Marked as
                                                        <strong>{{ $evaluation->results[$question['id']]['status'] }}</strong>
                                                        @if ($evaluation->results[$question['id']]['comment'] && !$markedBySystem)
                                                            , with comments:
                                                            <span>{!! $evaluation->results[$question['id']]['comment'] !!}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class='col-12  mt-1' id="commentHolder_{{ $question['id'] }}"
                                        style='display: none;'>
                                        <form>
                                            <textarea class="form-control content-ckeditor" name="comment[{{ $question['id'] }}]"
                                                id="comment_{{ $question['id'] }}" tabindex="1" autofocus>
                                                @if ($evaluation && isset($evaluation->results[$question['id']]))
{!! $evaluation->results[$question['id']]['comment'] !!}
@endif
                                            </textarea>

                                            <div class="demo-spacing-0" style='display:none;'
                                                id="comment_{{ $question['id'] }}_alert">
                                                <div class="alert alert-danger mt-1 alert-validation-msg" role="alert">
                                                    <div class="alert-body d-flex align-items-center">
                                                        <i data-lucide="info" class="me-50"></i>
                                                        <span>The comment value is missing.</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-wrap mt-1">
                                                <button type="button" class="btn btn-primary me-1"
                                                    onclick='Assessment.SubmitComment({{ 'window.CKEDITOR.instances.comment_' . $question['id'] . ',' . $question['id'] . ',' . $attempt->id }})'>
                                                    Save
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    onclick='Assessment.ToggleCommentAnswer("commentHolder_{{ $question['id'] }}")'
                                                    data-bs-dismiss="modal">Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class='card-footer'>
                    <div class='col-12  mt-1'>
                        <h5 class="mb-1 text-primary">
                            <i data-lucide='at-sign'></i>
                            Your final feedback:
                        </h5>
                        <form>

                            @if (count($feedbacks) > 0)
                                <div class="alert alert-secondary p-1 me-1">
                                    @foreach ($feedbacks as $feedback)
                                        <p>
                                            <span class="text-muted">{{ $feedback->updated_at }}:</span>
                                            <span>{!! $feedback->body['message'] !!}</span>
                                        </p>
                                    @endforeach
                                </div>
                            @elseif($attempt->feedbacks()->count() > 0)
                                <div class="alert alert-secondary p-1 me-1">
                                    @foreach ($attempt->feedbacks as $feedback)
                                        <p>
                                            <span class="text-muted">{{ $feedback->updated_at }}:</span>
                                            <span>{!! $feedback->body['message'] !!}</span>
                                        </p>
                                    @endforeach
                                </div>
                            @endif
                            @can('mark assessments')
                                <textarea class="form-control content-ckeditor" name="feedback[{{ $attempt->id }}]"
                                    id="feedback_{{ $attempt->id }}" required='required'>
                                [ {{ auth()->user()->name }} ]:
                                </textarea>

                                <div class="demo-spacing-0" style='display:none;' id="feedback_{{ $attempt->id }}_alert">
                                    <div class="alert alert-danger mt-1 alert-validation-msg" role="alert">
                                        <div class="alert-body d-flex align-items-center">
                                            <i data-lucide="info" class="me-50"></i>
                                            <span>The feedback value is missing.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-8 col-12 mt-1 mb-3">
                                    <div class="btn-group" role="group" aria-label="Action Buttons">

                                        <button type="button" id='satisfactory{{ $attempt->id }}' {{--                                            @if ($canEvaluate) --}}
                                            onclick='Assessment.MarkAttempt({{ 'window.CKEDITOR.instances.feedback_' . $attempt->id . ',' . $attempt->id . ',"satisfactory"' }})'
                                            {{--                                            @else --}} {{--                                            disabled='disabled' --}} {{--                                            @endif --}}
                                            class="btn btn-success">Satisfactory
                                        </button>
                                        <button type="button" id='notSatisfactory{{ $attempt->id }}' {{--                                            @if ($canEvaluate) --}}
                                            onclick='Assessment.MarkAttempt({{ 'window.CKEDITOR.instances.feedback_' . $attempt->id . ',' . $attempt->id . ',"unsatisfactory"' }})'
                                            {{--                                            @else --}} {{--                                            disabled='disabled' --}} {{--                                            @endif --}}
                                            class="btn btn-danger">Not Satisfactory
                                        </button>
                                        @if ($attempt->feedbacks()->count() > 0)
                                            <button type="button" id='emailResults{{ $attempt->id }}'
                                                onclick='Assessment.EmailResults({{ $attempt->id }})'
                                                class="btn btn-secondary">Email Results
                                            </button>
                                        @endif
                                    </div>
                                    @if ($attempt->feedbacks()->count() > 0)
                                        <span class='mt-1 ms-1'>Email Sent
                                            {{ Spatie\Activitylog\Models\Activity::where('log_name', 'communication')->where('subject_id', $attempt->user_id)->whereJsonContains('properties->quiz_attempt', $attempt->id)->count() }}
                                            times
                                        </span>
                                    @endif
                                </div>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>
        @else
            {{ abort(404, 'Unfortunately quiz attempt not found.') }}
        @endif
    </section>
    @if (!empty($attempt) && in_array($attempt->status, ['FAIL', 'RETURNED']))
        <div class="d-flex  w-100  items-justified-center">
            <a href="{{ route('frontend.lms.quizzes.show', $attempt->quiz_id) }}" class="btn btn-primary">Re-attempt
                Quiz</a>
        </div>
    @endif
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset('vendors/vendor/ckeditor/ckeditor.js') }}"></script>
    {{-- <script src="{{ asset(mix('js/core/ckeditor5-init.js')) }}"></script> --}}
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
@endsection
@section('page-script')
    <script src="{{ asset(mix('js/scripts/_my/assessment.js')) }}"></script>

    <script>
        $(function() {
            var ckEditorOptions = {
                extraPlugins: 'notification',
                removePlugins: 'exportpdf',
                filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
                filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
                filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
                filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}'
            };
            CKEDITOR.replaceAll('content-ckeditor', ckEditorOptions);
            CKEDITOR.on("instanceReady", function(event) {
                event.editor.on("beforeCommandExec", function(event) {
                    // Show the paste dialog for the paste buttons and right-click paste
                    if (event.data.name == "paste") {
                        event.editor._.forcePasteDialog = true;
                    }
                    // Don't show the paste dialog for Ctrl+Shift+V
                    if (event.data.name == "pastetext" && event.data.commandData.from ==
                        "keystrokeHandler") {
                        event.cancel();
                    }
                })
            });
        });
    </script>
@endsection
