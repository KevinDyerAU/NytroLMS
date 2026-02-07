<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Associated Lesson:</span>
    <span class='col-sm-8'>
        <a href='{{ route('lms.'.\Str::plural($post['parent']).'.show', $post['content']->lesson->id) }}'>{{ $post['content']->lesson->title }}</a>
    </span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Estimated Time:</span>
    <span class='col-sm-8'>{{ $post['content']->estimated_time }}</span>
</div>
