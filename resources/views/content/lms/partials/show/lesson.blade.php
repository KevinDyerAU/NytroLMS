<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Associated Course:</span>
    <span class='col-sm-8'>
        <a href='{{ route('lms.'.\Str::plural($post['parent']).'.show', $post['content']->course->id) }}'>{{ $post['content']->course->title }}</a>
    </span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Release Schedule:</span>
    <span class='col-sm-8'>
        <span class="fw-normal fw-medium">
            @if( $post['content']->release_key === "XDAYS")
                The lesson will be available {{ $post['content']->release_value }} days after course enrollment.
            @elseif( $post['content']->release_key === "DATE")
                The lesson will be available on {{ \Carbon\Carbon::parse($post['content']->release_value)->format('j F, Y') }}.
            @else
                The lesson is made available on course enrollment.
            @endif
        </span>
    </span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Does this lesson has Work Placement:</span>
    <span class='col-sm-8'>
       <span class="fw-normal fw-medium">{{ ($post['content']->has_work_placement ? 'Yes': 'No') }}</span>
    </span>
</div>
