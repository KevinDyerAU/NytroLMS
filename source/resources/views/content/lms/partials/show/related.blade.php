<div class="clearfix divider divider-secondary divider-start-center ">
    <span class="divider-text text-dark"> Related {{ ucwords($related['type']) }}</span>
</div>
@if(count($related['lvl1']) > 0)
    <div class="blockUI">
        @include('content.lms.post.listing',['type' => $post['type'], 'list' => $related])
    </div>
@else
    <div class='row mb-2'>
        <span class='fw-bolder col-sm-4 text-start'>No {{ ucwords(\Str::singular($related['type'])) }} is associated with this {{ $post['title'] }} yet. </span>
        <span class='col-sm-8'>
            <a class='btn btn-outline-primary btn-sm'
           href='{{ route('lms.'.$related['type'].'.create') }}'>Create {{ ucwords(\Str::singular($related['type'])) }}</a>
        </span>
    </div>
@endif
