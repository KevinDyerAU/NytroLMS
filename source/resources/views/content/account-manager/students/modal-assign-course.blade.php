<div class="modal modal-slide-in fade" id="assign-course-sidebar" aria-hidden="true">
    <div class="modal-dialog sidebar-lg">
        <div class="modal-content p-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
            <div class="modal-header mb-1">
                <h5 class="modal-title">
                    <span class="align-middle">Assign Course</span>
                </h5>
            </div>
            <div class="modal-body flex-grow-1">
                <form id='assign-course-form' class='blockUI'
                    data-backdate="{{ !!auth()->user()->can('course backdate reg') }}">
                    @csrf
                    <div class="mb-1">
                        <label class="form-label" for="amount">Student</label>
                        <input name="student_id" type="hidden" value="{{ $student->id }}" />
                        <input name="student_name" class="form-control" type="text" value="{{ $student->name }}" disabled />
                        <hr class="my-2" />
                    </div>
                    <div class="source-item">
                        <div data-repeater-list="course">
                            @if (count($student->courseEnrolments) > 0)
                                @foreach ($student->courseEnrolments as $item)
                                    <div class="repeater-wrapper" data-repeater-item>
                                        <div class='row'>
                                            <div class="d-flex justify-content-end">
                                                <i data-lucide="x" class="cursor-pointer font-medium-3"
                                                    data-repeater-delete></i>
                                            </div>
                                            <div class="col-12 mb-1">
                                                <input name="id" type="hidden" value="{{ $item->id }}" />
                                                <label class="form-label required" for="role">Assign Course</label>
                                                <select data-placeholder="Assign Course..." class="select2 form-select"
                                                    data-class="@error('course_id') is-invalid @enderror"
                                                    name="course_id" required="required">
                                                    <option></option>
                                                    @php $category = '' @endphp
                                                    @foreach ($courses as $course)
                                                        @if ($category !== $course->category)
                                                            @if ($category !== '')
                                                                {{ '</optgroup>' }}
                                                            @endif
                                                            @php
                $categoryKey =
                    !empty($course->category) &&
                    isset(
                    config('lms.course_category')[
                        $course->category
                    ],
                )
                    ? $course->category
                    : 'uncategorized';
                $categoryLabel =
                    config('lms.course_category')[$categoryKey] ??
                    'Uncategorized';
                                                            @endphp
                                                            <optgroup label="{{ $categoryLabel }}">
                                                        @endif

                                                        <option data-length='{{ $course->course_length_days }}'
                                                            value="{{ $course->id }}"
                                                            data-category="{{ $course->category }}"
                                                            {{ intval($item->course_id) === intval($course->id) ? "selected='selected'" : '' }}>
                                                            {{ $course->title }}</option>

                                                        @php $category = $course->category  @endphp
                                                    @endforeach
                                                    </optgroup>
                                                </select>
                                                @error('course_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-12 mb-1">
                                                <label class="form-label" for="course_start_at">Course Start
                                                    Date</label>
                                                <input name="course_start_at" class="form-control date-picker"
                                                    type="text"
                                                    value='{{ \Carbon\Carbon::parse($item->getRawOriginal('course_start_at'))->timezone(Helper::getTimeZone())->toDateString() }}'
                                                    data-backdate="{{ !!auth()->user()->can('course backdate reg') }}"
                                                    required="required" />
                                            </div>
                                            <div class="col-12 mb-1">
                                                <label class="form-label" for="course_ends_at">Course End Date</label>
                                                <input name="course_ends_at" class="form-control date-picker-end"
                                                    data-minDate='{{ \Carbon\Carbon::parse($item->getRawOriginal('course_ends_at'))->timezone(Helper::getTimeZone())->toDateString() }}'
                                                    type="text"
                                                    value='{{ \Carbon\Carbon::parse($item->getRawOriginal('course_ends_at'))->timezone(Helper::getTimeZone())->toDateString() }}'
                                                    required="required" />
                                            </div>
                                            @can('allow semester only')
                                                <div class="col-12 mb-1">
                                                    <div class=' form-check form-switch'>
                                                        <input type="checkbox" name='allowed_to_next_course'
                                                            class="form-check-input" value='0'
                                                            {{ intval($item->allowed_to_next_course) === 1 ? '' : "checked='checked'" }} />
                                                        <label class="form-check-label" for="allowed_to_next_course">Only
                                                            Semester 1</label>
                                                    </div>
                                                </div>
                                            @endcan
                                            <div class="col-12 mb-1">
                                                <div class=' form-check form-switch'>
                                                    <input type="checkbox" name='deferred' class="form-check-input"
                                                        value='1'
                                                        {{ intval($item->deferred) === 1 ? "checked='checked'" : '' }} />
                                                    <label class="form-check-label" for="deferred">Deferred</label>
                                                </div>
                                            </div>
                                            <div class="col-12 mb-1">
                                                <div class=' form-check form-switch'>
                                                    <input type="checkbox" name='is_chargeable' class="form-check-input"
                                                        value='1'
                                                        {{ intval($item->is_chargeable) === 1 ? "checked='checked'" : '' }} />
                                                    <label class="form-check-label" for="is_chargeable">Is
                                                        Chargeable?</label>
                                                </div>
                                            </div>
                                            <div class="col-12 mb-1">
                                                <div class=' form-check form-switch'>
                                                    <input type="checkbox" name='is_locked' class="form-check-input"
                                                        value='1'
                                                        {{ $item->is_locked ? "checked='checked'" : '' }} />
                                                    <label class="form-check-label" for="is_locked">Lock
                                                        Enrollment</label>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                @endforeach
                            @else
                                <div class="repeater-wrapper" data-repeater-item>
                                    <div class='row'>
                                        <div class="d-flex justify-content-end">
                                            <i data-lucide="x" class="cursor-pointer font-medium-3"
                                                data-repeater-delete></i>
                                        </div>
                                        <div class="col-12 mb-1">
                                            <label class="form-label required" for="role">Assign Course</label>
                                            <select data-placeholder="Assign Course..." class="select2 form-select"
                                                data-class="@error('course_id') is-invalid @enderror" name="course_id"
                                                required="required">
                                                <option></option>
                                                @php $category = '' @endphp
                                                @foreach ($courses as $course)
                                                    @if ($category !== $course->category)
                                                        @if ($category !== '')
                                                            {{ '</optgroup>' }}
                                                        @endif
                                                        @php
            $categoryKey =
                !empty($course->category) &&
                isset(config('lms.course_category')[$course->category])
                ? $course->category
                : 'uncategorized';
            $categoryLabel =
                config('lms.course_category')[$categoryKey] ??
                'Uncategorized';
                                                        @endphp
                                                        <optgroup label="{{ $categoryLabel }}">
                                                    @endif
                                                    <option data-length='{{ $course->course_length_days }}'
                                                        data-category="{{ $course->category }}"
                                                        value="{{ $course->id }}">
                                                        {{ $course->title }}</option>
                                                    @php $category = $course->category  @endphp
                                                @endforeach
                                                </optgroup>
                                            </select>
                                            @error('course_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-12 mb-1">
                                            <label class="form-label" for="course_start_at">Course Start Date</label>
                                            <input name="course_start_at" class="form-control date-picker"
                                                type="text"
                                                data-backdate="{{ !!auth()->user()->can('course backdate reg') }}"
                                                required="required" />
                                        </div>
                                        <div class="col-12 mb-1">
                                            <label class="form-label" for="course_ends_at">Course End Date</label>
                                            <input name="course_ends_at" class="form-control date-picker-end"
                                                type="text" required="required" />
                                        </div>
                                        @can('allow semester only')
                                            <div class="col-12 mb-1">
                                                <div class=' form-check form-switch'>
                                                    <input type="checkbox" name='allowed_to_next_course'
                                                        class="form-check-input" value='0' />
                                                    <label class="form-check-label" for="allowed_to_next_course">Only
                                                        Semester 1</label>
                                                </div>
                                            </div>
                                        @endcan
                                        <div class="col-12 mb-1">
                                            <div class=' form-check form-switch'>
                                                <input type="checkbox" name='deferred' class="form-check-input"
                                                    value='1' />
                                                <label class="form-check-label" for="deferred">Deferred</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-1">
                                            <div class=' form-check form-switch'>
                                                <input type="checkbox" name='is_chargeable' class="form-check-input"
                                                    value='1' />
                                                <label class="form-check-label" for="is_chargeable">Is
                                                    Chargeable?</label>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                </div>
                            @endif
                        </div>
                        @if (auth()->user()->can('assign multi-course'))
                            <div class="row mt-1 mb-2">
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button"
                                        class="btn btn-secondary btn-outline-secondary btn-sm btn-add-new"
                                        data-repeater-create>
                                        <i data-lucide="plus" class="me-25"></i>
                                        <span class="align-middle">Add Course</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <button type="button" id="proceed-btn" class="btn btn-primary me-1">Proceed</button>
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                    </div>

                    <!-- Hidden input to track notification preference -->
                    <input type="hidden" name="notify_leader" id="notify_leader" value="1" />
                    <!-- Hidden container to receive selected course ids from confirmation modal -->
                    <div id="email-course-ids-container" style="display:none;"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirm-notification-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notify Leader?</h5>
                <i data-lucide="x" class="cursor-pointer font-medium-3" data-bs-dismiss="modal"
                    aria-label="Close"></i>
            </div>
            <div class="modal-body">
                Do you want to send a Confirmation Email to this Student's Leader after applying changes? </br></br>
                @php
                    $leader = $student->leaders()->first();
                    $leaderName = $leader && $leader->user ? $leader->user->name : 'N/A';
                @endphp
                Leader: {{ $leaderName }}
                </br></br>
                <div class="mb-1">
                    <label class="form-label">Include Information about the following Courses:</label>
                    <div id="modal-email-course-list" class="ms-1">
                        <!-- Courses will be dynamically populated here -->
                    </div>
                    <!-- Hidden baseline of existing enrolments for fallback -->
                    <div id="modal-existing-course-data" style="display:none">
                        @foreach ($student->courseEnrolments as $enrolment)
                            @if(!empty($enrolment->course))
                                <span data-course-id="{{ $enrolment->course->id }}" data-course-title="{{ $enrolment->course->title }}"></span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirm-yes">Yes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var confirmYesBtn = document.getElementById('confirm-yes');
    var container = document.getElementById('email-course-ids-container');
    var form = document.getElementById('assign-course-form');
    var confirmModal = document.getElementById('confirm-notification-modal');
    var courseList = document.getElementById('modal-email-course-list');

    function rebuildEmailCourseList() {
        if (!courseList) return;
        courseList.innerHTML = '';

        // Gather all selected courses from the repeater (includes existing + newly added)
        // Repeater renames inputs to course[0][course_id], so match both exact and ends-with
        var selects = Array.from(document.querySelectorAll('[data-repeater-list="course"] select[name$="[course_id]"]'))
            .concat(Array.from(document.querySelectorAll('[data-repeater-list="course"] select[name="course_id"]')));
        var seenCourseIds = new Set();

        selects.forEach(function (sel) {
            if (!sel || !sel.value) return;
            var courseId = sel.value;

            // Avoid duplicates
            if (seenCourseIds.has(courseId)) return;
            seenCourseIds.add(courseId);

            var title = (sel.options[sel.selectedIndex] || {}).text || '';

            var wrapper = document.createElement('div');
            wrapper.className = 'form-check';

            var input = document.createElement('input');
            input.className = 'form-check-input modal-email-course';
            input.type = 'checkbox';
            input.value = courseId;
            input.id = 'email-course-' + courseId;
            input.checked = true;

            var label = document.createElement('label');
            label.className = 'form-check-label';
            label.setAttribute('for', input.id);
            label.textContent = title.trim();

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            courseList.appendChild(wrapper);
        });

        // Fallback: also include existing enrolments from hidden data block
        var baselineNodes = document.querySelectorAll('#modal-existing-course-data [data-course-id]');
        baselineNodes.forEach(function (node) {
            var courseId = (node.getAttribute('data-course-id') || '').trim();
            var title = (node.getAttribute('data-course-title') || '').trim();
            if (!courseId || seenCourseIds.has(courseId)) return;
            seenCourseIds.add(courseId);

            var wrapper = document.createElement('div');
            wrapper.className = 'form-check';
            var input = document.createElement('input');
            input.className = 'form-check-input modal-email-course';
            input.type = 'checkbox';
            input.value = courseId;
            input.id = 'email-course-' + courseId;
            input.checked = true;
            var label = document.createElement('label');
            label.className = 'form-check-label';
            label.setAttribute('for', input.id);
            label.textContent = title;
            wrapper.appendChild(input);
            wrapper.appendChild(label);
            courseList.appendChild(wrapper);
        });

        // Update Yes button enabled state after building list
        updateConfirmYesState();
    }

    function updateConfirmYesState() {
        if (!confirmYesBtn) return;
        var anyChecked = document.querySelectorAll('.modal-email-course:checked').length > 0;
        confirmYesBtn.disabled = !anyChecked;
    }

    // Use jQuery event since modal is triggered with jQuery .modal('show')
    $('#confirm-notification-modal').on('show.bs.modal', function () {
        rebuildEmailCourseList();
        updateConfirmYesState();
    });

    // Delegate change handler to keep button state in sync
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('modal-email-course')) {
            updateConfirmYesState();
        }
    });

    if (confirmYesBtn && container && form) {
        confirmYesBtn.addEventListener('click', function () {
            // clear previous hidden inputs
            container.innerHTML = '';
            // gather checked in modal
            var checked = document.querySelectorAll('.modal-email-course:checked');
            checked.forEach(function (el) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'email_course_ids[]';
                input.value = el.value;
                container.appendChild(input);
            });
        });
    }
});
</script>
