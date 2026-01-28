<div class="border-top-light border-top-2 pt-1 mt-1">
    @if(!$competency->is_competent)
        <h5 class="p-1 bg-light text-purple">The student is assessed as competent in all the assessment requirements for this unit of competency.</h5>
        <form id="LessonCompetentForm{{ $competency->lesson_id }}" class="my-1">
            <label for="endDate{{ $competency->lesson_id }}">Set Lesson End Date: </label>
            <input type="text" name="endDate{{ $competency->lesson_id }}" id="endDate{{ $competency->lesson_id }}"
                   data-mindate="{{ \Carbon\Carbon::parse($competency->lesson_end)->lessThan('1-1-2025')?'2025-1-1':$competency->lesson_end }}"
                   data-enddate="{{ $competency->lesson_end }}"
                   class="form-control date-picker"
                   required="required" />
            <label for="remarks{{ $competency->lesson_id }}">Your Remarks: </label>
            <textarea class="form-control mb-1" id="remarks{{ $competency->lesson_id }}"></textarea>
            <button class="btn btn-purple"
                    onclick="LMS.MarkLessonCompetent({{ $competency->lesson_id.','. $competency->user_id }})">Mark
                Competent
            </button>
        </form>
    @else
        <h5 class="p-1 bg-light-success text-success">Lesson marked competent already</h5>
        <div class="card">
            <div class="card-body">
                <div class='row mb-2'>
                    <span class='fw-bolder me-25 col col-sm-4 text-start'>Marked Competent on:</span>
                    <span class='col col-sm-6'>
                        {{ \Carbon\Carbon::parse( $competency->getRawOriginal( 'competent_on' ) )
                             ->timezone( Helper::getTimeZone() )
                             ->format( 'j F, Y' ) }}
                    </span>
                </div>
                @if(!empty($competency->notes))
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col col-sm-4 text-start'>Marked By:</span>
                        <span class='col col-sm-6'>
                        {{ $competency->notes['added_by']['user_name'] }}
                    </span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col col-sm-4 text-start'>Marked By (User Role):</span>
                        <span class='col col-sm-6'>
                        {{ $competency->notes['added_by']['role']?? '' }}
                    </span>
                    </div>
                    <div class='row mb-2'>
                        <span class='fw-bolder me-25 col col-sm-4 text-start'>Remarks:</span>
                        <span class='col col-sm-6'>
                        {{ $competency->notes['remarks']?? '' }}
                    </span>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
