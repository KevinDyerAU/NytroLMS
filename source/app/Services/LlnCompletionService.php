<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;

class LlnCompletionService
{
    protected string $llnCondition = 'NOT_SATISFACTORY'; // SATISFACTORY

    /**
     * Update the has_lln_completed column for a student's course enrolment.
     *
     * @param int $userId
     * @param int $courseId
     * @return void
     */
    public function updateLlnStatus($userId, $courseId)
    {
        $enrolment = StudentCourseEnrolment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
        if (!$enrolment) {
            return;
        }

        // Find LLN quizzes for this course (excluding special LLN quiz)
        $llnQuizIds = Quiz::where('course_id', $courseId)
            ->where('is_lln', true)
            ->pluck('id');

        //        Helper::debug(['course' => $courseId, 'llnQuizIds' => $llnQuizIds, 'isEmpty' => $llnQuizIds->isEmpty(), 'count' => count($llnQuizIds)],'dump','testa3');
        if ($llnQuizIds->isEmpty() || count($llnQuizIds) === 0) {
            $firstQuiz = Quiz::whereRaw('quizzes.id = (
                    SELECT MIN(q.id)
                    FROM quizzes q
                    JOIN topics t ON q.topic_id = t.id AND t.`order` = 0
                    JOIN lessons l ON t.lesson_id = l.id AND l.`order` = 0
                    WHERE l.course_id = ?
                )', [$courseId])
                ->join('topics', 'quizzes.topic_id', '=', 'topics.id')
                ->join('lessons', 'topics.lesson_id', '=', 'lessons.id')
                ->join('courses', function ($join) use ($courseId) {
                    $join->on('lessons.course_id', '=', 'courses.id')
                        ->where('courses.id', $courseId)
                        ->where('courses.id', '!=', 11111)
                        ->where('courses.id', '!=', 11112)
                        ->whereRaw('LOWER(courses.title) NOT LIKE ?', ['%semester 2%']);
                })
                ->select('quizzes.*')
                ->first();

            //            Helper::debug(['firstQuiz' => $firstQuiz],'dd','testa3');

            if ($firstQuiz) {
                $firstQuiz->is_lln = 1;
                $firstQuiz->save();
                $llnQuizIds = Quiz::where('course_id', $courseId)
                    ->where('is_lln', true)
                    ->pluck('id');
            }
        }
        // Append LLN quiz ID from config if set
        $configLlnQuizId = intval(config('lln.quiz_id'));
        if ($configLlnQuizId && !$llnQuizIds->contains($configLlnQuizId)) {
            $llnQuizIds->push($configLlnQuizId);
        }
        //        Helper::debug(['llnQuizIds' => $llnQuizIds],'dump','testt');
        $hasCompletedQuery = QuizAttempt::where('user_id', $userId)
            ->whereIn('quiz_id', $llnQuizIds);

        if ($this->llnCondition === 'SATISFACTORY') {
            $hasCompletedQuery->where('status', 'SATISFACTORY');
        } else {
            $hasCompletedQuery->where('system_result', '!=', 'INPROGRESS')
                ->where('status', '!=', 'ATTEMPTING');
        }

        $hasCompleted = $hasCompletedQuery->exists();

        $enrolment->has_lln_completed = $hasCompleted;
        $enrolment->save();
    }

    /**
     * Bulk update LLN status for all students/courses (for admin/maintenance use).
     */
    public function bulkUpdateAll()
    {
        StudentCourseEnrolment::where('status', '!=', 'DELIST')
            ->where('is_main_course', 1)
            ->where(function ($q) {
                $q->whereNull('has_lln_completed')->orWhere('has_lln_completed', '!=', 1);
            })
            ->chunk(500, function ($enrolments) {
                foreach ($enrolments as $enrolment) {
                    $this->updateLlnStatus($enrolment->user_id, $enrolment->course_id);
                }
            });
    }
}
