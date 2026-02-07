<div class='row'>
    <div class='col-12 border-2 border-light'>
        <div class="mb-1">
            <div class="row">
                <div class='col-md-6 col-12'>
                    <div class='mb-1'>
                        <label class="form-label required" for="lesson_id">Associate Course:</label>
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
                                data-lesson="{{ empty($post) ? "": $post->lesson_id}}"
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
                <div class='col-md-6 col-12'>
                    <div class='mb-1'>

                        <label class="form-label required" for="topic_id">Associate Topic:</label>
                        <select data-placeholder="Select a Topic"
                                class="select2 form-select" data-class="@error('topic_id') is-invalid @enderror"
                                data-topic="{{ empty($post) ? "": $post->topic_id }}"
                                id="topic_id"
                                name='topic_id' tabindex='5'>
                            <option></option>
                            @if(!empty($post->topic_id) && !empty($post->lesson_id))
                                @foreach(\App\Models\Topic::where('lesson_id',$post->lesson_id)->get() as $topic)
                                    <option value="{{ $topic->id }}"
                                        {{
                                            (( old('lesson_id') == intval($topic->id) ) ? 'selected=selected':
                                            ((!empty($post) && intval($post->topic_id) === intval($topic->id)) ? 'selected=selected' : ''))
                                        }}>
                                        {{ ucwords($topic->title) }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <div class='mb-1'>
            <x-forms.input name="estimated_time" input-class="" label-class="required" tabindex="6"
                           type="number" step="any"
                           value="{{ old('estimated_time')??$post->estimated_time??00.00 }}"
            ></x-forms.input>
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <div class='mb-1'>
            <x-forms.input name="passing_percentage" input-class="" label-class="required" tabindex="7"
                           type="number"
                           value="{{ old('passing_percentage')??$post->passing_percentage??0 }}"
            ></x-forms.input>
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <div class='mb-1'>
            <x-forms.input name="allowed_attempts" input-class="" label-class="required" tabindex="8"
                           type="number"
                           value="{{ old('allowed_attempts')??$post->allowed_attempts??3 }}"
            ></x-forms.input>
        </div>
    </div>
</div>

@can('upload checklist')
    <div class="row">
        <div class="col-md-6 mt-2 mb-5 border-2 border-light">
            <p class="fw-small"><small>Does this quiz require:</small></p>
            <div class="mb-1 form-check custom-option custom-option-basic">
                <label class="form-check-label custom-option-content" for="has_checklist">
                    <input class="form-check-input" type="checkbox" name="has_checklist"
                           value="1" id="has_checklist"
                        {{ (( old('has_checklist') == 1 ) ? 'checked':
                                 ((!empty($post) && intval($post->has_checklist) === 1) ? 'checked' : '')) }}
                    />
                    <span class="custom-option-header">
                  <span class="fw-medium">Checklist</span>
                  <span class="fw-medium text-muted">(Upload)</span>
                </span>
                </label>
            </div>
        </div>
    </div>
@endcan
