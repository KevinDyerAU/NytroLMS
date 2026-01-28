@extends('layouts.contentLayoutMaster')

@section('title','View '.$post['title'])

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('vendors/vendor/laraberg/css/laraberg.css') }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/dragula.min.css')) }}">
@endsection
@section('page-style')
    <!-- Page css files -->
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-drag-drop.css')) }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-2">
                <div class="card-body">
                    <ul class="nav nav-pills nav-fill">
                        <li class="nav-item">
                            <a class="nav-link active" id="lms_post_creator_tab" data-bs-toggle="pill"
                               href="#lms-post-creator"
                               aria-expanded="true"> Content</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="lms_post_organizer_tab" data-bs-toggle="pill"
                               href="#lms-post-organizer"
                               aria-expanded="false">
                                Details</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-content'>
        <div class='tab-pane active' id='lms-post-creator' role='tabpanel'
             aria-labelledby='{{ 'View Content for '. $post['title'] }}'
             aria-expanded='true'>
            <div class='row'>
                <div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        @if( !empty($post['content']->featuredImage()) )
                            <img
                                src="{{ Storage::url($post['content']->featuredImage()->file_path) }}"
                                class="card-img-top img-fluid height-400" style='object-fit: cover;'
                                alt="Featured Image"
                            />
                        @endif
                        <div class='card-header'>
                            <h2 class='fw-bolder text-primary'><span class='fw-normal text-secondary font-small-4 me-3'>Title: </span>
                                {{ $post['content']->title }}
                                {!! (!empty($post['content']->version))? "<span class='badge rounded-pill badge-light-primary px-1 pb-50'><small>v</small>{$post['content']->version}</span>":""  !!}
                            </h2>
                        </div>
                        <div class='card-body'>
                            <div class="row mb-2">
                                <div class="col">
                                    <span class='fw-normal text-secondary font-small-4 me-3'>Is Archived:</span>
                                    <strong class="ms-5">{{ $post['content']->is_archived === 1 ? "Yes": "No" }}</strong>
                                </div>
                            </div>
                            <div class='row mb-2'>
                                <div class="col-md-4"><span
                                        class='fw-normal text-secondary font-small-4 me-3'>Status:</span> <strong
                                        class="ms-5">{{ \Str::title($post['content']->status ?? "N/A") }}</strong></div>
                                <div class="col-md-4"><span class='fw-normal text-secondary font-small-4 me-3'>Visibility:</span>
                                    <strong
                                        class="ms-5">{{ \Str::title($post['content']->visibility ?? "N/A") }}</strong>
                                </div>
                                <div class="col-md-4"><span
                                        class='fw-normal text-secondary font-small-4 me-3'>Category:</span> <strong
                                        class="ms-5">{{ config( 'lms.course_category' )[$post['content']->category] ?? "N/A" }}</strong>
                                </div>
                            </div>
                            <div class='row mb-2'>
                                <span class='col-sm-12 mb-1 mt-1'><span class='fw-normal text-secondary font-small-4'>Content: </span> </span>
                                <span class='col-sm-12 p-2 border-light'>{!! $post['content']->lb_content !!}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='tab-pane' id='lms-post-organizer' role='tabpanel'
             aria-labelledby='{{ 'Organize '. $post['title'] }}'
             aria-expanded='false'>
            <div class='row'>
                <div class='col-md-12 col-12 mx-auto'>
                    <div class='card'>
                        <div class='card-body'>
                            <div class="clearfix divider divider-secondary divider-start-center ">
                                <span class="divider-text text-dark"> Additional Information</span>
                            </div>
                            @if( $post['type'] === 'course')
                                @include('content.lms.partials.show.course')
                            @elseif( $post['type'] === 'lesson')
                                @include('content.lms.partials.show.lesson')
                            @elseif( $post['type'] === 'topic')
                                @include('content.lms.partials.show.topic')
                            @elseif( $post['type'] === 'quiz')
                                @include('content.lms.partials.show.quiz')
                            @endif

                            @if( $post['type'] !== 'quiz')
                                @include('content.lms.partials.show.related')
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script src="{{ asset(mix('vendors/js/extensions/dragula.min.js')) }}"></script>
@endsection

@section('page-script')
    <!-- Page js files -->
    <script src="{{ asset(mix('js/scripts/_my/drag.js')) }}"></script>
    <script>
        $(function () {
            var drake = dragula([document.getElementById('handle-list-1'), document.getElementById('handle-list-2')], {
                moves: function (el, container, handle) {
                    console.log(handle);
                    return handle.classList.contains('handle');
                }
            });
            Drag.reorder(drake, '{{ \Str::plural($post['type']) }}', '{{ $post['content']['id'] }}', {
                item: 'li',
                prefixLength: 'item-'.length
            });
        });
    </script>
@endsection

