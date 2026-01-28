<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use Carbon\Carbon;

class PtrCompletionService
{
    protected string $ptrCondition = 'NOT_SATISFACTORY'; // SATISFACTORY

    // Debug flag - set to true to enable debug output
    protected bool $allowDebug = false;

    /**
     * Check if a student has completed PTR for a specific course.
     *
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public function hasCompletedPtrForCourse($userId, $courseId): bool
    {
        // Check if PTR is excluded for this course category
        $enrolment = StudentCourseEnrolment::where('user_id', $userId)
                                           ->where('course_id', $courseId)
                                           ->with('course')
                                           ->first();

        if (!$enrolment || !$enrolment->course) {
            return false;
        }

        // If PTR is excluded for this course category, consider it completed
        if (Helper::isPTRExcluded($enrolment->course->category)) {
            return true;
        }

        // Check if enrolment is grandfathered (before PTR implementation date)
        if ($this->isEnrolmentGrandfathered($enrolment)) {
            // If grandfathered, clean up any existing PTR attempts for this course
            $this->cleanupGrandfatheredPtrAttempts($userId, $courseId);

            return true;
        }

        // Check if PTR quiz was completed for this specific course
        $ptrQuizId = intval(config('ptr.quiz_id'));

        $hasCompletedQuery = QuizAttempt::where('user_id', $userId)
                                        ->where('course_id', $courseId)  // Course-specific!
                                        ->where('quiz_id', $ptrQuizId);

        // For PTR quizzes, consider complete when:
        // 1. Status is SATISFACTORY (passed), OR
        // 2. Status is SUBMITTED with COMPLETED system_result (single question), OR
        // 3. Status is SUBMITTED with EVALUATED system_result (multiple questions completed), OR
        // 4. Status is REVIEWING with COMPLETED system_result (submitted and under review)
        $hasCompletedQuery->where(function ($query) {
            $query->where('status', 'SATISFACTORY')
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('status', 'SUBMITTED')
                               ->whereIn('system_result', ['COMPLETED', 'EVALUATED']);
                  })
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('status', 'REVIEWING')
                               ->where('system_result', 'COMPLETED');
                  });
        });

        $hasCompleted = $hasCompletedQuery->exists();

        return $hasCompleted;
    }

    /**
     * Clean up PTR attempts for grandfathered enrolments
     * This prevents old attempts from interfering with the logic.
     */
    private function cleanupGrandfatheredPtrAttempts($userId, $courseId): void
    {
        try {
            $ptrQuizId = intval(config('ptr.quiz_id'));

            // Find any existing PTR attempts for this user and course
            $existingAttempts = QuizAttempt::where('user_id', $userId)
                                           ->where('course_id', $courseId)
                                           ->where('quiz_id', $ptrQuizId)
                                           ->get();

            if ($existingAttempts->count() > 0) {
                if ($this->allowDebug) {
                    dump('PTR Debug: Cleaning up grandfathered PTR attempts', [
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'attempts_found' => $existingAttempts->count(),
                        'attempt_ids' => $existingAttempts->pluck('id')->toArray(),
                    ]);
                }

                // Soft delete the attempts to prevent interference
                foreach ($existingAttempts as $attempt) {
                    $attempt->delete();
                }
            }
        } catch (\Exception $e) {
            if ($this->allowDebug) {
                dump('PTR Debug: Error cleaning up grandfathered attempts', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ]);
            }
        }
    }

    /**
     * Check for any conflicting PTR quiz attempts that might interfere with logic
     * This is useful for debugging and cleaning up old data.
     */
    public function checkForConflictingPtrAttempts($userId): array
    {
        try {
            $ptrQuizId = intval(config('ptr.quiz_id'));

            // Find all PTR attempts for this user
            $ptrAttempts = QuizAttempt::where('user_id', $userId)
                                      ->where('quiz_id', $ptrQuizId)
                                      ->with(['course:id,title,category'])
                                      ->get();

            $conflicts = [];

            foreach ($ptrAttempts as $attempt) {
                $enrolment = StudentCourseEnrolment::where('user_id', $userId)
                                                   ->where('course_id', $attempt->course_id)
                                                   ->first();

                if ($enrolment) {
                    $isGrandfathered = $this->isEnrolmentGrandfathered($enrolment);
                    $isExcluded = Helper::isPTRExcluded($attempt->course->category ?? 'unknown');

                    if ($isGrandfathered || $isExcluded) {
                        $conflicts[] = [
                            'attempt_id' => $attempt->id,
                            'course_id' => $attempt->course_id,
                            'course_title' => $attempt->course->title ?? 'Unknown',
                            'course_category' => $attempt->course->category ?? 'Unknown',
                            'status' => $attempt->status,
                            'system_result' => $attempt->system_result,
                            'created_at' => $attempt->created_at,
                            'is_grandfathered' => $isGrandfathered,
                            'is_excluded' => $isExcluded,
                            'enrolment_created_at' => $enrolment->created_at,
                        ];
                    }
                }
            }

            return $conflicts;
        } catch (\Exception $e) {
            if ($this->allowDebug) {
                dump('PTR Debug: Error checking for conflicting attempts', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
            }

            return [];
        }
    }

    /**
     * Check if an enrolment is grandfathered (before PTR implementation date).
     *
     * @param StudentCourseEnrolment $enrolment
     * @return bool
     */
    private function isEnrolmentGrandfathered(StudentCourseEnrolment $enrolment): bool
    {
        try {
            // Get PTR implementation date from config
            $ptrImplementationDate = Carbon::parse(config('ptr.implementation_date', '2025-09-01'));

            // Ensure we're comparing dates properly
            $enrolmentDate = Carbon::parse($enrolment->created_at);

            // If enrolment was created before PTR implementation date, it's grandfathered
            $isGrandfathered = $enrolmentDate->lt($ptrImplementationDate);

            if ($this->allowDebug) {
                dump('PTR Debug: Grandfathering check', [
                    'enrolment_created_at' => $enrolment->created_at,
                    'enrolment_parsed_date' => $enrolmentDate->format('Y-m-d H:i:s'),
                    'ptr_implementation_date' => config('ptr.implementation_date'),
                    'ptr_parsed_date' => $ptrImplementationDate->format('Y-m-d H:i:s'),
                    'is_grandfathered' => $isGrandfathered,
                    'comparison_result' => $enrolmentDate->lt($ptrImplementationDate),
                ]);
            }

            return $isGrandfathered;
        } catch (\Exception $e) {
            if ($this->allowDebug) {
                dump('PTR Debug: Error in grandfathering check', [
                    'error' => $e->getMessage(),
                    'enrolment_created_at' => $enrolment->created_at,
                    'ptr_implementation_date' => config('ptr.implementation_date'),
                ]);
            }

            // If there's an error parsing dates, default to not grandfathered
            return false;
        }
    }

    /**
     * Check if a student needs to complete PTR for any active course.
     *
     * @param int $userId
     * @return bool
     */
    public function needsPtrCompletion($userId): bool
    {
        $enrolments = StudentCourseEnrolment::where('user_id', $userId)
            ->where('status', '!=', 'DELIST')
            ->whereHas('course', function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->get();

        foreach ($enrolments as $enrolment) {
            // Skip excluded categories
            if ($enrolment->course && Helper::isPTRExcluded($enrolment->course->category)) {
                continue;
            }

            // Check if PTR is completed for this specific course
            if (!$this->hasCompletedPtrForCourse($userId, $enrolment->course_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all courses that require PTR completion for a student.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCoursesRequiringPtr($userId)
    {
        return StudentCourseEnrolment::where('user_id', $userId)
            ->where('status', '!=', 'DELIST')
            ->whereHas('course', function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%semester 2%'");
            })
            ->with('course:id,title,category')
            ->get()
            ->filter(function ($enrolment) use ($userId) {
                // Skip excluded categories
                if ($enrolment->course && Helper::isPTRExcluded($enrolment->course->category)) {
                    return false;
                }

                // Return true if PTR is not completed for this course
                return !$this->hasCompletedPtrForCourse($userId, $enrolment->course_id);
            });
    }

    /**
     * Mark PTR as completed for a specific course.
     *
     * @param int $userId
     * @param int $courseId
     * @param int $quizAttemptId
     * @return bool
     */
    public function markPtrCompletedForCourse($userId, $courseId, $quizAttemptId): bool
    {
        // Verify the quiz attempt exists and is valid
        $quizAttempt = QuizAttempt::where('id', $quizAttemptId)
                                  ->where('user_id', $userId)
                                  ->where('course_id', $courseId)
                                  ->where('quiz_id', config('ptr.quiz_id'))
                                  ->first();

        if (!$quizAttempt) {
            return false;
        }

        // Update the quiz attempt status if needed
        if ($quizAttempt->system_result === 'INPROGRESS' || $quizAttempt->status === 'ATTEMPTING') {
            $quizAttempt->update([
                'system_result' => 'COMPLETED',
                'status' => 'SUBMITTED',
            ]);
        }

        return true;
    }

    /**
     * Get PTR completion status for all courses of a student.
     *
     * @param int $userId
     * @return array
     */
    public function getPtrStatusForAllCourses($userId): array
    {
        $enrolments = StudentCourseEnrolment::where('user_id', $userId)
            ->where('status', '!=', 'DELIST')
            ->with('course:id,title,category')
            ->get();

        $status = [];

        foreach ($enrolments as $enrolment) {
            $courseId = $enrolment->course_id;
            $category = $enrolment->course->category ?? 'unknown';

            if (Helper::isPTRExcluded($category)) {
                $status[$courseId] = [
                    'course_title' => $enrolment->course->title ?? 'Unknown Course',
                    'category' => $category,
                    'ptr_required' => false,
                    'ptr_completed' => true,
                    'ptr_exempt' => true,
                    'exemption_reason' => 'Category excluded from PTR requirements',
                ];
            } else {
                $ptrCompleted = $this->hasCompletedPtrForCourse($userId, $courseId);
                $status[$courseId] = [
                    'course_title' => $enrolment->course->title ?? 'Unknown Course',
                    'category' => $category,
                    'ptr_required' => true,
                    'ptr_completed' => $ptrCompleted,
                    'ptr_exempt' => false,
                    'exemption_reason' => null,
                ];
            }
        }

        return $status;
    }

    /**
     * Create PTR completion record for a specific course.
     *
     * @param int $userId
     * @param int $courseId
     * @param int $quizId
     * @param int $quizAttemptId
     * @return bool
     */
    public function createPtrCompletionRecord($userId, $courseId, $quizId, $quizAttemptId): bool
    {
        try {
            // Verify the quiz attempt exists and is valid
            $quizAttempt = QuizAttempt::where('id', $quizAttemptId)
                                      ->where('user_id', $userId)
                                      ->where('quiz_id', $quizId)
                                      ->first();

            if (!$quizAttempt) {
                if ($this->allowDebug) {
                    dump('PTR Debug: Quiz attempt not found for completion record', [
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'quiz_id' => $quizId,
                        'quiz_attempt_id' => $quizAttemptId,
                    ]);
                }

                return false;
            }

            // Update the quiz attempt to mark PTR as completed for this course
            $quizAttempt->update([
                'course_id' => $courseId, // Ensure the course_id is set correctly
                'system_result' => 'COMPLETED',
                'status' => 'SUBMITTED',
            ]);

            if ($this->allowDebug) {
                dump('PTR Debug: Created PTR completion record', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'quiz_id' => $quizId,
                    'quiz_attempt_id' => $quizAttemptId,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->allowDebug) {
                dump('PTR Debug: Error creating PTR completion record', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'quiz_id' => $quizId,
                    'quiz_attempt_id' => $quizAttemptId,
                ]);
            }

            return false;
        }
    }
}
