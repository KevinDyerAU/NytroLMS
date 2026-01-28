{{--
    DIGEST MODAL - Reference Material Viewer
    This modal displays reference material (lessons and topics) for a quiz.
    It provides a tabbed interface to navigate between lesson content and related topics.

    Accessed via Read Lesson/Topics button in quiz.blade.php
--}}


{{-- Full-screen modal container with Bootstrap modal structure --}}
<div class="modal fade" id="digestModal" tabindex="-1" aria-hidden="true">
    {{-- Modal dialog with fullscreen class for maximum viewing area --}}
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
            {{-- Modal header with title and close button --}}
            <div class="modal-header">
                {{-- Dynamic title showing the quiz name from the $post variable --}}
                <h5 class="modal-title" id="digestTitle">Reference material for quiz: <strong>{{ $post->title }}</strong>
                </h5>
                {{-- Close button with tooltip and accessibility attributes --}}
                <button type="button" class="btn-close bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="Go back to your quiz" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Modal body containing the main content area --}}
            <div class="modal-body" id="digestBody">
                <div class="row">
                    {{-- Left sidebar: Navigation list for lessons and topics --}}
                    <div class="col-md-4 col-12 mb-4 mb-md-0">
                        {{-- Sticky navigation list that stays in view while scrolling --}}
                        <div class="list-group sticky-top">
                            {{-- Main lesson navigation item (always active by default) --}}
                            <a class="list-group-item list-group-item-action active"
                                id="lesson-content-{{ $post->lesson->id }}" data-bs-toggle="list"
                                href="#lesson{{ $post->lesson->id }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">{{ $post->lesson->title }}</h5>
                                    <small>Lesson</small>
                                </div>
                            </a>

                            {{-- Loop through topics if they exist for this lesson --}}
                            @if (!empty($post->lesson->topics))
                                @foreach ($post->lesson->topics as $topic)
                                    {{-- Individual topic navigation items with indentation (ps-3 class) --}}
                                    <a class="ps-3 list-group-item list-group-item-action"
                                        id="topic-content-{{ $topic->id }}" data-bs-toggle="list"
                                        href="#topic{{ $topic->id }}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1">{{ $topic->title }}</h5>
                                            <small>Topic</small>
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Right content area: Tabbed content display --}}
                    <div class="col-md-8 col-12">
                        {{-- Bootstrap tab content container --}}
                        <div class="tab-content">
                            {{-- Main lesson content tab (active by default) --}}
                            <div class="tab-pane fade show active" id="lesson{{ $post->lesson->id }}">
                                <h4 class="mb-1">{{ $post->lesson->title }}</h4>
                                {{-- Raw HTML content from Laraberg editor (lb_content) with image max-width styling --}}
                                <div class="digest-content">
                                    {!! $post->lesson->lb_content !!}
                                </div>
                            </div>

                            {{-- Loop through topics to create individual content tabs --}}
                            @if (!empty($post->lesson->topics))
                                @foreach ($post->lesson->topics as $topic)
                                    {{-- Individual topic content tab --}}
                                    <div class="tab-pane fade" id="topic{{ $topic->id }}">
                                        <h4 class="mb-1">{{ $topic->title }}</h4>
                                        {{-- Raw HTML content from Laraberg editor for this topic with image max-width styling --}}
                                        <div class="digest-content">
                                            {!! $topic->lb_content !!}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal footer with close button --}}
            <div class="modal-footer">
                {{-- Close button with tooltip and styling --}}
                <button type="button" class="btn btn-label-dark btn-secondary" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Go back to your quiz" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- CSS styling for digest content images --}}
<style>
    .digest-content img {
        max-width: 600px !important;
    }
</style>
