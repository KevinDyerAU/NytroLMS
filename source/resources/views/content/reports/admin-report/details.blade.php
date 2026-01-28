<div class='row'>
    <div class='col-12 mx-auto'>
        @if(!empty($report->student_details) && $report->student_details->count() > 0)
            <div class="clearfix divider divider-secondary divider-start-center ">
                <span class="divider-text text-dark">Student</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Name:</span>
                <span class='col-sm-6'>{{ $report->student_details['name'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                <span class='col-sm-6'>{{ $report->student_details['email'] }}</span>
            </div>
            @if(strlen($report->student_details['phone']) > 3)
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                    <span class='col-sm-6'>{{ $report->student_details['phone'] }}</span>
                </div>
            @endif
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                <span class='col-sm-6'>{{ $report->student_details['address'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Status:</span>
                <span class='col-sm-6'>{{ $report->student_status??"N/A" }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Last Active:</span>
                <span class='col-sm-6'>{{ $report->student_last_active??"Not Yet" }}</span>
            </div>
    </div>
    @endif
    @if(!empty($report->trainer_details) && $report->trainer_details->count() > 0)
        <div class='col-12 mx-auto'>
            <div class="clearfix divider divider-secondary divider-start-center ">
                <span class="divider-text text-dark">Trainer</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Name:</span>
                <span class='col-sm-6'>{{ $report->trainer_details['name'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                <span class='col-sm-6'>{{ $report->trainer_details['email'] }}</span>
            </div>
            @if(strlen($report->trainer_details['phone']) > 3)
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                    <span class='col-sm-6'>{{ $report->trainer_details['phone'] }}</span>
                </div>
            @endif
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                <span class='col-sm-6'>{{ $report->trainer_details['address'] }}</span>
            </div>
        </div>
    @endif
    @if(!empty($report->leader_details) && $report->leader_details->count() > 0)
        <div class='col-12 mx-auto'>
            <div class="clearfix divider divider-secondary divider-start-center ">
                <span class="divider-text text-dark">Leader</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Name:</span>
                <span class='col-sm-6'>{{ $report->leader_details['name'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                <span class='col-sm-6'>{{ $report->leader_details['email'] }}</span>
            </div>
            @if(strlen($report->leader_details['phone']) > 3)
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                    <span class='col-sm-6'>{{ $report->leader_details['phone'] }}</span>
                </div>
            @endif
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                <span class='col-sm-6'>{{ $report->leader_details['address'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Last Active:</span>
                <span class='col-sm-6'>{{ $report->leader_last_active??"Not Yet" }}</span>
            </div>
        </div>
    @endif
    @if(!empty($report->company_details) && $report->company_details->count() > 0)
        <div class='col-12 mx-auto'>
            <div class="clearfix divider divider-secondary divider-start-center ">
                <span class="divider-text text-dark">Company</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Name:</span>
                <span class='col-sm-6'>{{ $report->company_details['name'] }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Email:</span>
                <span class='col-sm-6'>{{ $report->company_details['email'] }}</span>
            </div>
            @if(strlen($report->company_details['number']) > 3)
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Phone:</span>
                    <span class='col-sm-6'>{{ $report->company_details['number'] }}</span>
                </div>
            @endif
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Address:</span>
                <span class='col-sm-6'>{{ $report->company_details['address'] }}</span>
            </div>
        </div>
    @endif
    <div class='col-12 mx-auto'>
        <div class="clearfix divider divider-secondary divider-start-center ">
            <span class="divider-text text-dark">Course Details</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder me-25 col-sm-4 text-end'>Title:</span>
            <span class='col-sm-6'>{{ $report->course_details['title'] }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder me-25 col-sm-4 text-end'>Status:</span>
            <span class='col-sm-6'>{{ $report->course_status }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder me-25 col-sm-4 text-end'>Start Date:</span>
            <span class='col-sm-6'>{{ $report->student_course_start_date }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder me-25 col-sm-4 text-end'>End Date:</span>
            <span class='col-sm-6'>{{ $report->student_course_end_date }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder me-25 col-sm-4 text-end'>Semester 1 Only:</span>
            <span class='col-sm-6'>{{ $report->allowed_to_next_course ? "No" : "Yes" }}</span>
        </div>
        @if(!empty($report->student_course_progress) && $report->student_course_progress->count() > 0)
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Current Progress:</span>
                <span class='col-sm-6'>{{ $report->student_course_progress['current_course_progress']??0 }}%</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Expected Progress:</span>
                <span class='col-sm-6'>{{ $report->student_course_progress['expected_course_progress']??0 }}%</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Total Assignments:</span>
                <span class='col-sm-6'>{{ $report->student_course_progress['total_assignments']??0 }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Pending Assignments:</span>
                <span class='col-sm-6'>{{ $report->student_course_progress['total_assignments_remaining']??0 }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Satisfactory Assignments:</span>
                <span
                    class='col-sm-6'>{{ $report->student_course_progress['total_assignments_satisfactory']??0 }}</span>
            </div>
            <div class='row mb-1'>
                <span class='fw-bolder me-25 col-sm-4 text-end'>Not Satisfactory Assignments:</span>
                <span
                    class='col-sm-6'>{{ $report->student_course_progress['total_assignments_not_satisfactory']??0 }}</span>
            </div>
            @if($report->student_course_progress??['hours_details'] !== null)
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Total Course Time:</span>
                    <span class="col-sm-6">
                    {{ $report->student_course_progress['hours_details']['actual']['hours'].":". $report->student_course_progress['hours_details']['actual']['minutes']}}

                    </span>
                </div>
                <div class='row mb-1'>
                    <span class='fw-bolder me-25 col-sm-4 text-end'>Reported Time:</span>
                    <span class="col-sm-6">
                    {{ $report->student_course_progress['hours_details']['reported']['hours'].":". $report->student_course_progress['hours_details']['reported']['minutes']}}

                    </span>
                </div>
            @else
                <div class='row mb-1'>
                    <span class="col-sm-12">No hours log available</span>
                </div>
            @endif
        @else
            <div class='row mb-1'>
                <span class="col-sm-12">No course progress available</span>
            </div>
        @endif
    </div>
</div>
