<div class="modal modal-slide-in fade" id="work-placement-sidebar" aria-hidden="true">
    <div class="modal-dialog sidebar-lg">
        <div class="modal-content p-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
            <div class="modal-header mb-1">
                <h5 class="modal-title">
                    <span class="align-middle">Add Work Placement</span>
                </h5>
            </div>
            <div class="modal-body flex-grow-1">
                <form id='work-placement-form' class='blockUI'>
                    @csrf
                    <div class="mb-1">
                        <input name="user_id" id="user_id" type="hidden" value="{{ $student->id }}"/>
                    </div>
                    <div class="source-item">
                        <div class='row'>
                            <div class="col-12 mb-1">
                                <label class="form-label required" for="role">Select Course</label>
                                <select data-placeholder="Select Course..." class="select2 form-select"
                                        data-class="@error('course_id') is-invalid @enderror"
                                        name="course_id" id="course_id" required="required">
                                    <option></option>
                                    @foreach($registeredCourses as $enrolment)
                                        @if($enrolment->is_main_course)
                                            <option value="{{ $enrolment->course->id }}"
                                                    data-start-date="{{ \Carbon\Carbon::parse($enrolment->getRawOriginal('course_start_at'))->timezone( Helper::getTimeZone() )->toDateString() }}"
                                                    data-end-date="{{ \Carbon\Carbon::parse($enrolment->getRawOriginal('course_ends_at'))->timezone( Helper::getTimeZone() )->toDateString() }}">
                                                {{ $enrolment->course->title }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('course_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="fw-light py-1" id="selected_course" style="display: none;">
                                    <span class="start_date"></span>
                                    <span class="end_date"></span>
                                    <input type="hidden" name="course_start_date" id="course_start_date" value=""/>
                                    <input type="hidden" name="course_end_date" id="course_end_date" value=""/>
                                </div>
                            </div>
                            <div class="col-12 mb-1">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="consultation_completed" id="consultation_completed" class="form-check-input"
                                           value="1"/>
                                    <label class="form-check-label" for="consultation_completed">Consultation
                                        Completed</label>
                                </div>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="consultation_completed_on">Consultation Completed
                                    On</label>
                                <input name="consultation_completed_on" id="consultation_completed_on" class="form-control date-picker" type="text"/>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="wp_commencement_date">Work Placement Commencement
                                    Date</label>
                                <input name="wp_commencement_date" id="wp_commencement_date" class="form-control date-picker" type="text"/>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="wp_end_date">Work Placement End Date</label>
                                <input name="wp_end_date" id="wp_end_date" class="form-control date-picker" type="text"/>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="employer_name">Employer Name</label>
                                <input name="employer_name" id="employer_name" class="form-control" type="text"/>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="employer_email">Employer Email</label>
                                <input name="employer_email" id="employer_email" class="form-control" type="email"/>
                            </div>
                            <div class="col-12 mb-1">
                                <label class="form-label" for="employer_phone">Employer Phone</label>
                                <input name="employer_phone" id="employer_phone" class="form-control" type="text"/>
                            </div>

                            <div class="col-12 mb-1">
                                <label class="form-label" for="employer_address">Employer Address</label>
                                <textarea name="employer_address" id="employer_address" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-12 mb-1">
                                <label class="form-label" for="employer_notes">Employer Notes</label>
                                <textarea name="employer_notes" id="employer_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap mb-3">
                        <button type="submit" class="btn btn-primary me-1">Proceed</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
