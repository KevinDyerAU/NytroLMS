<div class='row'>
    <div class='col-12 mx-auto'>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Student Name:</span>
            <span class='col-sm-8'>{{ $report->user->name }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Course Name:</span>
            <span class='col-sm-8'>{{ $report->course->title }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Course Start:</span>
            <span class='col-sm-8'>{{ $report->course_start_date }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Course End:</span>
            <span class='col-sm-8'>{{ $report->course_end_date }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Consultation Completed:</span>
            <span class='col-sm-8'>{{ $report->consultation_completed ? 'Yes' : 'No' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Consultation Completed On:</span>
            <span class='col-sm-8'>{{ $report->consultation_completed_on ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>WP Commencement Date:</span>
            <span class='col-sm-8'>{{ $report->wp_commencement_date ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>WP End Date:</span>
            <span class='col-sm-8'>{{ $report->wp_end_date ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Leader:</span>
            <span class='col-sm-8'>{{ $report->leader ? $report->leader->name : '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Company:</span>
            <span class='col-sm-8'>{{ $report->company ? $report->company->name : '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Employer Name:</span>
            <span class='col-sm-8'>{{ $report->employer_name ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Employer Email:</span>
            <span class='col-sm-8'>{{ $report->employer_email ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Employer Phone:</span>
            <span class='col-sm-8'>{{ $report->employer_phone ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Employer Address:</span>
            <span class='col-sm-8'>{{ $report->employer_address ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Employer Notes:</span>
            <span class='col-sm-8'>{{ $report->employer_notes ?? '' }}</span>
        </div>
        <div class='row mb-1'>
            <span class='fw-bolder col-sm-4 text-end'>Created By:</span>
            <span class='col-sm-8'>{{ $report->creator ? $report->creator->name : '' }}</span>
        </div>
    </div>
</div>
