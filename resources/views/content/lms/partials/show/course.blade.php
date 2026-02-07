<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Course Version:</span>
    <span class='col-sm-8 fw-bolder'><small>v</small>{{ $post['content']->version }}</span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Course Length:</span>
    <span class='col-sm-8'>{{ $post['content']->course_length_days }} Days</span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Course Expiry:</span>
    <span class='col-sm-8'>{{ $post['content']->course_expiry_days }} Days</span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Next linked course (Next Semester):</span>
    <span
        class='col-sm-8'>{{ \App\Models\Course::where('id',$post['content']->next_course)->first()?->title??" N/A" }}</span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Next Semester Length:</span>
    <span class='col-sm-8'>{{ $post['content']->next_course_after_days }} Days</span>
</div>
<div class='row mb-2'>
    <span class='fw-bolder col-sm-4 text-start'>Auto register to next semester:</span>
    <span
        class='col-sm-8'>{{ $post['content']->auto_register_next_course>0?"Yes":"No" }}</span>
</div>
@if( $post['content']->published_at )
    <div class='row mb-2'>
        <span class='fw-bolder col-sm-4 text-start'>Published At:</span>
        <span class='col-sm-8'>{{ \Carbon\Carbon::parse($post['content']->getRawOriginal('published_at'))->format( 'j F, Y g:i A' ) }}</span>
    </div>
@endif
@can('set course restriction')
    <div class='row mb-2'>
        <span class='fw-bolder col-sm-4 text-start'>Restricted Roles:</span>
        <span class='col-sm-8'>
            @if(!empty($post['content']->restricted_roles))
                <ul class='list-unstyled d-flex flex-column'>
                    @foreach(\App\Models\Role::whereIn('id',$post['content']->restricted_roles)->get() as $role)
                        <li>{{ $role->name }}</li>
                    @endforeach
                </ul>
            @else
                <span class="fw-normal">Not any</span>
            @endif
        </span>
    </div>
@endcan
