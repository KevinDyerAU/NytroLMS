<?php

namespace App\Http\Controllers;

use App\DataTables\CompetencyDataTable;
use App\Models\Competency;
use App\Services\CourseProgressService;
use App\Services\StudentCourseService;
use Illuminate\Http\Request;

class CompetencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CompetencyDataTable $dataTable, Request $request)
    {
        $this->authorize('view competency');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'List'],
        ];

        //        $courses = Course::accessible()->notRestricted()->orderBy( 'category', 'asc' )->get();

        return $dataTable->with([
            //            'course' => $request->course,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ])
            ->render('content.competency.index', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                //                             'courses' => $courses,
            ]);
    }

    public function show(Competency $competency)
    {
        $this->authorize('view competency');

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['name' => 'Competency'],
            ['name' => 'Under Review'],
        ];

        return view()->make('content.competency.show')
            ->with([
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    public function getLessonEndDate(Competency $competency)
    {
        if (empty($competency->lesson_end)) {
            return '';
        }
        $lessonEndDate = StudentCourseService::getLessonEndDate($competency->user_id, $competency->course_id, $competency->lesson_id);
        if (!empty($lessonEndDate)) {
            return StudentCourseService::lessonEndDateBeforeCompetency($lessonEndDate);
        }

        return '';
    }

    public function getCompetency(Competency $competency)
    {
        $competency->lesson_end = $this->getLessonEndDate($competency);

        return response()->json([
            'data' => [
                'raw' => $competency,
                'rendered' => $this->renderDetails($competency),
            ],
            'success' => true, 'status' => 'success',
            'message' => 'Competency Fetched',
        ]);
    }

    public function renderDetails(Competency $competency)
    {
        $courseProgress = CourseProgressService::getProgress($competency->user_id, $competency->course_id);
        $progressDetails = CourseProgressService::getProgressDetails($courseProgress, ['user_id' => $competency->user_id, 'course_id' => $competency->course_id]);
        $lessonDetails = $progressDetails['lessons']['list'][$competency->lesson_id]['topics']['list'];

        //        dd( $lessonDetails );
        return view()
            ->make('content.competency.details')
            ->with([
                'competency' => $competency,
                'topics' => $lessonDetails,
            ])
            ->render();
    }
}
