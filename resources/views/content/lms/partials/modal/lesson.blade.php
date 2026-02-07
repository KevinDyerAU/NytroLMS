<div class='row'>
    <div class='col-md me-1 border-2 border-light'>
        <div class="mb-1">
            <label class="form-label required" for="course_id">Release Schedule:</label>
            <select name="release_key" id="release_key" data-class="@error('release_key') is-invalid @enderror"
                    class="select2 form-select">
                <option value="IMMEDIATE" {{
                                (( old('release_key') == "IMMEDIATE" ) ? 'selected=selected':
                                ((!empty($post) && $post->release_key === "IMMEDIATE") ? 'selected=selected' : ''))
                            }}>Immediately - <span>The lesson is made available on course enrollment.</span></option>
                <option value="XDAYS" {{
                                (( old('release_key') == "XDAYS" ) ? 'selected=selected':
                                ((!empty($post) && $post->release_key === "XDAYS") ? 'selected=selected' : ''))
                            }}>Enrollment-based -
                    <span>The lesson will be available X days after course enrollment.</span></option>
                <option value="DATE" {{
                                (( old('release_key') == "DATE" ) ? 'selected=selected':
                                ((!empty($post) && $post->release_key === "DATE") ? 'selected=selected' : ''))
                            }}>Specific date - <span>The lesson will be available on a specific date.</span></option>
            </select>
        </div>
        <div class="mb-1" id="release_schedule" style="display: none;">
            <div class="d-flex flex-row">
                <input name="release_value" id="release_value"
                       class="form-control  @error('release_value') is-invalid @enderror"/>
                <label id="release_value_label" for="release_value" class="mt-1 ms-2"> day(s)</label>
            </div>
        </div>
    </div>
    <div class='col-md ms-1 me-1 border-2 border-light'>
        <div class="mb-1">
            <label class="form-label required" for="course_id">Associate Course:</label>
            @if(empty($post->course) || (!empty($post->course) && \App\Models\CourseProgress::where('course_id',$post->course->id)->count() < 1))
                <select data-placeholder="Select a course"
                        class="select2 form-select" data-class="@error('course_id') is-invalid @enderror"
                        id="course_id"
                        name='course_id' tabindex='3'>
                    <option></option>
                    @php $category = '' @endphp
                    @foreach(\App\Models\Course::notRestricted()->orderBy('category', 'asc')->get() as $course)

                        @if($category !== $course->category)
                            @if($category !== '')
                                {{ "</optgroup>" }}
                            @endif
                            <optgroup label="{{ config( 'lms.course_category' )[(!empty($course->category) ? $course->category : 'uncategorized')] }}">
                        @endif

                        <option value="{{ $course->id }}"
                            {{
                                        (( old('course_id') == intval($course->id) ) ? 'selected=selected':
                                        ((!empty($post) && intval($post->course_id) === intval($course->id)) ? 'selected=selected' : ''))
                                    }}>
                            {{ ucwords($course->title) }}
                        </option>
                            @php $category = $course->category  @endphp
                        @endforeach
                    </optgroup>
                </select>
                @error('course_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            @else
                <input type="hidden" name="course_id" value="{{ $post->course_id }}"/>
                <p>{{ $post->course->title }}</p>
                <p class="text-muted"><span class="text-danger">Unable to change.</span><small> Reason: Student(s)
                        Already Enrolled.</small></p>
            @endif
        </div>
    </div>
</div>
@can('mark work placement')
<div class="row">
    <div class="col-md-6 mt-2 mb-5 border-2 border-light">
        <p class="fw-small"><small>Does this lesson has:</small></p>
        <div class="mb-1 form-check custom-option custom-option-basic">
            <label class="form-check-label custom-option-content" for="has_work_placement">
                <input class="form-check-input" type="checkbox" name="has_work_placement"
                       value="1" id="has_work_placement"
                       {{ (( old('has_work_placement') == 1 ) ? 'checked':
                                ((!empty($post) && $post->has_work_placement == 1) ? 'checked' : '')) }}
                />
                <span class="custom-option-header">
                  <span class="fw-medium">Work Placement</span>
                  <span class="fw-medium text-muted">(WP)</span>
                </span>
            </label>
        </div>
    </div>
</div>
@endcan
