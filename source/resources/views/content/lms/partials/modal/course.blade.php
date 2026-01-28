<div class='row'>
    <div class='col-md-6 col-12 border-2 border-light'>
        <div class="mb-1">
            <label class="form-label" for="next_course">Select course for next semester, if any:</label>
            <select data-placeholder="Select a course"
                    class="select2 form-select" data-class="@error('next_course') is-invalid @enderror"
                    id="next_course"
                    name='next_course' tabindex='3'>
                <option value='0'>None</option>
                @php $category = '' @endphp
                @foreach(App\Models\Course::where('next_course',0)->notRestricted()->orderBy('category', 'asc')->get() as $course)

                    @if($category !== $course->category)
                        @if($category !== '')
                            {{ "</optgroup>" }}
                        @endif
                        <optgroup label="{{ config( 'lms.course_category' )[(!empty($course->category) ? $course->category : 'uncategorized')] }}">
                    @endif

                    <option value="{{ $course->id }}"
                        {{
                            (( old('next_course') == $course->id ) ? 'selected=selected':
                            ((!empty($post) && $post->next_course === $course->id) ? 'selected=selected' : ''))
                        }}>
                        {{ ucwords($course->title) }}
                    </option>

                    @php $category = $course->category  @endphp
                @endforeach
                </optgroup>
            </select>
            @error('next_course')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class='row' id='show_next_course' style='display: none'>
            <div class='mb-1'>
                <x-forms.input name="next_course_after_days" input-class="" label-class="required" tabindex="4"
                               type="number"
                               value="{{ old('next_course_after_days')??$post->next_course_after_days??'' }}"
                ></x-forms.input>
            </div>
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <div class='mb-1'>
            <x-forms.input name="course_length_days" input-class="" label-class="required" type="number"
                       value="{{ old('course_length_days')??$post?->course_length_days??90 }}"
                       tabindex="5"></x-forms.input>
        </div>
        <div class='mb-1'>
            <x-forms.input name="course_expiry_days" input-class="" label-class="required" tabindex="4"
                           type="number"
                           value="{{ old('course_expiry_days')??$post->course_expiry_days??(!empty($post->course_length_days) ? $post->course_length_days *2 : '') }}"
            ></x-forms.input>
        </div>
    </div>
    <div class='col-md-6 col-12'>
        <h4 class="form-label">Visibility</h4>
        <div class="row custom-options-checkable g-1">
            <div class="col-md-6">
                <input
                    class="custom-option-item-check"
                    type="radio"
                    name="visibility"
                    id="visibility_public"
                    value="PUBLIC"
                    tabindex="7"
                    {{ (((!empty($post) && $post->visibility === 'PUBLIC') || old('visibility') === 'PUBLIC')?"checked":(empty($post)?"checked":""))}}
                />
                <label class="custom-option-item p-1" for="visibility_public">
              <span class="d-flex justify-content-between flex-wrap mb-50">
                <span class="fw-bolder">Public</span>
                <span class="fw-bolder"></span>
              </span>
                    <small class="d-block">Allow to appear for course assignment</small>
                </label>
            </div>

            <div class="col-md-6">
                <input
                    class="custom-option-item-check"
                    type="radio"
                    name="visibility"
                    id="visibility_private"
                    value="PRIVATE"
                    tabindex="8"
                    {{ ((!empty($post) && $post->visibility === 'PRIVATE')|| old('visibility') === 'PRIVATE') ?"checked":""}}
                />
                <label class="custom-option-item p-1" for="visibility_private">
              <span class="d-flex justify-content-between flex-wrap mb-50">
                <span class="fw-bolder">Private</span>
                <span class="fw-bolder"></span>
              </span>
                    <small class="d-block">Block to appear for course assignment</small>
                </label>
            </div>
        </div>
    </div>

    <div class='col-md-6 col-12 mb-3'>
        <h4 class="form-label">Status</h4>
        <div class="row custom-options-checkable g-1">
            <div class="col-md-6">
                <input
                    class="custom-option-item-check"
                    type="radio"
                    name="status"
                    id="status_publish"
                    value="PUBLISHED"
                    tabindex="9"
                    {{ (((!empty($post) && $post->status === 'PUBLISHED') || old('status') === 'PUBLISHED')?"checked":"")}}
                />
                <label class="custom-option-item p-1" for="status_publish">
              <span class="d-flex justify-content-between flex-wrap mb-50">
                <span class="fw-bolder">Publish</span>
                <span class="fw-bolder"></span>
              </span>
                    <small class="d-block">Allow to appear for course assignment</small>
                </label>
            </div>

            <div class="col-md-6">
                <input
                    class="custom-option-item-check"
                    type="radio"
                    name="status"
                    id="status_draft"
                    value="DRAFT" tabindex="10"
                    {{ (((!empty($post) && $post->status === 'DRAFT')|| old('status') === 'DRAFT') ?"checked":(empty($post)?"checked":""))}}
                />
                <label class="custom-option-item p-1" for="status_draft">
              <span class="d-flex justify-content-between flex-wrap mb-50">
                <span class="fw-bolder">Draft</span>
                <span class="fw-bolder"></span>
              </span>
                    <small class="d-block">Block to appear for course assignment</small>
                </label>
            </div>
        </div>
    </div>

    <div class='col-md-6 col-12'>
        @can('manage course version')
        <div class='mb-1'>
            <x-forms.input name="version" input-class="" label-class="required" type="number"
                           value="{{ old('version')??$post->version??1 }}"
                           tabindex="11"></x-forms.input>
        </div>
        @endcan
        <div class="mb-1">
            <label class="form-label" for="next_course">Select roles restricted to access this course:</label>
            <select data-placeholder="Select any roles"
                    class="select2 form-select" data-class="@error('restricted_roles') is-invalid @enderror"
                    id="restricted_roles" name='restricted_roles[]' tabindex='12' multiple>
                <option value='0'>None</option>
                @foreach(App\Models\Role::whereNotIn('name',['Root', 'Student'])->get() as $role)
                    <option value="{{ $role->id }}"
                        {{
                            (( collect(old('restricted_roles'))->contains($role->id) ) ? 'selected=selected':
                            ((!empty($post) && in_array($role->id, $post->restricted_roles ?? []) ) ? 'selected=selected' : ''))
                        }}>
                        {{ ucwords($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('restricted_roles')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class='col-md-6 col-12'>
        <div class="mb-1">
            <label class="form-label required" for="category">Select Category:</label>
            <select data-placeholder="Select a Category"
                    class="select2 form-select" data-class="@error('category') is-invalid @enderror"
                    id="category" name='category' tabindex='13'>
                <option></option>
                @foreach(config( 'lms.course_category' ) as $key => $title)
                    <option value="{{ $key }}"
                        {{
                            (( collect(old('category'))->contains($key) ) ? 'selected=selected':
                            (( !empty($post) && $key ===  $post->category ) ? 'selected=selected' : ''))
                        }}>
                        {{ ucwords($title) }}
                    </option>
                @endforeach
            </select>
            @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class='mb-1'>
            <h5 class="form-label pt-1 fw-normal" >Set Course Archive Status:</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_archived"
                       name='is_archived' value='1' tabindex="6"
                       @if(old('is_archived') === 1 || (!empty($post) && intval($post->is_archived) === 1)) checked="checked" @endif />
                <label class="form-check-label" for="is_archived">Is Archive?</label>
            </div>
        </div>
    </div>
</div>
