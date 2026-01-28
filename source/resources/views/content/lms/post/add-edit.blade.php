@extends('layouts/contentLayoutMaster')

@section('title', 'Manage ' . $post['title'])

@section('vendor-style')
    <!-- vendor css files -->
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/laraberg/css/laraberg.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendors/css/pickers/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/dragula.min.css')) }}">
@endsection

@section('page-style')
    {{-- Page Css files --}}
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-sweet-alerts.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/forms/pickers/form-flat-pickr.css')) }}">
    <link rel="stylesheet"
        href="{{ asset(mix('css/' . strtolower(env('SETTINGS_KEY', 'KeyInstitute')) . '/base/plugins/extensions/ext-component-drag-drop.css')) }}">
@endsection

@section('content')
    @if (strtolower($action['name']) === 'edit')
        <div class="row">
            <div class="col-12">
                <div class="card mb-2">
                    <div class="card-body">
                        <ul class="nav nav-pills nav-fill">
                            <li class="nav-item">
                                <a class="nav-link {{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? '' : 'active' }}"
                                    id="lms_post_creator_tab" data-bs-toggle="pill" {{--                                   onclick='{{ ($post['type'] === 'question' && $gotoTab) ? "Tabs.gotoRoute('".route('lms.quizzes.edit', $post['parent']->slug)."')":"javascript:void(0);" }}' --}}
                                    href="#lms-post-creator" aria-expanded="true">
                                    {{ $action['name'] . ' ' . $post['title'] }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? 'active' : '' }}"
                                    id="lms_post_organizer_tab" data-bs-toggle="pill" href="#lms-post-organizer"
                                    {{--                                   onclick='{{ ($post['type'] === 'quiz' && $gotoTab) ? "Tabs.gotoRoute('".route('lms.questions.edit', $post['content']->id)."')":"javascript:void(0);" }}' --}} aria-expanded="false"><span class="spinner-border"
                                        style="display:none;margin-right: 20px;width: 1rem;height: 1rem;"></span>
                                    {{ $post['type'] === 'quiz' ? 'Setup Questions' : 'Organize ' . $post['title'] }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class='tab-content'>
        <div class='tab-pane {{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? '' : 'active' }}'
            id='lms-post-creator' role='tabpanel' aria-labelledby='{{ 'Create New ' . $post['title'] }}'
            aria-expanded='{{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? 'false' : 'true' }}'>
            <div class='row'>
                <div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-body'>
                            <form method='POST' action='{{ $action['url'] }}' class="form form-vertical"
                                enctype="multipart/form-data">
                                @if (strtolower($action['name']) === 'edit')
                                    @method('PUT')
                                    <input type='hidden' value='{{ md5($post['content']->id) }}' name='v'>
                                    <input type='hidden' value='{{ $post['content']?->id }}' name='x'>
                                @endif
                                @csrf
                                @include('content.lms.post.modal-body', [
                                    'action' => $action,
                                    'title' => $post['title'] ?? '',
                                    'type' => $post['type'] ?? '',
                                    'post' => $post['content'] ?? [],
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='tab-pane {{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? 'active' : '' }}'
            id='lms-post-organizer' role='tabpanel' aria-labelledby='{{ 'Organize ' . $post['title'] }}'
            aria-expanded='{{ $post['type'] === 'quiz' && !empty(Session::get('success') || $questions) ? 'true' : 'false' }}'>

            @if ($post['type'] === 'quiz' && !empty($questions))
                @include('content.lms.partials.setup.quiz', ['questions' => $related ?? null])
            @elseif(strtolower($action['name']) === 'edit' && $post['type'] !== 'quiz')
                <div class='row'>
                    <div class='col-md-12 col-12 mx-auto'>
                        <div class='card blockUI'>
                            <div class="card-header">
                                <h4 class="fw-bold text-primary">{{ \Str::title($related['type']) }}</h4>
                            </div>
                            <div class='card-body'>
                                @include('content.lms.post.listing', [
                                    'type' => $post['type'] ?? '',
                                    'list' => $related,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('vendor-script')
    <script src="{{ asset(mix('vendors/js/react/react.production.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/react/react-dom.production.min.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/laraberg/js/laraberg.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/dragula.min.js')) }}"></script>
    <script src="{{ asset('vendors/js/pickers/flatpickr/flatpickr.min.js') }}"></script>
@endsection
@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/enable-select.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/utils.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/tabs.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/drag.js')) }}"></script>
    <script src="{{ asset(mix('js/scripts/_my/lms-question.js')) }}"></script>
    <script>
        $(function() {
            Laraberg.init('_content', {
                sidebar: false,
                // prefix: '/api/v1/',
                // laravelFilemanager: true,
                laravelFilemanager: {
                    prefix: '/laravel-filemanager'
                }
            });
            @if ($post['type'] === 'quiz' && !empty($questions))
                var ckEditorOptions = {
                    extraPlugins: "notification,html5audio",
                    filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
                    filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
                    filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
                    filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}',
                };
                @if ($questions || old('question'))
                    @php
                        $questionnaire = old('question') ?? $related['lvl1'];
                    @endphp

                    @if ($questionnaire)
                        @foreach ($questionnaire as $id => $question)
                            CKEDITOR.replace("content_{{ $question['id'] ?? $id }}", ckEditorOptions);
                        @endforeach
                    @endif
                @endif
            @endif
            window.onbeforeunload = function(evt) {
                //Your Extra Code
                return;
            };
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
            Utils.toggleRelatedFields('next_course', 'show_next_course', 'neq', '0');
            @if (strtolower($action['name']) === 'edit')
                var drake = dragula([document.getElementById('handle-list-1'), document.getElementById(
                    'handle-list-2')], {
                    moves: function(el, container, handle) {
                        console.log(handle);
                        return handle.classList.contains('handle');
                    }
                });
                Drag.reorder(drake, '{{ \Str::plural($post['type']) }}', '{{ $post['content']['id'] }}', {
                    item: 'li',
                    prefixLength: 'item-'.length
                });
            @endif

            @if (isset($post['type']) && $post['type'] === 'lesson')
                if ($("#release_key").val() == 'XDAYS') {
                    $("#release_value_label").show();
                    $("#release_value").prop('required', true).prop('type', 'number');
                    @if (!empty($post['content']))
                        $("#release_value").val('{{ $post['content']['release_value'] }}');
                    @endif
                    $("#release_schedule").show();
                } else if ($("#release_key").val() == 'DATE') {
                    $("#release_schedule").show();
                    $("#release_value_label").hide();
                    $("#release_value").prop('required', true).prop('type', 'text');
                    @if (!empty($post['content']))
                        $("#release_value").val('{{ $post['content']['release_value'] }}');
                    @endif
                    $("#release_value").flatpickr({
                        dateFormat: 'Y-m-d',
                        altFormat: 'd-m-Y',
                        altInput: true,
                    });
                } else {
                    $("#release_schedule").hide();
                }
                $("#release_key").on('select2:select', function(e) {
                    const data = e.params.data;
                    $("#release_schedule").show();
                    switch (data.id) {
                        case 'XDAYS':
                            $("#release_value").prop('required', true).prop('type', 'number').val('');
                            $("#release_value_label").show();
                            break;
                        case 'DATE':
                            $("#release_value_label").hide();
                            $("#release_value").prop('required', true).prop('type', 'text').val('');
                            $("#release_value").flatpickr({
                                dateFormat: 'Y-m-d',
                                altFormat: 'd-m-Y',
                                altInput: true,
                            });
                            break;
                        default:
                            $("#release_value").prop('required', false).prop('type', 'text').val('');
                            $("#release_schedule").hide();
                            break;
                    }
                });
            @endif
            @if (isset($post['type']) && $post['type'] === 'quiz')
                $('#cancel').on('click', function() {
                    window.location = '{{ route('lms.quizzes.index') }}';
                });
            @else
                $('#cancel').on('click', function() {
                    window.location =
                        '{{ strtolower($action['name']) === 'edit' ? route('lms.' . \Str::plural($post['type']) . '.show', $post['content']['id']) : route('lms.' . \Str::plural($post['type']) . '.index') }}';
                });
            @endif

            if ($("#lesson_id").length > 0 && $("#course_id").length > 0) {
                $('#course_id').on('select2:select', function(e) {
                    const data = e.params.data;
                    $('#lesson_id').find('option').remove();
                    axios.get('/api/v1/courses/' + data.id).then(response => {
                        const res = response.data;
                        const course = res.data;
                        if (res.status === 'success' && course.id > 0) {
                            let lessons = course.lessons.data;
                            // console.log(leaders);
                            if (lessons.length > 0) {
                                $("#lesson_id").append('<option></option>');
                                $.each(lessons, function(index, lesson) {
                                    // console.log(leader);
                                    $('#lesson_id').append('<option value=' + lesson.id +
                                        '>' + lesson.title + '</option>');
                                    // $('#leaders').prop('disabled', false);
                                });
                                $("#lesson_id").attr("data-placeholder", 'Select a Lesson');
                            }
                        }
                    }).catch(error => {
                        console.log(error);
                    });
                });
            }

            if ($("#lesson_id").length > 0 && $("#topic_id").length > 0) {
                $('#lesson_id').on('select2:select', function(e) {
                    const data = e.params.data;
                    $('#topic_id').find('option').remove();
                    axios.get('/api/v1/lessons/' + data.id).then(response => {
                        const res = response.data;
                        const lesson = res.data;
                        if (res.status === 'success' && lesson.id > 0) {
                            let topics = lesson.topics.data;
                            // console.log(leaders);
                            if (topics.length > 0) {
                                $("#topic_id").append('<option></option>');
                                $.each(topics, function(index, topic) {
                                    // console.log(leader);
                                    $('#topic_id').append('<option value=' + topic.id +
                                        '>' + topic.title + '</option>');
                                    // $('#leaders').prop('disabled', false);
                                });

                            }
                        }
                    }).catch(error => {
                        console.log(error);
                    });
                });
            }

            $("#course_length_days").on("change", function() {
                let course_length_days = $(this).val();
                if (course_length_days > 0) {
                    $("#course_expiry_days").val(course_length_days * 2);
                } else {
                    $("#course_expiry_days").val(0);
                }
            });
        });
    </script>
@endsection
