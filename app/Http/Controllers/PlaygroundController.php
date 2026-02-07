<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\AdminReport;
use App\Models\Competency as CompetencyModel;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentActivity;
use App\Models\StudentCourseEnrolment;
use App\Models\StudentCourseStats;
use App\Models\Topic;
use App\Models\User;
use App\Services\CourseProgressService;
use App\Services\StudentCourseService;
use App\Services\StudentTrainingPlanService;
use App\Services\UserDataExportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class PlaygroundController extends Controller
{
    public function index()
    {
        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Layouts'],
            ['name' => 'Layout Full'],
        ];

        return view('content.playground.index', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'exportForm' => auth()->user()->username === 'mohsina',
        ]);
    }

    public function process(Request $request)
    {
        return $this->{$request->form}($request);
    }

    public function test(Request $request)
    {
        //        $this->addCompetency(3, 2);
        //        $this->updateCompetencyEvidence();
        //        dd( $this->testQuery() );
        //        dd($this->competencyEndDate());
        //        dd( $this->addStudentCompetency($request->start_date, $request->end_date) );
        //        dd( $this->testCourseProgress( [ 'user_id' => 3, 'course_id' => 2 ] ) );
        //        dd( $this->testAddToProgress(101385) );
        // 2: BSB30120 - Certificate III in Business / 5: BSB30120 - Certificate III in Business (Semester 2)
    }

    /**
     * Test getTotalCounts method for a specific user
     * URL: /playground/test-get-total-counts?user_id=123.
     */
    public function testGetTotalCounts(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json(['error' => 'user_id parameter is required'], 400);
        }

        // Find the user
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Get all course progress for the user
        $courseProgresses = CourseProgress::where('user_id', $userId)->get();

        if ($courseProgresses->isEmpty()) {
            return response()->json(['error' => 'No course progress found for user'], 404);
        }

        $results = [];

        foreach ($courseProgresses as $progress) {
            // Get the details array
            $detailsArray = $progress->details->toArray();

            // Add user_id and course_id to the progress data for getTotalCounts
            $detailsArray['user_id'] = $userId;
            $detailsArray['course'] = $progress->course_id;

            // Call getTotalCounts method
            $totalCounts = CourseProgressService::getTotalCounts($userId, $detailsArray);

            $results[] = [
                'course_id' => $progress->course_id,
                'course_title' => $progress->course->title ?? 'Unknown',
                'is_main_course' => $progress->course->is_main_course ?? false,
                'total_counts' => $totalCounts,
                'original_percentage' => $progress->getRawOriginal('percentage'),
                'calculated_percentage' => $progress->percentage,
            ];
        }

        return response()->json([
            'user_id' => $userId,
            'user_name' => $user->name,
            'results' => $results,
        ]);
    }

    public function studentCompetency(Request $request)
    {
        $start_date = Carbon::parse($request->start_date)->toDateString();
        $end_date = Carbon::parse($request->end_date)->toDateString();

        return $this->addStudentCompetency($start_date, $end_date);
    }

    public function lastLoginFix(Request $request)
    {
        $users = $this->getUsersLastLoggedInEmpty();
        $i = 0;
        foreach ($users as $user) {
            $i++;
            if (!empty($user->enrolment_created_at)) {
                $lastLoggedIn = $user->enrolment_created_at;

                if (empty($user->detail->first_login)) {
                    $user->detail->first_login = $lastLoggedIn;
                }
                $user->detail->last_logged_in = $lastLoggedIn;
                $user->detail->save();
                if (!empty($user->adminReports)) {
                    foreach ($user->adminReports as $adminReport) {
                        $adminReport->student_last_active = $lastLoggedIn;
                        $adminReport->save();
                    }
                }
            }
        }
        dd($users);
    }

    public function getUsersLastLoggedInEmpty()
    {
        $users = User::with(['detail', 'adminReports'])
            ->select('users.*', 'student_activities.created_at as enrolment_created_at')
            ->join('user_details', 'users.id', '=', 'user_details.user_id')
            ->join('student_activities', function ($join) {
                $join->on('users.id', '=', 'student_activities.user_id')
                    ->where('student_activities.activity_event', '=', 'ENROLMENT');
            })
            ->whereNotNull('users.password_change_at')
            ->whereNull('user_details.last_logged_in')
//                    ->where('id', 15202)
            ->get();

        return $users;
    }

    public function updateLessonEndDates(Request $request)
    {
        //        $users = ['total' => 0,'users' => []];
        $total = 0;
        StudentCourseEnrolment::with(['student', 'course', 'course.lessons'])->where('status', '!=', 'DELIST')->chunk(100, function ($enrolments) use (&$total) {
            foreach ($enrolments as $enrolment) {
                //                $users['users'][ $enrolment->user_id][$enrolment->course_id] = 0;
                foreach ($enrolment->course->lessons as $lesson) {
                    StudentCourseService::updateLessonEndDates($enrolment->user_id, $lesson->course_id, $lesson->id);
                    //                    $users['users'][$enrolment->user_id][$enrolment->course_id]++;
                    $total = $total + 1;
                }
            }
        });

        //        $users['sum'] = array_sum(\Arr::flatten($users['users']));
        return $total;
    }

    public function testAddToProgress($quiz_id)
    {
        $quiz = Quiz::where('id', $quiz_id)->first();
        $payload = [
            'key' => 'quiz',
            'id' => $quiz->id,
            'parent_id' => $quiz->topic_id,
            'data' => $quiz->toArray(),
        ];

        return (new CourseProgressService())->addToProgress($quiz->lesson->course->id, $payload);
    }

    public function testCourseProgress($option)
    {
        $progress = CourseProgress::where('user_id', $option['user_id'])->where('course_id', $option['course_id'])->first();
        //            dump('Progress found', $progress);
        if (!empty($progress)) {
            $progressDetails = $progress->details->toArray();
            $details = CourseProgressService::reEvaluateProgress($option['user_id'], $progressDetails);

            return $details;
        }

        return false;
    }

    public function courseProgress(Request $request)
    {
        $course_id = $request->course_id ?? null;
        $users = $request->users ?? [];
        if (empty($course_id)) {
            return 'COURSE ID MISSING';
        }
        if (!empty($users)) {
            if (str_contains($users, ',')) {
                $users = explode(',', $users);
            } else {
                $users = [$users];
            }
            foreach ($users as $user_id) {
                $this->testCourseProgress(['user_id' => $user_id, 'course_id' => $course_id]);
            }
        } else {
            $users = StudentCourseEnrolment::where('course_id', $course_id)->get()->pluck('user_id')->toArray();
            foreach ($users as $user_id) {
                $this->testCourseProgress(['user_id' => $user_id, 'course_id' => $course_id]);
            }
        }

        return $users;
    }

    public function addStudentCompetency($start_date, $end_date) // 2024-02-29
    {$courseIds = Course::all()->pluck('id'); // where( 'is_archived', '!=', 1 )->get
        //        dd($courseIds);
        //        DB::enableQueryLog();
        $enrolments = StudentCourseEnrolment::with(['course.lessons'])
            ->join('course_progress', function ($join) use ($start_date, $end_date, $courseIds) {
                $join->on('course_progress.course_id', '=', 'student_course_enrolments.course_id')
                    ->on('course_progress.user_id', '=', 'student_course_enrolments.user_id')
                    ->whereDate('course_progress.updated_at', '>=', $start_date)
                    ->whereDate('course_progress.updated_at', '<=', $end_date)
                    ->where('student_course_enrolments.status', '!=', 'DELIST')
//                                                     ->where( 'student_course_enrolments.user_id',  47)
                    ->whereIn('student_course_enrolments.course_id', $courseIds);
            })->get();
        //        $bindings = DB::getQueryLog();
        $marked = ['count' => 0];
        //        dd($marked,$bindings[0],  \Str::replaceArray('?', $bindings[0]['bindings'], $bindings[0]['query']));//
        foreach ($enrolments as $enrolment) {
            $lessons = $enrolment->course->lessons;
            //            dump($lessons->toArray());
            foreach ($lessons as $lesson) {
                //
                //                $lesson_id = $lesson->id;
                //                $competency = CompetencyModel::where( 'user_id', 9624 )->where( 'lesson_id', $lesson_id )->first();
                //                $lessonStartDate = StudentCourseService::lessonStartDate( 9624, $lesson_id );
                //                $lessonEndDate = StudentCourseService::lessonEndDate( 9624, $lesson_id );
                //                dd($competency, StudentCourseService::competencyCheck( 9624, $lesson ), $lessonStartDate, $lessonEndDate);
                if (StudentCourseService::addCompetency($enrolment->user_id, $lesson)) {
                    $marked['count']++;
                }
            }
        }

        return $marked;
    }

    public function competencyEndDate()
    {
        $competencies = CompetencyModel::all();
        foreach ($competencies as $competency) {
            $lessonStartDate = StudentCourseService::lessonStartDate($competency->user_id, $competency->lesson_id);
            $lessonEndDate = StudentCourseService::lessonEndDate($competency->user_id, $competency->lesson_id);
            $competency->lesson_start = Carbon::parse($lessonStartDate)->timezone(Helper::getTimeZone())->format('Y-m-d');
            $competency->lesson_end = Carbon::parse($lessonEndDate)->timezone(Helper::getTimeZone())->format('Y-m-d');
            $competency->save();
        }
    }

    public function testQuery()
    {
        $query = (new CompetencyModel())->newQuery();

        if (auth()->user()->isTrainer()) {
            $query = $query->whereHas('user', function ($query) {
                return $query->whereHas('trainers', function (Builder $query) {
                    $query->where('id', '=', auth()->user()->id);
                });
            });
        }

        $query = $query->whereHas('lesson', function ($query) {
            $query->whereRaw('lessons.title NOT LIKE "Study Tips%"');
        });

        $query = $query->whereNotNull('evidence_id'); //                       ->where( 'is_competent', '!=', 1 )

        return $query->toSql();
    }

    public function updateCompetencyEvidence()
    {
        dd(StudentCourseService::updateCompetencyEvidence());
    }

    public function addCompetency($user_id, $lesson_id)
    {
        $lesson = Lesson::find($lesson_id);
        //        dd(StudentCourseService::competencyCheck( $user_id, $lesson ));
        dd(StudentCourseService::addCompetency($user_id, $lesson));
    }

    public function adminReports(Request $request)
    {
        $course = $request->course ?? null;
        $user = $request->user ?? null;
        $column = $request->column ?? null;
        $condition = $request->condition ?? null;

        //        dd($request->all(), $condition);

        if (empty($user) && empty($condition)) {
            return false;
        }
        if (empty($column)) {
            return false;
        }

        $query = (new AdminReport())->newQuery();
        if (!empty($user)) {
            if (str_contains($user, ',')) {
                $users = explode(',', $user);
                $query->whereIn('student_id', $users);
            } else {
                $query->where('student_id', $user);
            }
        }
        if (!empty($condition)) {
            if ($condition['type'] === 'AND') {
                $query->where($condition['column'], $condition['operator'], $condition['value']);
            }
            if ($condition['type'] === 'OR') {
                $query->orWhere($condition['column'], $condition['operator'], $condition['value']);
            }
            if ($condition['type'] === 'RAW') {
                $query->whereRaw($condition['value']);
            }
        }
        //        dd($query->toSql());
        $data = $query->get();
        if (!empty($data) && (method_exists($this, 'AR_'.$column))) {
            foreach ($data as $row) {
                $record = $this->{'AR_'.$column}($row);
                //                dd($row, $column, $record);
                $row->{$column} = $record;
                $row->save();
            }

            return count($data).' records updated';
        }

        return 'error occurred';
    }

    public function AR_student_details($data)
    {
        $student = User::find($data->student_id);
        $student_details = array_merge(
            [
            'name' => $student->name,
            'email' => $student->email,
            'study_type' => $student->study_type],
            $student->detail->toArray(),
            ['enrolment' => !empty($student->enrolments) ? $student->enrolments->toArray() : []]
        );

        return $student_details;
    }

    public function AR_course_details($data)
    {
        $enrolment = StudentCourseEnrolment::where('user_id', $data->student_id)->where('course_id', $data->course_id)->first();
        $course = Course::find($data->course_id);

        return [
            'title' => $course->title,
            'course_length' => $course->course_length_days,
            'enrolment_id' => $enrolment->id,
            'deferred' => $enrolment->getRawOriginal('deferred'),
            //                'deferred_details' => $enrolment->getRawOriginal( 'deferred_details' ),
        ];
    }

    public function migrateLarabergContent(Request $request)
    {
        $table = $request->input('table');
        $chunkSize = 500; // Process records in chunks

        // Map table names to their corresponding model class
        $models = [
            'courses' => \App\Models\Course::class,
            'lessons' => \App\Models\Lesson::class,
            'topics' => \App\Models\Topic::class,
            'quizzes' => \App\Models\Quiz::class,
        ];

        if (!array_key_exists($table, $models)) {
            return response()->json(['error' => 'Invalid table selected.'], 400);
        }

        $modelClass = $models[$table];

        DB::beginTransaction(); // Start transaction

        try {
            // Process records in chunks, skipping already migrated ones
            DB::table('lb_contents_backup')
                ->where('contentable_type', $modelClass) // Filter by model type
                ->join($table, "{$table}.id", '=', 'lb_contents_backup.contentable_id') // Join on contentable_id
                ->whereNull("{$table}.content") // Skip already migrated records
                ->select('lb_contents_backup.raw_content', "{$table}.id")
                ->orderBy("{$table}.id") // Add orderBy clause
                ->chunk($chunkSize, function ($records) use ($table) {
                    foreach ($records as $record) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update(['content' => $record->raw_content]);
                    }
                });

            DB::commit(); // Commit transaction

            return response()->json(['message' => "Data migration completed successfully for {$table}."]);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export user data as SQL queries.
     *
     * @return \Illuminate\Http\Response
     */
    public function exportUserData(Request $request)
    {
        // Check if the authenticated user is mohsina
        if (auth()->user()->username !== 'mohsina') {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->input('user_id');
        $userDataExportService = app(UserDataExportService::class);
        $output = $userDataExportService->exportUserData($userId);

        $filename = "user_data_export_{$userId}_".date('Y-m-d_His').'.sql';

        return Response::make($output, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function importUserData(Request $request)
    {
        if (!app()->environment('local') || auth()->user()->username !== 'mohsina') {
            return response()->json(['error' => 'Import User Data is only available in local environment for user mohsina.'], 403);
        }

        $request->validate([
            'sql_file' => 'required|file',
        ]);

        try {
            $file = $request->file('sql_file');
            $content = file_get_contents($file->getRealPath());

            // Parse SQL content to extract table names and record counts
            $tables = [];
            $queries = array_filter(array_map('trim', explode(';', $content)));

            foreach ($queries as $query) {
                if (preg_match('/INSERT\s+INTO\s+[`"]?([^`"\s]+)[`"]?/i', $query, $matches)) {
                    $table = $matches[1];
                    if (!isset($tables[$table])) {
                        $tables[$table] = 0;
                    }
                    $tables[$table]++;
                }
            }

            // If proceed is true, execute the queries
            if ($request->has('proceed')) {
                try {
                    DB::beginTransaction();
                    foreach ($queries as $query) {
                        if (!empty(trim($query))) {
                            DB::statement($query);
                        }
                    }
                    DB::commit();

                    $summaryContent = "<div class='alert alert-success'><ul>";
                    foreach ($tables as $table => $count) {
                        $summaryContent .= "<li>{$table}: {$count} records imported successfully</li>";
                    }
                    $summaryContent .= '</ul></div>';

                    return response()->json([
                        'content' => $summaryContent,
                        'title' => 'Import Completed',
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    return response()->json(['error' => 'Error importing data: '.$e->getMessage()], 500);
                }
            }

            // If not proceeding, just show the summary
            $summaryContent = "<div class='alert alert-success'><ul>";
            foreach ($tables as $table => $count) {
                $summaryContent .= "<li>{$table}: {$count} records to be imported</li>";
            }
            $summaryContent .= '</ul></div>';

            return response()->json([
                'content' => $summaryContent,
                'title' => 'Import Summary',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing SQL file: '.$e->getMessage()], 500);
        }
    }

    public function verifyProgress($user_id = null, $course_id = null)
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Verify Progress'],
        ];

        return view('content.playground.verify-progress', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'user_id' => $user_id,
            'course_id' => $course_id,
        ]);
    }

    public function getProgressDetails($user_id, $course_id)
    {
        try {
            // Get student and course
            $student = User::findOrFail($user_id);
            $course = Course::findOrFail($course_id);

            // Get enrollment and progress
            $enrolment = StudentCourseEnrolment::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->with(['progress', 'enrolmentStats'])
                ->first();

            if (!$enrolment) {
                return response()->json([
                    'error' => 'Student is not enrolled in this course',
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                ]);
            }

            // Get course progress details
            $courseProgress = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            // Get admin report
            $adminReport = AdminReport::where('student_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            return response()->json([
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'email' => $student->email,
                ],
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'code' => $course->code,
                ],
                'enrollment' => [
                    'id' => $enrolment->id,
                    'status' => $enrolment->status,
                    'course_start_at' => $enrolment->course_start_at,
                    'course_ends_at' => $enrolment->course_ends_at,
                    'course_expiry' => $enrolment->course_expiry,
                    'course_completed_at' => $enrolment->course_completed_at,
                    'enrolment_stats' => $enrolment->enrolmentStats?->course_stats,
                ],
                'course_progress' => $courseProgress ? [
                    'id' => $courseProgress->id,
                    'percentage' => $courseProgress->percentage,
                    'details' => $courseProgress->details,
                ] : null,
                'admin_report' => $adminReport ? [
                    'id' => $adminReport->id,
                    'course_status' => $adminReport->course_status,
                    'course_expiry' => $adminReport->course_expiry,
                    'course_completed_at' => $adminReport->course_completed_at,
                    'student_course_progress' => $adminReport->student_course_progress,
                    'updated_at' => $adminReport->updated_at,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'course_id' => $course_id,
            ], 500);
        }
    }

    public function compareProgress($user_id = null, $course_id = null)
    {
        $pageConfigs = ['layoutWidth' => 'full'];
        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Compare Progress'],
        ];

        return view('content.playground.compare-progress', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
            'user_id' => $user_id,
            'course_id' => $course_id,
        ]);
    }

    public function getComparisonDetails($user_id, $course_id)
    {
        try {
            // Get student and course
            $student = User::findOrFail($user_id);
            $course = Course::findOrFail($course_id);

            // Get enrollment
            $enrolment = StudentCourseEnrolment::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->with(['progress', 'enrolmentStats'])
                ->first();

            if (!$enrolment) {
                return response()->json([
                    'error' => 'Student is not enrolled in this course',
                    'user_id' => $user_id,
                    'course_id' => $course_id,
                ]);
            }

            // Get training plan
            $trainingPlanService = new StudentTrainingPlanService($user_id);
            $trainingPlan = $trainingPlanService->getTrainingPlan(true);

            // Get admin report
            $adminReport = AdminReport::where('student_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            // Get course progress
            $courseProgress = CourseProgress::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->first();

            return response()->json([
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullname,
                    'email' => $student->email,
                ],
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                ],
                'enrollment' => [
                    'id' => $enrolment->id,
                    'status' => $enrolment->status,
                    'course_start_at' => $enrolment->course_start_at,
                    'course_ends_at' => $enrolment->course_ends_at,
                    'course_expiry' => $enrolment->course_expiry,
                    'course_completed_at' => $enrolment->course_completed_at,
                    'enrolment_stats' => $enrolment->enrolmentStats?->course_stats,
                ],
                'course_progress' => $courseProgress ? [
                    'id' => $courseProgress->id,
                    'percentage' => $courseProgress->percentage,
                    'details' => $courseProgress->details,
                ] : null,
                'training_plan' => [
                    'raw' => $trainingPlan,
                    'html' => $trainingPlanService->renderTrainingPlan($trainingPlan, $student),
                ],
                'admin_report' => $adminReport ? [
                    'id' => $adminReport->id,
                    'course_status' => $adminReport->course_status,
                    'course_expiry' => $adminReport->course_expiry,
                    'course_completed_at' => $adminReport->course_completed_at,
                    'student_course_progress' => $adminReport->student_course_progress,
                    'updated_at' => $adminReport->updated_at,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function queryProgress(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $course_id = $request->input('course_id');

        $result = null;

        switch ($type) {
            case 'lesson':
                $result = Lesson::with(['topics', 'quizzes'])
                    ->where('id', $id)
                    ->where('course_id', $course_id)
                    ->first();

                break;

            case 'topic':
                $result = Topic::with(['lesson', 'quizzes'])
                    ->where('id', $id)
                    ->whereHas('lesson', function ($query) use ($course_id) {
                        $query->where('course_id', $course_id);
                    })
                    ->first();

                break;

            case 'quiz':
                $result = Quiz::with(['topic', 'lesson'])
                    ->where('id', $id)
                    ->where(function ($query) use ($course_id) {
                        $query->whereHas('topic.lesson', function ($q) use ($course_id) {
                            $q->where('course_id', $course_id);
                        })->orWhereHas('lesson', function ($q) use ($course_id) {
                            $q->where('course_id', $course_id);
                        });
                    })
                    ->first();

                break;

            case 'quiz_attempt':
                $result = QuizAttempt::with(['quiz', 'user'])
                    ->where('id', $id)
                    ->where('user_id', $user_id)
                    ->first();

                break;

            default:
                return response()->json(['error' => 'Invalid type specified'], 400);
        }

        if (!$result) {
            return response()->json([
                'error' => "No {$type} found with ID {$id}",
                'type' => $type,
                'id' => $id,
                'user_id' => $user_id,
                'course_id' => $course_id,
            ]);
        }

        return response()->json($result->toArray());
    }

    /**
     * Show user deletion form and preview.
     */
    public function showUserDeletionForm()
    {
        // Restrict access to local/development environment and specific user
        if (!$this->canAccessUserDeletion()) {
            if (!in_array(config('app.env'), ['local', 'development'])) {
                abort(403, 'Access denied. User data deletion is not available in production environment.');
            }
            if (auth()->user()->username !== 'mohsina') {
                abort(403, 'Access denied. User data deletion is restricted to authorized users only.');
            }
            abort(403, 'Access denied. User data deletion is not available.');
        }

        $pageConfigs = ['layoutWidth' => 'full'];

        $breadcrumbs = [
            ['link' => 'home', 'name' => 'Home'],
            ['name' => 'Playground'],
            ['name' => 'User Data Deletion'],
        ];

        return view('content.playground.user-deletion-form', [
            'pageConfigs' => $pageConfigs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Preview user data that will be deleted.
     */
    public function previewUserDeletion(Request $request)
    {
        // Restrict access to local/development environment and specific user
        if (!$this->canAccessUserDeletion()) {
            if (!in_array(config('app.env'), ['local', 'development'])) {
                abort(403, 'Access denied. User data deletion is not available in production environment.');
            }
            if (auth()->user()->username !== 'mohsina') {
                abort(403, 'Access denied. User data deletion is restricted to authorized users only.');
            }
            abort(403, 'Access denied. User data deletion is not available.');
        }

        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'username' => 'required|string|exists:users,username',
            ]);

            $userId = $request->user_id;
            $username = $request->username;

            // Verify user ID matches username
            $user = User::where('id', $userId)->where('username', $username)->first();

            if (!$user) {
                return back()->withErrors(['error' => 'User ID and username do not match.']);
            }

            // Get counts of data that will be deleted
            $deletionStats = [
                'student_course_enrolments' => StudentCourseEnrolment::where('user_id', $userId)->count(),
                'admin_reports' => AdminReport::where('student_id', $userId)->count(),
                'student_activities' => StudentActivity::where('user_id', $userId)->count(),
                'quiz_attempts' => QuizAttempt::where('user_id', $userId)->count(),
                'student_course_stats' => StudentCourseStats::where('user_id', $userId)->count(),
                'evaluations' => Evaluation::where('student_id', $userId)->count(),
                'feedback' => Feedback::where('user_id', $userId)->count(),
            ];

            // Get detailed course enrolment information
            $courseEnrolments = StudentCourseEnrolment::with('course')
                ->where('user_id', $userId)
                ->get()
                ->map(function ($enrolment) {
                    return [
                        'id' => $enrolment->id,
                        'course_name' => $enrolment->course->title ?? 'Unknown Course',
                        'course_category' => $enrolment->course->category ?? 'Unknown',
                        'status' => $enrolment->status,
                        'enrolled_at' => $enrolment->created_at,
                        'course_start_at' => $enrolment->course_start_at,
                        'course_ends_at' => $enrolment->course_ends_at,
                    ];
                });

            // Debug: Uncomment the line below to see deletion stats
            // dd($deletionStats);

            $pageConfigs = ['layoutWidth' => 'full'];

            $breadcrumbs = [
                ['link' => 'home', 'name' => 'Home'],
                ['name' => 'Playground'],
                ['name' => 'User Data Deletion Preview'],
            ];

            return view('content.playground.user-deletion-preview', [
                'pageConfigs' => $pageConfigs,
                'breadcrumbs' => $breadcrumbs,
                'user' => $user,
                'deletionStats' => $deletionStats,
                'courseEnrolments' => $courseEnrolments,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An unexpected error occurred: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Execute user data deletion.
     */
    public function executeUserDeletion(Request $request)
    {
        // Restrict access to local/development environment and specific user
        if (!$this->canAccessUserDeletion()) {
            if (!in_array(config('app.env'), ['local', 'development'])) {
                abort(403, 'Access denied. User data deletion is not available in production environment.');
            }
            if (auth()->user()->username !== 'mohsina') {
                abort(403, 'Access denied. User data deletion is restricted to authorized users only.');
            }
            abort(403, 'Access denied. User data deletion is not available.');
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'username' => 'required|string|exists:users,username',
            'confirmation' => 'required|string|in:PROCEED',
        ]);

        $userId = $request->user_id;
        $username = $request->username;

        // Verify user ID matches username again
        $user = User::where('id', $userId)->where('username', $username)->first();
        if (!$user) {
            return redirect()->route('playground.user-deletion-form')->withErrors(['error' => 'User ID and username do not match.']);
        }

        try {
            DB::beginTransaction();

            // Delete data in order to avoid foreign key constraints
            $deletedCounts = [];

            // 1. Delete evaluations (referenced by quiz attempts)
            $deletedCounts['evaluations'] = Evaluation::where('student_id', $userId)->delete();

            // 2. Delete feedback (referenced by quiz attempts)
            $deletedCounts['feedback'] = Feedback::where('user_id', $userId)->delete();

            // 3. Delete quiz attempts
            $deletedCounts['quiz_attempts'] = QuizAttempt::where('user_id', $userId)->delete();

            // 4. Delete student course stats
            $deletedCounts['student_course_stats'] = StudentCourseStats::where('user_id', $userId)->delete();

            // 5. Delete admin reports
            $deletedCounts['admin_reports'] = AdminReport::where('student_id', $userId)->delete();

            // 6. Delete student activities
            $deletedCounts['student_activities'] = StudentActivity::where('user_id', $userId)->delete();

            // 7. Delete student course enrolments
            $deletedCounts['student_course_enrolments'] = StudentCourseEnrolment::where('user_id', $userId)->delete();

            DB::commit();

            return redirect()->route('playground.user-deletion-form')
                ->with('success', "User data deletion completed successfully. Deleted: " . json_encode($deletedCounts));
        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => 'Deletion failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper method to check if progress is valid (copied from StudentTrainingPlanService).
     */
    private function isValidProgress($progress): bool
    {
        return !empty($progress) &&
               !empty($progress->course_id) &&
               !empty($progress->details) &&
               $progress->details->isNotEmpty();
    }
}
