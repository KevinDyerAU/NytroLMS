@extends('layouts.contentLayoutMaster')

@section('title','Competency: '. $course->title)

@section('vendor-style')
    {{-- vendor css files --}}
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/toastr.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/animate/animate.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/extensions/sweetalert2.min.css')) }}">
    <link rel="stylesheet" href="{{ asset(mix('vendors/css/forms/select/select2.min.css')) }}">
@endsection

@section('page-style')
    <link rel="stylesheet"
          href="{{ asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-toastr.css')) }}">
    <link rel="stylesheet"
          href="{{asset(mix('css/'.strtolower(env('SETTINGS_KEY','KeyInstitute')).'/base/plugins/extensions/ext-component-sweet-alerts.css'))}}">
    <link rel="stylesheet" href="{{ asset('vendors/vendor/daterangepicker/daterangepicker.css') }}">
@endsection

@section('content')
    <section id="course-competency" class="d-flex flex-column">
        {{--<form class="dt_adv_search col-12 position-relative" id="filterForm">
            <div class="row g-1 mb-1">

                <div class="col col-md-4">
                    <label class="form-label">Select Date:</label>
                    <div id="reportrange" class="form-control" data-column="10">
                        <i class="fa fa-calendar"></i>&nbsp;
                        <span>December 5, 2022 - December 5, 2023</span> <i class="fa fa-caret-down"></i>
                        <input id="startDate" type="hidden" data-column="10" name="startDate" value="05-12-2022">
                        <input id="endDate" type="hidden" data-column="10" name="endDate" value="05-12-2023">
                    </div>
                </div>
                <div class="col col-md-4 pt-1">
                    <button type="button" class="btn btn-primary btn-sm mt-1 me-1 waves-effect waves-float waves-light"
                            id="submitFilters">Submit
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1 waves-effect" id="clearFilters">
                        Reset
                    </button>
                </div>
            </div>
        </form>--}}
        <div class="clearfix row my-1">
            <div class="table-responsive">
                <table class="table table-responsive full-width dataTable no-footer" id="course-competency-table"
                       role="grid" aria-describedby="course-competency-table_info" style="width: 210px;">
                    <thead>
                    <tr role="row">
                        <th title="Student" rowspan="1" colspan="1" style="width: 180px;">Student</th>
                        <th title="Status" rowspan="1" colspan="1" style="width: 110px;">Status</th>
                        <th title="Company" rowspan="1" colspan="1" style="width: 110px;">Company</th>
                        @foreach( $lessons as $lesson )
                            <th title="{{ $lesson->title }}" rowspan="1" colspan="1">{{ $lesson->title }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach( $studentCourse as $row)
{{--                        @continue($row->student->is_active !== 1)--}}
                        @php
                            $lessonCompetency = \DB::table('competencies')
//                                            ->rightJoin('lessons', 'competencies.lesson_id','=','lessons.id')
                                            ->rightJoin('lessons', function($join) use ($lessons, $row) {
                                                $join->on('competencies.lesson_id','=','lessons.id')
                                                        ->where('competencies.user_id', $row->user_id);
                                            })
                                            ->leftJoin('student_activities', function($join) use($lessons, $row){
                                                $join->on('lessons.id', '=','student_activities.actionable_id')
                                                     ->where('student_activities.user_id', $row->user_id)
                                                     ->where( function ( $query ) {
                                                         $query->where('student_activities.activity_event','LESSON START')
                                                         ->orWhere('student_activities.activity_event','LESSON MARKED');
                                                     } )

                                                     ->where('student_activities.actionable_type', \App\Models\Lesson::class);
                                            })
                                            ->whereIn('lessons.id', $lessons->pluck('id'))
//                                            ->where('competencies.user_id', $row->user_id)
                                            ->select('competencies.*','lessons.title','lessons.id','student_activities.actionable_id','student_activities.activity_on')
                                            ->orderBy('lessons.order')->get();
//                            dd($lessonCompetency);
//                            $competency = \App\Models\Competency::where('user_id', $user_id)
//                                            ->whereIn('lesson_id', $lessons->pluck('id'))
//                                            ->get();
//                            dd($competency, $lessons->pluck('id'));
                        @endphp
                        <tr data-rowid="{{ $row->id }}">
                            <td>{!! $row->student ? "<a href='".route( "account_manager.students.show", $row->user_id )."'>".$row->student->name."</a>" : "N/A"  !!} </td>
                            <td>{{ $row->student ? ($row->student?->is_active?"Active":"In-Active") : "N/A" }}</td>
                            <td>{{ $row->student ? $row->student->companies?->first()?->name : "N/A" }}</td>
                            @foreach($lessonCompetency as $competency)
                                <td>
                                    @if(!empty($competency->is_competent))
                                        <span class='text-success fw-bold'>COMPETENT</span>
                                        @if(!empty($competency->lesson_start))
                                            <br><small>( {{ $competency->lesson_start }} - {{ $competency->lesson_end }}  )</small>
                                        @endif
                                    @elseif(!empty($competency->activity_on))
                                        <span class='text-secondary fw-normal'>COMMENCE</span>
                                        <br><small>( {{ \Carbon\Carbon::parse($competency->activity_on)->toDateString() }} )</small>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="clearfix row my-1">
            {{ $studentCourse->onEachSide(1)->links() }}
        </div>
    </section>

@endsection

@section('vendor-script')
    {{-- vendor files --}}
    <script src="{{ asset(mix('vendors/js/extensions/moment.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/moment-timezone-with-data.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/forms/select/select2.full.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/toastr.min.js')) }}"></script>
    <script src="{{ asset(mix('vendors/js/extensions/sweetalert2.all.min.js')) }}"></script>
@endsection

@section('page-script')
    <script src="{{ asset(mix('js/scripts/pages/datatable-listing.js')) }}"></script>
    <script src="{{ asset('vendors/vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(function () {

        });
    </script>

@endsection
