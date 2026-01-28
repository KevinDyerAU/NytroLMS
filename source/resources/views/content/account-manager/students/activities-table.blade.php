<form class="dt_adv_search col-10 position-relative" id="filterForm">
    <div class="row g-1 mb-1">
        <div class="col col-md-4">
            <label class="form-label">Select Date:</label>
            <div id="reportrange" class="form-control" data-column='2'>
                <i class="fa fa-calendar"></i>&nbsp;
                <span></span> <i class="fa fa-caret-down"></i>
                <input id="start_date" type="hidden" data-column='2' name="start_date"/>
                <input id="end_date" type="hidden" data-column='2' name="end_date"/>
            </div>
        </div>
        <div class="col col-md-4">
            <label class="form-label" for="period">Select Period:</label>
            <select class="select2 form-select" id="period" name='period'>
                <option
                    value="weekly" {{ ( strtolower(request('period')) === 'monthly') ?'':'selected' }}>
                    Weekly
                </option>
                <option
                    value="monthly" {{ ( strtolower(request('period')) === 'monthly') ?'selected':'' }}>
                    Monthly
                </option>
            </select>
        </div>
        <div class="col col-md-4 pt-1">
            <button type="button"
                    class="btn btn-primary btn-sm mt-1 me-1 waves-effect waves-float waves-light"
                    id="submitFilters">Submit
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-1 waves-effect"
                    id="clearFilters">Reset
            </button>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped" id="student-activities-table">
    <thead>
    <tr>
        <th>User ID</th>
        <th>Activity Period</th>
        <th>Total Hours</th>
        <th>Activity Duration</th>
        <th>Logged Time</th>
        <th>Total Hours Completed</th>
    </tr>
    </thead>
    <tbody>
    {{-- DataTables will handle data population --}}
    </tbody>
</table>
