<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Competency;
use App\Models\Evaluation;
use App\Models\Lesson;
use App\Models\LessonEndDate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\StudentLMSAttachables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentCourseService
{
    public function __construct()
    {
    }

    public static function markCompetency($user_id, Lesson $lesson, $remarks, $endDate)
    {
        $lesson_id = $lesson->id;
        if (empty($remarks)) {
            return false;
        }
        if (empty($endDate)) {
            return false;
        }
        $competency = self::getCompetency($user_id, $lesson_id);
        //        dump( $competency );
        if (empty($competency)) {
            $competency = self::addCompetency($user_id, $lesson, true);
            //            dd( [ 'competency' => $competency ] );
        }
        //        dd( $competency );
        $courseEnrolment = StudentCourseEnrolment::where('user_id', $user_id)
            ->where('course_id', $lesson->course_id)
            ->first();
        $authenticatable = auth()->user();
        $competency->notes = [
            'remarks' => $remarks,
            'added_by' => [
                'user_id' => $authenticatable->id,
                'user_name' => $authenticatable->name,
                'role' => $authenticatable->roleName(),
            ],
            'added_at' => Carbon::parse($endDate)->toDateTimeString(),
            'marked_at' => Carbon::today()->timezone(Helper::getTimeZone())->toDateTimeString(),
        ];
        $competency->course_start = $courseEnrolment->getRawOriginal('course_start_at') ?? null;
        $competency->is_competent = true;
        $competency->competent_on = Carbon::parse($endDate)->toDateTimeString();
        $competency->save();

        self::updateLessonEndDates($user_id, $lesson->course_id, $lesson_id);

        return $competency;
    }

    public static function addCompetency($user_id, Lesson $lesson, $forceCreate = false)
    {
        $lesson_id = $lesson->id;
        if ($lesson_id === config('lln.lesson_id') || $lesson_id === config('ptr.lesson_id')) {
            return;
        }
        $competency = Competency::where('user_id', $user_id)->where('lesson_id', $lesson_id)->first();
        //        $evidence = StudentCourseService::evidenceDetails( $user_id, $lesson_id );
        //        if(auth()->user()->id === 1 && $lesson_id === 100018 && $user_id === 47){
        //
        //            $lessonStartDate = self::lessonStartDate( $user_id, $lesson_id );
        //            $lessonEndDate = self::lessonEndDate( $user_id, $lesson_id );
        //            dd($competency, self::competencyCheck($user_id, $lesson), $lessonStartDate, $lessonEndDate);
        //        }
        //        dd($forceCreate, ( ( self::competencyCheck( $user_id, $lesson ) &&  empty( $competency )) || $forceCreate ));
        if ((self::competencyCheck($user_id, $lesson)) || $forceCreate) {
            $courseEnrolment = StudentCourseEnrolment::where('user_id', $user_id)->where('course_id', $lesson->course_id)->first();
            if (empty($courseEnrolment)) {
                \Log::error('Student Course Enrolment not found for user_id: '.$user_id.' and course_id: '.$lesson->course_id);

                return;
            }
            $lessonStartDate = self::lessonStartDate($user_id, $lesson_id);
            $lessonEndDate = self::lessonEndDate($user_id, $lesson_id);
            //            dd( $lessonStartDate, $lessonEndDate );
            if (empty($competency)) {
                $competency = new Competency();
                $competency->user_id = $user_id;
                $competency->lesson_id = $lesson_id;
                $competency->course_id = $lesson->course_id;
            }
            //            $competency->evidence_id = $evidence->id;
            $competency->course_start = $courseEnrolment->getRawOriginal('course_start_at') ?? null;
            $competency->lesson_start = Carbon::parse($lessonStartDate)->timezone(Helper::getTimeZone())->format('Y-m-d');
            $competency->lesson_end = Carbon::parse($lessonEndDate)->format('Y-m-d');
            $competency->param = [
                'last_attempt_end_date' => self::lastAttemptEndDate($user_id, $lesson_id),
                'checklist_end_date' => self::checklistEndDate($user_id, $lesson_id),
            ];
            //            dd( 'new cerate', $competency->toArray(), $courseEnrolment );
            $competency->save();

            return $competency;
        }

        return $competency;
    }

    public static function getCompetency($user_id, $lesson_id)
    {
        return Competency::where('user_id', $user_id)->where('lesson_id', $lesson_id)->first() ?? null;
    }

    public static function competencyCheck($user_id, Lesson $lesson)
    {
        $lesson_id = $lesson->id;
        $lessonCompletion = self::lessonCompletion($user_id, $lesson);
        //        dump('Lesson Complete check',$lessonCompletion);
        if ($lessonCompletion) {
            $checklistComplete = self::checklistComplete($user_id, $lesson_id);
            $workPlacementComplete = self::workPlacementComplete($user_id, $lesson);
            //            $evidenceComplete = self::evidenceCheck( $user_id, $lesson_id );
            //            dd($checklistComplete, $workPlacementComplete);
            //            dd(['checklist'=> $checklistComplete, 'work placement' => $workPlacementComplete]);
            if ($checklistComplete
                && $workPlacementComplete
                //                && $evidenceComplete
            ) {
                return true;
            }
        }

        return false;
    }

    public static function evidenceReady($user_id, Lesson $lesson)
    {
        $lesson_id = $lesson->id;
        $lessonCompletion = self::lessonCompletion($user_id, $lesson);
        //        dd($lessonCompletion);
        if ($lessonCompletion) {
            $checklistComplete = self::checklistComplete($user_id, $lesson_id);
            $workPlacementComplete = self::workPlacementComplete($user_id, $lesson);
            //            dd($checklistComplete, $workPlacementComplete);
            if ($checklistComplete && $workPlacementComplete) {
                return true;
            }
        }

        return false;
    }

    public static function lessonStartDate($user_id, $lesson_id)
    {
        // Fetch all quiz attempts for this user and lesson, ordered by created_at (or id as fallback)
        $quizAttempts = QuizAttempt::where('user_id', $user_id)
            ->where('lesson_id', $lesson_id)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        // Find the first attempt with a non-null submitted_at (i.e., first submission)
        $firstSubmitted = $quizAttempts->first(function ($attempt) {
            return !empty($attempt->submitted_at);
        });

        if ($firstSubmitted) {
            return Carbon::parse($firstSubmitted->getRawOriginal('submitted_at'))->timezone(Helper::getTimeZone())->format('j F, Y');
        }

        // If no submitted attempts, use the first attempt's updated_at if system_result is MARKED, else created_at
        if ($quizAttempts->isNotEmpty()) {
            $firstAttempt = $quizAttempts->first();
            if ($firstAttempt->system_result === 'MARKED') {
                return Carbon::parse($firstAttempt->getRawOriginal('updated_at'))->timezone(Helper::getTimeZone())->format('j F, Y');
            }
            // else {
            //     return Carbon::parse($firstAttempt->getRawOriginal('created_at'))->timezone(Helper::getTimeZone())->format('j F, Y');
            // }
        }

        // Fallback to activity log if no quiz attempts found
        $activityService = app()[StudentActivityService::class];
        $activity1 = $activityService->getActivityWhere([
            'activity_event' => 'LESSON START',
            'actionable_type' => Lesson::class,
            'actionable_id' => $lesson_id,
            'user_id' => $user_id,
        ])->first();

        if (!empty($activity1)) {
            return Carbon::parse($activity1->getRawOriginal('activity_on'))->timezone(Helper::getTimeZone())->format('j F, Y');
        }

        return;
    }

    public static function lessonEndDate($user_id, $lesson_id, $forceUpdate = false)
    {
        $lesson = Lesson::where('id', $lesson_id)->first();
        // dump($lesson->id);
        if (empty($lesson)) {
            return false;
        }
        $isLessonComplete = self::lessonCompletion($user_id, $lesson);
        // dump($isLessonComplete);
        if (!$isLessonComplete) {
            return;
        }

        if ($forceUpdate) {
            $lessonEndDate = self::updateLessonEndDates($user_id, $lesson->course_id, $lesson_id);

            // dump([$forceUpdate, $lessonEndDate, $lessonEndDate?->end_date]);
            // Use getRawOriginal to avoid accessor formatting
            return $lessonEndDate ? $lessonEndDate->getRawOriginal('end_date') : null;
        }

        $lessonEndDate = self::getLessonEndDate($user_id, $lesson->course_id, $lesson_id);
        // dump($lessonEndDate);
        if (!empty($lessonEndDate) && !empty($lessonEndDate->end_date)) {
            // Use getRawOriginal to avoid accessor formatting
            return $lessonEndDate->getRawOriginal('end_date');
        }

        // dump('all failed');
        return;
    }

    public static function checklistEndDate($user_id, $lesson_id)
    {
        $latestTime = null;
        [$quizzes, $checklists] = self::latestChecklistsForLesson($lesson_id, $user_id);

        if (count($checklists) !== count($quizzes)) {
            return;
        }
        if (!empty($checklists)) {
            foreach ($checklists as $checkListItem) {
                $status = $checkListItem->properties['status'] ?? 'N/A';
                if ($status === 'NOT SATISFACTORY') {
                    return;
                }
                if (empty($latestTime)) {
                    $latestTime = $checkListItem->created_at;
                } else {
                    $latestTime = Carbon::parse($checkListItem->created_at)->greaterThan($latestTime) ? $checkListItem->created_at : $latestTime;
                }
            }
        }

        return $latestTime;
    }

    public static function lessonActivityDate($user_id, $lesson_id)
    {
        $activityTime = null;
        $activityService = app()[StudentActivityService::class];
        $activityMarked = $activityService->getActivityWhere([
            'activity_event' => 'LESSON MARKED',
            'actionable_type' => Lesson::class,
            'actionable_id' => $lesson_id,
            'user_id' => $user_id,
        ])?->first();

        //            dump(['activityMarked' => $activityMarked]);
        if (!empty($activityMarked)) {
            $activityTime = $activityMarked->getRawOriginal('activity_on');
        } else {
            $activityTime = self::lastAttemptEndDate($user_id, $lesson_id);
            if (empty($activityTime)) {
                $activity2 = $activityService->getActivityWhere([
                    'activity_event' => 'LESSON END',
                    'actionable_type' => Lesson::class,
                    'actionable_id' => $lesson_id,
                    'user_id' => $user_id,
                ])?->first();
                //                dd(['activity2' => $activity2]);
                if (!empty($activity2)) {
                    $activityTime = $activity2->getRawOriginal('activity_on');
                }
            }
        }

        return $activityTime;
    }

    public static function lastAttemptEndDate($user_id, $lesson_id)
    {
        $activityTime = null;
        $activityService = app()[StudentActivityService::class];

        //        $lastestAttempt = QuizAttempt::where( 'user_id', $user_id )->where( 'lesson_id', $lesson_id )->orderBy( 'accessed_at', 'DESC' )->orderBy( 'id', 'DESC' )->get();
        //        if($lesson_id === 18) {
        //            dd( $lastestAttempt->toArray(), Lesson::find( $lesson_id ) );
        //        }
        $lastestAttempt = QuizAttempt::where('user_id', $user_id)
            ->where('lesson_id', $lesson_id)
            ->orderBy('accessed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
        if (!empty($lastestAttempt)) {
            $activityTime = $lastestAttempt['accessed_at'] ?? null;
        }
        if (empty($activityTime) && !empty($lastestAttempt)) {
            $activityQ = $activityService->getActivityWhere(
                [
                'activity_event' => 'ASSESSMENT MARKED',
                'actionable_type' => QuizAttempt::class,
                'actionable_id' => $lastestAttempt->quiz_id,
                'user_id' => $user_id,
            ]
            );
            $activityQ = $activityQ->sortByDesc('id')->first();
            if (!empty($activityQ)) {
                $activityTime = $activityQ->getRawOriginal('activity_on');
            }
        }

        //        dd($activityTime);
        return $activityTime;
    }

    public static function courseProgressDetails($user_id, Lesson $lesson)
    {
        $progress = CourseProgressService::getProgress($user_id, $lesson->course_id);
        // dump($progress->id);
        if (empty($progress) || empty($progress->details)) {
            return false;
        }

        return $progress->details->toArray();
    }

    public static function lessonCompletion($user_id, Lesson $lesson)
    {
        $progress = self::courseProgressDetails($user_id, $lesson);
        // dump($progress);
        $isComplete = $lesson->isCompleteForStudent($user_id, $progress);

        // dump($isComplete);
        return $isComplete;
    }

    public static function workPlacementComplete($user_id, Lesson $lesson)
    {
        if (empty($lesson->has_work_placement)) {
            return true;
        }

        $lesson_id = $lesson->id;
        $attachment = StudentLMSAttachables::forEvent('WORK_PLACEMENT')
            ->forAttachable(Lesson::class, $lesson_id)
            ->where('student_id', $user_id)?->first();

        return !empty($attachment);
    }

    public static function evidenceCheck($user_id, $lesson_id)
    {
        $attachment = StudentLMSAttachables::forEvent('EVIDENCE')
            ->forAttachable(Lesson::class, $lesson_id)
            ->where('student_id', $user_id)?->first();

        return !empty($attachment);
    }

    public static function evidenceDetails($user_id, $lesson_id)
    {
        $attachment = StudentLMSAttachables::forEvent('EVIDENCE')
            ->forAttachable(Lesson::class, $lesson_id)
            ->where('student_id', $user_id)?->first();

        return $attachment;
    }

    public static function updateCompetencyEvidence()
    {
        $competencies = Competency::select('competencies.*', 'student_lms_attachables.id as evidence')->whereNull('evidence_id')
            ->join('student_lms_attachables', function ($join) {
                $join->on('student_lms_attachables.student_id', '=', 'competencies.user_id')
                    ->on('student_lms_attachables.attachable_id', '=', 'competencies.lesson_id')
                    ->where('student_lms_attachables.attachable_type', 'App\Models\Lesson')
                    ->where('student_lms_attachables.event', 'EVIDENCE');
            })->get();
        foreach ($competencies as $competency) {
            $competency->evidence_id = $competency->evidence;
            $competency->save();
        }

        return $competencies;
    }

    public static function checklistComplete($user_id, $lesson_id)
    {
        [$quizzes, $checklists] = self::latestChecklistsForLesson($lesson_id, $user_id);

        $quizCount = count($quizzes);
        $checklistCount = count($checklists);
        //        dd([$quizCount => $quizzes->toArray(), $checklistCount=> $checklists->toArray()]);

        if ($quizCount === 0 && $checklistCount === 0) {
            return true;
        }

        if ($checklistCount !== $quizCount) {
            return false;
        }
        if (!empty($checklists)) {
            foreach ($checklists as $checkListItem) {
                $status = $checkListItem->properties['status'] ?? 'N/A';
                if ($status === 'NOT SATISFACTORY') {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function latestChecklistsForLesson($lesson_id, $user_id): array
    {
        $quizzes = Quiz::where('lesson_id', $lesson_id)->where('has_checklist', 1)->get();

        $quizIds = $quizzes->pluck('id');
        $checklists = StudentLMSAttachables::forEvent('CHECKLIST')
            ->whereIn('id', function ($query) use ($user_id) {
                return $query->select(DB::raw('max(id)'))
                    ->from('student_lms_attachables')
                    ->where('attachable_type', Quiz::class)
                    ->groupBy(['attachable_id', 'student_id'])
                    ->having('student_id', $user_id);
            })
            ->whereIn('attachable_id', $quizIds)->get();

        //        dd($quizIds, $checklists->toArray());
        return [$quizzes, $checklists];
    }

    public static function isCourseCompleted($courseId)
    {
        $lessonIds = Lesson::where('course_id', $courseId)->pluck('id');
        $totalLessons = $lessonIds->count();
        $competentCount = Competency::whereIn('lesson_id', $lessonIds)
            ->where('is_competent', 1)
            ->count();

        return ($totalLessons > 0) && ($totalLessons == $competentCount);
    }

    public static function getPreviousAttempt(QuizAttempt $currentQuizAttempt)
    {
        // Find the previous quiz attempt for the same user and quiz
        $previousQuizAttempt = QuizAttempt::where('user_id', $currentQuizAttempt->user_id)
            ->where('quiz_id', $currentQuizAttempt->quiz_id)
            ->where('id', '<', $currentQuizAttempt->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousQuizAttempt) {
            return; // "NO_PREV_QUIZ_ATTEMPT_FOUND";
        }

        return $previousQuizAttempt;
    }

    public static function getPreviousEvaluation(QuizAttempt $currentQuizAttempt)
    {
        $previousQuizAttempt = self::getPreviousAttempt($currentQuizAttempt);

        if (empty($previousQuizAttempt)) {
            return; // "NO_PREV_QUIZ_ATTEMPT_FOUND";
        }
        // Fetch the evaluation record for the previous quiz attempt
        $previousEvaluation = Evaluation::where('evaluable_type', QuizAttempt::class)
            ->where('evaluable_id', $previousQuizAttempt->id)
            ->where('student_id', $previousQuizAttempt->user_id)
            ->first();

        if (!$previousEvaluation) {
            return; // "NO_EVALUATION_FOUND";
        }

        // Return the evaluation record
        return $previousEvaluation;
    }

    /**
     * @param $studentId "Student ID"
     * @param $courseId "Course ID"
     * @param $lessonId "Lesson ID"
     * @return mixed
     */
    public static function updateLessonEndDates($studentId, $courseId, $lessonId)
    {
        $competencyDate = self::competencyDate($studentId, $lessonId);
        $workPlacementDate = self::workPlacementDate($studentId, $lessonId);
        $checklistDate = self::checklistDate($studentId, $lessonId);
        $lastQuizMarkedDate = self::lastQuizMarkedDate($studentId, $lessonId);

        $lessonEndDate = self::getLessonEndDate($studentId, $courseId, $lessonId);

        self::setLessonDates($lessonEndDate, $competencyDate, $workPlacementDate, $checklistDate, $lastQuizMarkedDate);

        $lessonEndDate->end_date = self::determineEndDate($competencyDate, $workPlacementDate, $checklistDate, $lastQuizMarkedDate);

        $lessonEndDate->save();

        return $lessonEndDate;
    }

    public static function competencyDate($user_id, $lesson_id)
    {
        $competency = self::getCompetency($user_id, $lesson_id);

        return $competency ? $competency->competent_on : null;
    }

    private static function workPlacementDate($user_id, $lesson_id)
    {
        $attachment = StudentLMSAttachables::forEvent('WORK_PLACEMENT')
            ->forAttachable(Lesson::class, $lesson_id)
            ->where('student_id', $user_id)?->first();

        return !empty($attachment) ? $attachment->created_at : null;
    }

    private static function checklistDate($user_id, $lesson_id)
    {
        return self::checklistEndDate($user_id, $lesson_id);
    }

    private static function lastQuizMarkedDate($user_id, $lesson_id)
    {
        return self::lessonActivityDate($user_id, $lesson_id);
    }

    public static function getLessonEndDate($studentId, $courseId, $lessonId)
    {
        return LessonEndDate::firstOrNew([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
        ]);
    }

    private static function setLessonDates($lessonEndDate, $competencyDate, $workPlacementDate, $checklistDate, $lastQuizMarkedDate)
    {
        $lessonEndDate->competency_date = $competencyDate;
        $lessonEndDate->work_placement_date = $workPlacementDate;
        $lessonEndDate->checklist_date = $checklistDate;
        $lessonEndDate->last_quiz_marked_date = $lastQuizMarkedDate;
    }

    private static function determineEndDate($competencyDate, $workPlacementDate, $checklistDate, $lastQuizMarkedDate)
    {
        return collect([$competencyDate, $workPlacementDate, $checklistDate, $lastQuizMarkedDate])
            ->filter()
            ->sortDesc()
            ->first() ?? null;
    }

    public static function lessonEndDateBeforeCompetency(LessonEndDate $lessonEndDate)
    {
        $workPlacementDate = $lessonEndDate->getRawOriginal('work_placement_date');
        $checklistDate = $lessonEndDate->getRawOriginal('checklist_date');
        $lastQuizMarkedDate = $lessonEndDate->getRawOriginal('last_quiz_marked_date');
        $endDate = collect([$workPlacementDate, $checklistDate, $lastQuizMarkedDate])
            ->filter()
            ->sortDesc()
            ->first() ?? '';
        if (!empty($endDate)) {
            return $endDate;
        }

        return '';
    }
}
