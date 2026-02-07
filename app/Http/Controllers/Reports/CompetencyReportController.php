<?php

namespace App\Http\Controllers\Reports;

use App\DataTables\Reports\CompetencyDetailDataTable;
use App\DataTables\Reports\CompetencyReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\StudentCourseEnrolment;
use Illuminate\Http\Request;

class CompetencyReportController extends Controller
{
    public function index(CompetencyReportDataTable $dataTable, Request $request)
    {
        $this->authorize('view competency reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Enrolled Course List'],
        ];

        //        $courses = Course::accessible()->notRestricted()->orderBy( 'category', 'asc' )->get();

        return $dataTable->with([
            //            'course' => $request->course,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])
            ->render('content.reports.competency-report.index', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                //                             'courses' => $courses,
            ]);
    }

    public function show(StudentCourseEnrolment $enrolment, CompetencyDetailDataTable $dataTable, Request $request)
    {
        $this->authorize('view competency reports');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Course Competencies', 'link' => route('reports.competencies.index')],
            ['name' => 'Competency Details'],
        ];

        $course = $enrolment->course;

        //        $courses = Course::accessible()->notRestricted()->orderBy( 'category', 'asc' )->get();
        //        dd($enrolment, $request->all());
        return $dataTable->with([
            'course_id' => $enrolment->course_id,
            //            'course' => $request->course,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])
            ->render('content.reports.competency-report.detail', [
                'course' => $course,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                //                             'courses' => $courses,
            ]);
    }

    public function _show(StudentCourseEnrolment $enrolment)
    {
        $this->authorize('view competency reports');
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['name' => 'Competency Reports', 'link' => route('reports.competencies.index')],
            ['name' => 'Details'],
        ];

        $course = $enrolment->course;
        $lessons = Lesson::where('course_id', $enrolment->course_id)->orderBy('order')->orderBy('id')->get();
        $studentCourse = StudentCourseEnrolment::with(['student', 'student.companies', 'course'])
            ->where('course_id', $enrolment->course_id)
            ->where('status', '!=', 'DELIST')
            ->whereHas('student', function ($query) {
                $query->where('users.is_active', '=', 1)
                    ->join('student_activities', function ($join) {
                        $join->on('users.id', '=', 'student_activities.user_id')
                            ->whereNotNull('course_id')
                            ->where('student_activities.activity_event', 'like', 'LESSON%')
                            ->where('student_activities.actionable_type', \App\Models\Lesson::class);
                    });
            })
            ->paginate(15)->withQueryString();

        //        dd($studentCourse);
        //        dd($studentCourse->pluck('student')[0]);
        return view()->make('content.reports.competency-report.show')
            ->with(
                [
                'enrolment' => $enrolment,
                'course' => $course,
                'lessons' => $lessons,
                'studentCourse' => $studentCourse,
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]
            );
    }
}
