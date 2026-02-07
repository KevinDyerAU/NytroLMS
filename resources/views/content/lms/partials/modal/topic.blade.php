<div class='row'>
    <div class='col-12 border-2 border-light'>
        <div class="row">
            @if(empty($post->course_id) || (!empty($post->course_id) && \App\Models\CourseProgress::where('course_id',$post->course_id)->count() < 1))

                <div class='col-md-6 col-12'>
                    <div class='mb-1'>
                        <label class="form-label required" for="course_id">Associate Course:</label>
                        <select data-placeholder="Select a Course"
                                class="select2 form-select required"
                                data-course="{{ empty($post) ? "": $post->course_id }}"
                                data-class="@error('course_id') is-invalid @enderror"
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
                    </div>
                </div>
                <div class='col-md-6 col-12'>
                    <div class='mb-1'>
                        <label class="form-label required" for="lesson_id">Associate Lesson:</label>
                        <select data-placeholder="Select a Lesson"
                                class="select2 form-select" data-class="@error('lesson_id') is-invalid @enderror"
                                data-lesson="{{ empty($post) ? "": $post?->lesson_id}}"
                                id="lesson_id"
                                name='lesson_id' tabindex='4'>
                            <option></option>
                            @if(!empty($post->course_id) && !empty($post->lesson_id))
                                @foreach(\App\Models\Lesson::where('course_id',$post->course_id)->get() as $lesson)
                                    <option value="{{ $lesson->id }}"
                                        {{
                                            (( old('lesson_id') == intval($lesson->id) ) ? 'selected=selected':
                                            ((!empty($post) && intval($post->lesson_id) === intval($lesson->id)) ? 'selected=selected' : ''))
                                        }}>
                                        {{ ucwords($lesson->title) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('lesson_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @else
                <div class='col-md-6 col-12'>
                    <div class='mb-1'>
                        <label class="form-label required" for="lesson_id">Associate Course/Lesson:</label>
                        <input type="hidden" name="course_id" value="{{ $post->course_id??0 }}"/>
                        <input type="hidden" name="lesson_id" value="{{ $post->lesson_id??0 }}"/>
                        <p>{{ 'Course: '.$post->course->title.'<br/> Lesson: '.$post->lesson->title }}</p>
                        <p class="text-muted"><span class="text-danger">Unable to change.</span><small> Reason:
                                Student(s)
                                Already Enrolled.</small></p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <div class='mb-1'>
            <x-forms.input name="estimated_time" input-class="" label-class="required" tabindex="5"
                           type="number" step="any"
                           value="{{ old('estimated_time')??$post->estimated_time??00.00 }}"
            ></x-forms.input>
        </div>
    </div>
</div>
