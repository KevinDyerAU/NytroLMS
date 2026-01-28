<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Helpers\Helper;
use App\Models\AdminReport;
use App\Models\Competency;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Evaluation;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentCourseEnrolment;
use App\Models\StudentCourseStats;
use App\Models\StudentLMSAttachables;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseProgressService
{
    public function __construct()
    {
    }

    public static function getQuizzes(CourseProgress $progress, int $lesson_id, int $topic_id, int $user_id = 0)
    {
        $details = self::detailArray($progress);
        //        if ( $user_id > 0 ) {
        //           dd( self::getUpdatedQuizCounts( $progress, $user_id ));
        //        }

        if (empty($details) || empty($details['lessons'])) {
            return [];
        }

        return $details['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'];
    }

    protected static function detailArray(CourseProgress $progress)
    {
        return $progress->details ? $progress->details->toArray() : [];
    }

    private static function getPrevLessonId(array $lessons, int $currentLessonId)
    {
        $currentIndex = array_search($currentLessonId, $lessons);
        //        dd($currentIndex, $lessons, $currentLessonId, isset($lessons[$currentIndex - 1]));

        if ($currentIndex !== false && isset($lessons[$currentIndex - 1])) {
            return $lessons[$currentIndex - 1];
        }

        return $lessons[$currentIndex];
    }

    private static function getPrevTopicId(array $topics, int $currentTopicId)
    {
        $currentIndex = array_search($currentTopicId, $topics);
        //        dd($currentIndex, $lessons, $currentLessonId, isset($lessons[$currentIndex - 1]));

        if ($currentIndex !== false && isset($topics[$currentIndex - 1])) {
            return $topics[$currentIndex - 1];
        }

        return $topics[$currentIndex];
    }

    public static function getNextLessonId(array $lessons, int $currentLessonId)
    {
        // Find the current lesson index in the array
        $currentIndex = array_search($currentLessonId, $lessons);
        //        dump(['currentIndex' => $currentIndex, 'lessons' => $lessons]);
        // Check if the lesson was found and it's not the last lesson
        if ($currentIndex !== false && isset($lessons[$currentIndex + 1])) {
            //            dump(['newIndex' => ($currentIndex+1), 'value' => $lessons[$currentIndex+1]]);
            return $lessons[$currentIndex + 1];
        }
        //        dump('No index');

        return;
    }

    public static function getNextTopicId(array $topics, int $currentTopicId)
    {
        $currentIndex = array_search($currentTopicId, $topics);

        // Check if the lesson was found and it's not the last lesson
        if ($currentIndex !== false && isset($topics[$currentIndex + 1])) {
            return $topics[$currentIndex + 1];
        }

        return;
    }

    public static function getNextLink($progress, $option, $enrolment = [])
    {
        session()->forget(['linked']);
        //        dump(empty( $progress ) , empty( $progress['details'][ 'lessons' ] ) , empty($option), empty( $progress ) || empty( $progress['details'][ 'lessons' ] ) || empty($option));
        if (empty($progress) || empty($progress['details']['lessons']) || empty($option)) {
            return;
        }

        $details = $progress['details'];
        //        dd($details, $option );
        /*
                $data = [];
                $linked = [];
                $lesson_complete = FALSE;
                $next_lesson = NULL;*/

        if ($option['type'] === 'lesson') {
            if (empty($enrolment)) {
                return;
            }
            $lessons = array_keys($details['lessons']['list']);
            $next_id = self::getNextLessonId($lessons, $option['id']);

            if (!empty($next_id)) {
                $lesson = Lesson::find($next_id);

                $course_start_date = null;
                if ($lesson->release_key === 'XDAYS' && $enrolment instanceof \Illuminate\Database\Eloquent\Model) {
                    $start_at = $enrolment->getRawOriginal('course_start_at');
                    if ($start_at) {
                        $course_start_date = self::parseAndFormatDate($start_at);
                    }
                }

                return [
                    'id' => $next_id,
                    'data' => $lesson,
                    'course_start_date' => $course_start_date,
                    'is_allowed' => (bool)$lesson->isAllowed($course_start_date),
                    'release_key' => $lesson->release_key,
                    'release_plan' => $lesson->releasePlan($course_start_date),
                    'link' => route('frontend.lms.lessons.show', $next_id),
                    'title' => 'Lesson',
                    'type' => 'lesson',
                ];
            }

            return [];
        }

        if ($option['type'] === 'topic') {
            $lesson_id = $option['parent'];
            $topics = array_keys($details['lessons']['list'][$lesson_id]['topics']['list']);
            $next_id = self::getNextTopicId($topics, $option['id']);

            if (!empty($next_id)) {
                return [
                    'id' => $next_id,
                    'data' => Topic::find($next_id),
                    'link' => route('frontend.lms.topics.show', $next_id),
                    'title' => 'Topic',
                    'type' => 'topic',
                ];
            }

            return [
                'id' => $lesson_id,
                'data' => Lesson::find($lesson_id),
                'link' => route('frontend.lms.lessons.show', $lesson_id),
                'title' => 'Lesson',
                'type' => 'lesson',
            ];
        }

        return;
    }

    public static function getPrevLink($progress, $option)
    {
        if (empty($progress) || empty($progress['details']['lessons']) || empty($option)) {
            return;
        }

        $details = $progress['details'];

        if ($option['type'] === 'lesson') {
            $lessons = array_keys($details['lessons']['list']);
            $prev_id = self::getPrevLessonId($lessons, $option['id']);

            if (!empty($prev_id) && $prev_id !== $option['id']) {
                $lesson = Lesson::find($prev_id);

                // Don't allow navigation back to study tips/pre-course lessons
                if ($lesson && \Str::contains(\Str::lower($lesson->title), ['pre-course', 'study tip', 'course assessment'])) {
                    return;
                }

                return [
                    'id' => $prev_id,
                    'data' => $lesson,
                    'link' => route('frontend.lms.lessons.show', $prev_id),
                    'title' => 'Lesson',
                    'type' => 'lesson',
                ];
            }

            return;
        }

        if ($option['type'] === 'topic') {
            $lesson_id = $option['parent'];
            $topics = array_keys($details['lessons']['list'][$lesson_id]['topics']['list']);
            $prev_id = self::getPrevTopicId($topics, $option['id']);

            if (!empty($prev_id) && $prev_id !== $option['id']) {
                return [
                    'id' => $prev_id,
                    'data' => Topic::find($prev_id),
                    'link' => route('frontend.lms.topics.show', $prev_id),
                    'title' => 'Topic',
                    'type' => 'topic',
                ];
            }

            return;
        }

        return;
    }

    public static function isPrevLessonCompleted(array $progress, $option)
    {
        if (empty($progress) || empty($progress['details']['lessons']) || empty($option)) {
            return;
        }

        $details = $progress['details'];
        $lessons = array_keys($details['lessons']['list']);
        $prev_id = self::getPrevLessonId($lessons, $option['id']);
        //        dump($option['id'],$prev_id);
        if ($prev_id === $option['id']) {
            return true;
        }
        if ($prev_id > 0) {
            $lesson = $details['lessons']['list'][$prev_id];
            if (empty($lesson)) {
                return false;
            }
            //            dump($lesson, self::lessonCompleted($lesson), self::lessonAttempted($lesson));
            if (self::lessonCompleted($lesson) || self::lessonAttempted($lesson)) {
                return true;
            }
        }

        //        dd($prev_id);
        return false;
    }

    public static function isPrevTopicCompleted(array $progress, $option)
    {
        if (empty($progress) || empty($progress['details']['lessons']) || empty($option)) {
            return;
        }

        $details = $progress['details'];
        $topics = array_keys($details['lessons']['list'][$option['lesson_id']]['topics']['list']);
        $prev_id = self::getPrevTopicId($topics, $option['id']);
        //        dump($topics, $option,$prev_id);
        if ($prev_id === $option['id']) {
            return true;
        }
        if ($prev_id > 0) {
            $topic = $details['lessons']['list'][$option['lesson_id']]['topics']['list'][$prev_id];
            if (empty($topic)) {
                return false;
            }
            //            dump($topic, self::topicCompleted($topic), self::topicAttempted($topic));
            if (self::topicCompleted($topic) || self::topicAttempted($topic)) {
                return true;
            }
        }

        //        dd($prev_id);
        return false;
    }

    private static function lessonCompleted(mixed $lesson): bool
    {
        return $lesson['completed'] || $lesson['submitted'] || $lesson['marked_at'];
    }

    private static function lessonAttempted(mixed $lesson): bool
    {
        return $lesson['topics']['count'] === $lesson['topics']['attempted'];
    }

    private static function topicCompleted(mixed $topic): bool
    {
        return $topic['completed'] || $topic['submitted'] || $topic['marked_at'];
    }

    private static function topicAttempted(mixed $topic): bool
    {
        return $topic['quizzes']['count'] === $topic['quizzes']['attempted'];
    }

    public static function initProgressSession($user_id, $course_id, $enrolment = null)
    {
        $progress = self::getProgress($user_id, $course_id);
        if (empty($progress)) {
            $newProgress = self::populateProgress($course_id);
            $progress = CourseProgress::create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'percentage' => self::getTotalCounts($user_id, $newProgress),
                'details' => $newProgress,
            ]);
        }
        if (empty($enrolment)) {
            $enrolment = StudentCourseEnrolment::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->first();
        }
        if (empty($enrolment->course_progress_id) && !empty($progress->id)) {
            $enrolment->course_progress_id = $progress->id;
            $enrolment->save();
        }
        //        ddd(self::getTotalCounts($user_id,$progress->details->toArray()));
        if (empty($progress->details)) {
            return;
        }
        $detailsArray = $progress->details ? $progress->details->toArray() : [];
        $totalCounts = self::getTotalCounts($user_id, $detailsArray);
        //        dump(['initProgressSession totalCounts' => $totalCounts]);
        $progress->percentage = $totalCounts;
        $progress->save();

        return self::updateProgressSession($progress);
    }

    /**
     * @return CourseProgress
     */
    public static function getProgress($user_id, $course_id)
    {
        return CourseProgress::where('user_id', $user_id)
            ->where('course_id', $course_id)
            ->where('course_id', '!=', config('constants.precourse_quiz_id', 0))
            ->first();
    }

    public static function populateProgress(int $course_id)
    {
        //        DB::enableQueryLog();
        $course = Course::with('lessons')->where('id', $course_id)->first();
        $progress = ['course' => $course_id, 'completed' => false, 'at' => null];
        if (empty($course)) {
            return $progress;
        }
        $progress['lessons'] = [
            'passed' => 0,
            'count' => 0,
            'submitted' => 0,
            'list' => [],
        ];
        $previousLesson = 0;
        $previousTopic = 0;
        $previousQuiz = 0;
        if (!empty($course->lessons)) {
            $lessons = $course->lessons()->with('topics')->orderBy('order')->get();
            foreach ($lessons as $lesson) {
                $progress['lessons']['count']++;
                $progress['lessons']['submitted'] = 0;
                $progress['lessons']['list'][$lesson->id] = ['completed' => false, 'submitted' => false, 'at' => null, 'completed_at' => null, 'submitted_at' => null, 'marked_at' => null, 'lesson_end_at' => null, 'previous' => $previousLesson];
                $previousLesson = $lesson->id;
                $progress['lessons']['list'][$lesson->id]['topics'] = [
                    'passed' => 0,
                    'count' => 0,
                    'submitted' => 0,
                    'list' => [],
                ];
                if (!empty($lesson->topics)) {
                    $topics = $lesson->topics()->with('quizzes')->orderBy('order')->get();
                    foreach ($topics as $topic) {
                        $progress['lessons']['list'][$lesson->id]['topics']['count']++;
                        $progress['lessons']['list'][$lesson->id]['topics']['submitted'] = 0;
                        $progress['lessons']['list'][$lesson->id]['topics']['list'][$topic->id] = ['completed' => false, 'submitted' => false, 'at' => null, 'completed_at' => null, 'submitted_at' => null, 'marked_at' => null, 'previous' => $previousTopic];
                        $previousTopic = $topic->id;
                        $progress['lessons']['list'][$lesson->id]['topics']['list'][$topic->id]['quizzes'] = [
                            'passed' => 0,
                            'count' => 0,
                            'submitted' => 0,
                            'list' => [],
                        ];
                        if (!empty($topic->quizzes)) {
                            $quizzes = $topic->quizzes()->orderBy('order')->get();
                            foreach ($quizzes as $quiz) {
                                $progress['lessons']['list'][$lesson->id]['topics']['list'][$topic->id]['quizzes']['count']++;
                                $progress['lessons']['list'][$lesson->id]['topics']['list'][$topic->id]['quizzes']['list'][$quiz->id] = ['passed' => false, 'failed' => false, 'submitted' => false, 'at' => 'null', 'marked_at' => null, 'passed_at' => null, 'failed_at' => null, 'submitted_at' => null, 'previous' => $previousQuiz];
                                $previousQuiz = $quiz->id;
                                //                                dump($progress);
                            }
                        }
                    }
                }
            }
        }

        //        dump(DB::getQueryLog());
        //        dd($progress);
        return $progress;
    }

    public static function getTotalCounts($user_id, $progress): array
    {
        $courseCompleted = false;
        $processed = 0;
        $completed = 0;
        $total = 0;
        $passed = 0;
        $failed = 0;
        $submitted = 0;
        $empty = 0;

        // dump('=== getTotalCounts START ===');
        // dump('Progress data keys: ' . json_encode(array_keys($progress)));

        // Check if this is a main course for LLND logic
        $isMainCourse = false;
        if (isset($progress['course'])) {
            $course = $progress['course'];
            if (is_int($course) || is_string($course)) {
                $course = \App\Models\Course::find($course);
            }
            if ($course) {
                $isMainCourse = $course->is_main_course == 1 || !str_contains(strtolower($course->title), 'semester 2');
                //dump("Course check - ID: {$course->id}, Title: {$course->title}, is_main_course: {$course->is_main_course}, isMainCourse: " . ($isMainCourse ? 'true' : 'false'));
            }
        }

        if (!empty($progress['lessons']) && !empty($progress['lessons']['list'])) {
            //dump('Processing lessons - count: ' . count($progress['lessons']['list']));
            $lessonIndex = 0;
            foreach ($progress['lessons']['list'] as $lesson) {
                if (!empty($lesson)) {
                    $total++;
                }

                $isFirstLesson = $lessonIndex === 0;
                $lessonIndex++;

                //dump("Lesson {$lessonIndex} - isFirstLesson: " . ($isFirstLesson ? 'true' : 'false') . ", completed: " . ($lesson['completed'] ?? 'false') . ", marked: " . ($lesson['marked'] ?? 'false'));

                if ($lesson['completed'] || (isset($lesson['marked']) && $lesson['marked'])) {
                    $completed++;
                    $processed++;
                } elseif (!empty($lesson['submitted']) || !empty($lesson['attempted'])) {
                    $processed++;
                }
                if ($lesson['topics']['count'] === 0) {
                    $empty++;
                }
                if (!empty($lesson['topics']['list'])) {
                    //dump("Lesson {$lessonIndex} topics count: " . count($lesson['topics']['list']));
                    $topicIndex = 0;
                    foreach ($lesson['topics']['list'] as $topic) {
                        if (!empty($topic)) {
                            $total++;
                        }

                        $isFirstTopic = $isFirstLesson && $topicIndex === 0;
                        $topicIndex++;

                        //dump("Topic {$topicIndex} - isFirstTopic: " . ($isFirstTopic ? 'true' : 'false') . ", completed: " . ($topic['completed'] ?? 'false') . ", marked: " . ($topic['marked'] ?? 'false'));

                        if ($topic['completed'] || (isset($topic['marked']) && $topic['marked'])) {
                            $completed++;
                            $processed++;
                        } elseif (!empty($topic['submitted']) || !empty($topic['attempted'])) {
                            $processed++;
                        }

                        if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                            //dump("Topic {$topicIndex} quizzes count: {$topic['quizzes']['count']}");
                            $quizIndex = 0;
                            $originalQuizCount = $topic['quizzes']['count'];
                            $originalPassed = $topic['quizzes']['passed'];
                            $originalFailed = $topic['quizzes']['failed'] ?? 0;

                            // Check if this is the first quiz of first topic of first lesson
                            $isFirstQuiz = $isFirstTopic && $quizIndex === 0;

                            //dump("Quiz check - isFirstQuiz: " . ($isFirstQuiz ? 'true' : 'false') . ", isMainCourse: " . ($isMainCourse ? 'true' : 'false'));

                            // Check if first quiz is already passed (old registration)
                            $firstQuizAlreadyPassed = false;
                            if ($isFirstQuiz && !empty($topic['quizzes']['list'])) {
                                $firstQuiz = reset($topic['quizzes']['list']);
                                $firstQuizAlreadyPassed = !empty($firstQuiz['status']) && $firstQuiz['status'] === 'SATISFACTORY';
                                //dump("First quiz status: " . ($firstQuiz['status'] ?? 'null') . ", alreadyPassed: " . ($firstQuizAlreadyPassed ? 'true' : 'false'));
                            }

                            // Apply LLND logic only for main courses and when first quiz is not already passed
                            if ($isMainCourse && $isFirstQuiz && !$firstQuizAlreadyPassed) {
                                // dump("=== getTotalCounts LLND Logic Applied ===");
                                //dump("isMainCourse: " . ($isMainCourse ? 'true' : 'false'));
                                //dump("isFirstQuiz: " . ($isFirstQuiz ? 'true' : 'false'));
                                //dump("firstQuizAlreadyPassed: " . ($firstQuizAlreadyPassed ? 'true' : 'false'));
                                //dump("Initial completed count before LLND: {$completed}");
                                $llnQuizId = config('lln.quiz_id');
                                $userId = $progress['user_id'] ?? $user_id ?? 0;
                                $userId = intval($userId);
                                //dump("LLND Logic Applied - Quiz ID: {$llnQuizId}, User ID: {$userId}");

                                // Get LLND quiz attempts
                                $llnAttempts = \App\Models\QuizAttempt::where('user_id', $userId)
                                    ->where('quiz_id', $llnQuizId)
                                    ->get();

                                $llnSubmitted = $llnAttempts->count();
                                $llnPassed = $llnAttempts->where('status', 'SATISFACTORY')->count();
                                $llnFailed = $llnAttempts->whereIn('status', ['FAIL', 'RETURNED'])->count();

                                //dump("LLND Attempts - Total: {$llnSubmitted}, Passed: {$llnPassed}, Failed: {$llnFailed}");

                                // Replace first quiz counts with LLND counts
                                if ($llnSubmitted > 0) {
                                    $total += 1; // Add LLND quiz to total
                                    $submitted += $llnSubmitted;
                                    $processed += $llnSubmitted;

                                    //dump("LLND Submitted - Updated counts - total: {$total}, submitted: {$submitted}, processed: {$processed}");

                                    if ($llnPassed > 0) {
                                        $passed += 1;
                                        // dump("After LLND quiz (+1): completed = " . ($completed + 1));
                                        $completed += 1; // LLND quiz
                                        // dump("After first topic (+1): completed = " . ($completed + 1));
                                        $completed += 1; // First topic (marked as completed)
                                        $processed += 1; // First topic (marked as completed)

                                        // Mark first topic as completed (always)
                                        $topic['completed'] = true;
                                        $topic['marked'] = true;

                                        // Only mark lesson as completed if it has only 1 topic
                                        if ($lesson['topics']['count'] === 1) {
                                            // dump("After first lesson (+1): completed = " . ($completed + 1));
                                            $completed += 1; // First lesson (marked as completed)
                                            $processed += 1; // First lesson (marked as completed)
                                            $lesson['completed'] = true;
                                            $lesson['marked'] = true;
                                        }
                                        //dump("LLND Passed - Final completed count: {$completed} (topic completed, lesson depends on topic count)");
                                    } elseif ($llnFailed > 0) {
                                        $failed += 1;
                                        //dump("LLND Failed - Updated counts - failed: {$failed}");
                                    }
                                } else {
                                    // No LLND attempts, use original quiz counts
                                    $total += $originalQuizCount;
                                    $passed += $originalPassed;
                                    $failed += $originalFailed;
                                    $completed += $originalPassed;
                                    //dump("No LLND attempts - Using original counts - total: {$total}, passed: {$passed}, failed: {$failed}, completed: {$completed}");
                                }

                                // Process remaining quizzes (skip first quiz)
                                $remainingQuizzes = 0;
                                foreach ($topic['quizzes']['list'] as $quizIndex => $quiz) {
                                    if ($quizIndex === 0) {
                                        continue;
                                    } // Skip first quiz (replaced by LLND)

                                    if (!empty($quiz['submitted'])) {
                                        $processed++;
                                        $submitted++;
                                        $remainingQuizzes++;
                                    } elseif (!empty($quiz['attempted'])) {
                                        $processed++;
                                        $remainingQuizzes++;
                                    }
                                }
                                //dump("Remaining quizzes processed: {$remainingQuizzes}");
                            } else {
                                // Normal quiz processing (not first quiz or not main course)
                                $total += $originalQuizCount;
                                $passed += $originalPassed;
                                $failed += $originalFailed;
                                $completed += $originalPassed;

                                //dump("Normal quiz processing - total: {$total}, passed: {$passed}, failed: {$failed}, completed: {$completed}");

                                $normalQuizzes = 0;
                                foreach ($topic['quizzes']['list'] as $quiz) {
                                    if (!empty($quiz['submitted'])) {
                                        $processed++;
                                        $submitted++;
                                        $normalQuizzes++;
                                    } elseif (!empty($quiz['attempted'])) {
                                        $processed++;
                                        $normalQuizzes++;
                                    }
                                }
                                //dump("Normal quizzes processed: {$normalQuizzes}");
                            }
                        } else {
                            $empty++;
                        }
                    }
                }
            }
        }

        if (!empty($progress['completed'])) {
            $courseCompleted = true;
        }

        $return = [
            'passed' => $completed,
            'failed' => $failed,
            'processed' => $processed,
            'attempted' => $processed,
            'submitted' => $submitted,
            'total' => $total,
            'quizzes_passed' => $passed,
            'quizzes_failed' => $failed,
            'course_completed' => $courseCompleted,
            'empty' => $empty,
        ];

        //dump("=== getTotalCounts FINAL RESULTS ===");
        //dump("Final counts - total: {$total}, passed: {$passed}, failed: {$failed}, processed: {$processed}, submitted: {$submitted}, completed: {$completed}");
        //dump("Return array: " . json_encode($return));
        //dump("=== Percentage Calculation Debug ===");
        //dump("Total items: {$total}");
        //dump("Completed items: {$completed}");
        //dump("Percentage calculation: ({$completed} / {$total}) * 100 = " . ($total > 0 ? ($completed / $total) * 100 : 0));

        return $return;
    }

    /**
     * Get LLND adjustment values for percentage calculation.
     *
     * @param array $progress
     * @return array
     */
    private static function getLLNAdjustment($progress)
    {
        $adjustment = [
            'should_adjust' => false,
            'total_adjustment' => 0,
            'passed_adjustment' => 0,
            'processed_adjustment' => 0,
        ];

        // Check if this is a main course
        $course = $progress['course'];
        if (is_int($course)) {
            $course = \App\Models\Course::find($course);
        }
        if (empty($course) || !$course->is_main_course) {
            return $adjustment;
        }

        // Get the first lesson (order = 0)
        $firstLesson = null;
        if (!empty($progress['lessons']['list'])) {
            foreach ($progress['lessons']['list'] as $lesson) {
                if (isset($lesson['data']['order']) && $lesson['data']['order'] === 0) {
                    $firstLesson = $lesson;

                    break;
                }
            }
        }

        if (!$firstLesson) {
            return $adjustment;
        }

        // Check if LLND lesson is completed
        $isLLNCompleted = !empty($firstLesson['marked_at']) || !empty($firstLesson['completed']);

        if (!$isLLNCompleted) {
            return $adjustment;
        }

        // Check for old LLND quiz completion first
        $oldLlnQuizId = config('constants.precourse_quiz_id');
        $oldLlnAttempt = \App\Models\QuizAttempt::where('user_id', $progress['user_id'] ?? 0)
            ->where('quiz_id', $oldLlnQuizId)
            ->where('status', 'SATISFACTORY')
            ->latest('id')
            ->first();

        // Check for new LLND quiz completion
        $newLlnQuizId = config('lln.quiz_id');
        $newLlnAttempt = \App\Models\QuizAttempt::where('user_id', $progress['user_id'] ?? 0)
            ->where('quiz_id', $newLlnQuizId)
            ->where('status', 'SATISFACTORY')
            ->latest('id')
            ->first();

        // If both LLND exist, consider only old LLND (no adjustment)
        if ($oldLlnAttempt && $newLlnAttempt) {
            return $adjustment; // No adjustment when both exist
        }

        // If only old LLND exists, no adjustment (keep old percentage calculation)
        if ($oldLlnAttempt && !$newLlnAttempt) {
            return $adjustment; // No adjustment for old LLND only
        }

        // If only new LLND exists, apply adjustment
        if (!$oldLlnAttempt && $newLlnAttempt) {
            // Check if the first topic has quizzes that need to be adjusted
            if (!empty($firstLesson['topics']['list'])) {
                $firstTopic = null;
                foreach ($firstLesson['topics']['list'] as $topic) {
                    if (isset($topic['data']['order']) && $topic['data']['order'] === 0) {
                        $firstTopic = $topic;

                        break;
                    }
                }

                if ($firstTopic && !empty($firstTopic['quizzes']['list'])) {
                    // Count original quizzes that should be replaced by LLND
                    $originalQuizCount = count($firstTopic['quizzes']['list']);
                    $llnQuizCount = 1; // LLND replaces the first quiz

                    $adjustment['should_adjust'] = true;
                    $adjustment['total_adjustment'] = $llnQuizCount - $originalQuizCount; // +1 for LLND, -original count
                    $adjustment['passed_adjustment'] = $llnQuizCount; // LLND quiz is passed
                    $adjustment['processed_adjustment'] = $llnQuizCount; // LLND quiz is processed
                }
            }
        }

        return $adjustment;
    }

    /**
     * Adjust percentage calculation to account for LLND quiz swapping.
     *
     * @param array $data
     * @param float $percentage
     * @return float
     */
    private static function adjustPercentageForLLND($data, $percentage)
    {
        // If the percentage is already 100%, no adjustment needed
        if ($percentage >= 100.00) {
            return $percentage;
        }

        // Check if this looks like LLND-adjusted data
        // LLND typically adds 1 to processed and total, so we should see this pattern
        if (isset($data['processed']) && isset($data['total']) && $data['processed'] > 0 && $data['total'] > 0) {
            // If processed is greater than what would be expected from normal progress,
            // it might indicate LLND adjustment
            $expectedProcessed = $data['passed'] + $data['submitted'];
            if ($data['processed'] > $expectedProcessed) {
                // This might be due to LLND adjustment, ensure percentage is calculated correctly
                $adjustedPercent = ($data['processed'] / $data['total']) * 100;

                return min(100.00, floatval(number_format($adjustedPercent, 2)));
            }
        }

        return $percentage;
    }

    public static function updateProgressSession(CourseProgress $progress)
    {
        //        ddd(isset($progress->details['models_attached']));
        if (!is_iterable($progress)) {
            return $progress;
        }
        if (!isset($progress->details['models_attached']) || (isset($progress->details['models_attached']) && !$progress->details['models_attached'])) {
            $detailsArray = $progress->details ? $progress->details->toArray() : [];
            $progress->details = self::attachModels($detailsArray);
            $progress->save();
            //            $progress = $progress->update([
            //                'details' => self::attachModels($progress)
            //            ]);
        }
        Session::put('courseProgress', $progress);

        return $progress;
    }

    public static function attachModels($progress)
    {
        if (empty($progress['course'])) {
            return $progress;
        }
        //        dd($progress);
        $previousLesson = 0;
        $previousTopic = 0;
        $previousQuiz = 0;
        $progress['data'] = Course::where('id', $progress['course'])->first()->toArray();
        if ($progress['lessons']['count'] > 0) {
            foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {
                $progress['lessons']['list'][$lesson_id]['data'] = Lesson::where('id', $lesson_id)->first()?->toArray();
                //                dd($progress[ 'lessons' ][ 'list' ][ $lesson_id ]);
                $progress['lessons']['list'][$lesson_id]['previous'] = $previousLesson;
                $previousLesson = $lesson_id;
                if ($lesson['topics']['count'] > 0) {
                    foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['data'] = Topic::where('id', $topic_id)->first()?->toArray();
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['previous'] = $previousTopic;
                        $previousTopic = $topic_id;
                        if ($topic['quizzes']['count'] > 0) {
                            foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['data'] = Quiz::where('id', $quiz_id)->first()?->toArray();
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['previous'] = $previousQuiz;
                                $previousQuiz = $quiz_id;
                            }
                        }
                    }
                }
            }
        }
        $progress['models_attached'] = true;

        return $progress;
    }

    public static function updateStudentProgress($student_id, $courseProgresses = null)
    {
        if (empty($courseProgresses)) {
            $courseProgresses = CourseProgress::where('user_id', intval($student_id))->where('course_id', '!=', config('constants.precourse_quiz_id', 0))->get();
        }
        if (!empty($courseProgresses)) {
            foreach ($courseProgresses as $courseProgress) {
                if (!empty($courseProgress) && $courseProgress->course_enrolment_status !== 'DELIST') {
                    $progressDetails = $courseProgress->details ? $courseProgress->details->toArray() : [];
                    $details1 = CourseProgressService::reEvaluateProgress($student_id, $progressDetails);
                    $courseProgress->details = self::attachModels($details1);
                    $courseProgress->percentage = CourseProgressService::getTotalCounts(intval($student_id), $details1);
                    $courseProgress->save();
                    //                    if($courseProgress->save()){
                    //                        if($student_id === 3) {
                    //                            \Log::debug( "saved Student Progress student {$student_id}, 'course' {$courseProgress->course_id} " . ( !empty( $progressDetails[ 'course' ] ) ? 'not-empty' : 'empty' )
                    //                                . ", 'lessons'" . ( !empty( $progressDetails[ 'lessons' ] ) ? 'not-empty' : 'empty' )
                    //                                . ", 'course_status'  {$courseProgress->course_enrolment_status}", ['p1' => $progressDetails, 'p2'=> $details1]);
                    //                        }
                    //                    }
                }
            }
        }
    }

    public static function reEvaluateProgress($user_id, $progress)
    {
        //        dd($user_id, 'reEvaluateProgress');

        if (empty($progress) || empty($progress['course']) || empty($progress['lessons'])) {
            return false;
        }

        if (empty($user_id)) {
            return false;
        }

        $student_id = $user_id;
        $lessonIds = array_keys($progress['lessons']['list'] ?? []);
        $lessons = ['passed' => 0, 'submitted' => 0, 'attempted' => 0, 'count' => count($lessonIds)];
        //        dd($user_id, $progress);
        //        dd($lessonIds, $lessons);
        if (!empty($lessonIds)) {
            $topicTime = [];
            foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {// Lesson
                $topicTime[$lesson_id] = [];
                $lessonMarkedAt = null;
                $isLessonAlreadyMarked = !empty($lesson['marked_at']);
                if ($isLessonAlreadyMarked) {
                    $lessonMarkedAt = $lesson['marked_at'];
                }

                $topicIds = array_keys($progress['lessons']['list'][$lesson_id]['topics']['list']);
                $topics = ['passed' => 0, 'submitted' => 0, 'attempted' => 0, 'count' => count($topicIds)];
                $invalidTopicIds = [];
                if ($topicIds) {// reset topics
                    foreach ($topicIds as $topic_id) {// Topic
                        //                        dump($topic_id, $progress[ 'course' ]);
                        $isInvalidValidTopic = Topic::where('id', intval($topic_id))
                            ->where('course_id', intval($progress['course']))
                            ->first();
                        if (empty($isInvalidValidTopic)) {
                            unset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]);
                            $invalidTopicIds[] = $topic_id;

                            continue;
                        }
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = false;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = false;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['passed'] = false;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['failed'] = false;
                    }
                }
                $topicIds = array_diff($topicIds, $invalidTopicIds);

                //                if($lesson_id !== 26){
                //                    continue;
                //                }else{
                //                    dump( 'Lesson', $lesson_id, $topicIds, $invalidTopicIds );
                //                }
                $currentDateTime = Carbon::now()->toDateTimeString();
                if ($topicIds) {
                    foreach ($topicIds as $topic_id) {// Topic
                        //                        if($topic_id === 95) {
                        //                            dump( 'topic'.'start', $topic_id );
                        //                        }
                        $topicMarkedAt = null;
                        $isTopicAlreadyMarked = !empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at']);

                        if ($isTopicAlreadyMarked) {
                            $topicMarkedAt = $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'];
                        }
                        $quizIds = [];
                        //                        if(!empty($progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ][ 'quizzes' ][ 'list' ])) {
                        //                            $quizIds = array_keys( $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ][ 'quizzes' ][ 'list' ] );
                        $quizRecords = Quiz::where('topic_id', $topic_id)->where('lesson_id', $lesson_id)->get();
                        $quizIds = $quizRecords->pluck('id')->toArray();
                        //                        }

                        //                        if($topic_id === 95) {
                        //                            dump( 'quizzes', $topic_id, $quizIds );
                        //                        }
                        //                        dump('Topic', $topic_id, $quizIds);
                        //                        if($topic_id === 95) {
                        //                        dump( 'topic', $topic_id, $topicIds, $isTopicAlreadyMarked,$topicMarkedAt, $isTopicAlreadyMarked );
                        //                        }
                        $quizzes = ['passed' => 0, 'failed' => 0, 'submitted' => 0, 'attempted' => 0, 'count' => count($quizIds)];
                        $quizCount = [];
                        $invalidQuizIds = [];
                        if (!empty($quizIds)) {// reset quiz
                            foreach ($quizIds as $quiz_id) {// Quiz
                                $isInvalidValidQuiz = Quiz::where('id', intval($quiz_id))->where('topic_id', intval($topic_id))->first();
                                if (empty($isInvalidValidQuiz)
                                    && isset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id])) {
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id] = [];
                                    unset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]);
                                    $invalidQuizIds[] = $quiz_id;

                                    continue;
                                }

                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['attempted'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed'] = false;
                            }
                        }

                        $quizIds = array_diff($quizIds, $invalidQuizIds);
                        //                        if($topic_id === 95) {
                        //                            dd( 'Quiz Ids',$quizIds, $invalidQuizIds, $quizzes );
                        //                        }
                        $topic_end_at = null;

                        if (!empty($quizIds)) {
                            $quizAttempts = QuizAttempt::with('evaluation')->where('user_id', intval($user_id))
                                ->where('course_id', intval($progress['course']))
                                ->where('lesson_id', $lesson_id)
                                ->where('topic_id', $topic_id)
                                ->with('quiz')
                                ->latestAttemptSubmittedOnly()
                                ->whereIn('quiz_id', $quizIds)->get();
                            //                            if(auth()->user()->id === 1261 && $topic_id == 95) {
                            //                                dd( $quizIds, $quizAttempts );
                            //                            }
                            //                            dump('quiz attempt counts', $lesson_id, $topic_id,  $quizIds, count($quizAttempts), $quizAttempts );
                            //                            $student_id = $user_id ?? session()->get( 'student_id' );
                            if (count($quizAttempts) > 0) {
                                //                                dd( count($quizAttempts), $quizIds, count( $quizIds ) !== count( $quizAttempts ), $isTopicAlreadyMarked );
                                if ($isTopicAlreadyMarked) {
                                    foreach ($quizIds as $quizId) {
                                        $quizData = $quizRecords->where('id', $quizId)->first();
                                        //                                        dump($quizData->created_at, $lessonMarkedAt, $topicMarkedAt);
                                        if (self::parseDate($quizData->created_at)->lessThanOrEqualTo(self::parseDate($lessonMarkedAt))
                                            || self::parseDate($quizData->created_at)->lessThanOrEqualTo(self::parseDate($topicMarkedAt))) {
                                            $attempt = QuizAttempt::firstOrCreate(
                                                ['user_id' => $student_id, 'quiz_id' => $quizId],
                                                [
                                                    'user_id' => $user_id,
                                                    'course_id' => $progress['course'],
                                                    'lesson_id' => $lesson_id,
                                                    'topic_id' => $topic_id,
                                                    'quiz_id' => $quizId,
                                                    'questions' => [],
                                                    'submitted_answers' => [],
                                                    'attempt' => 1,
                                                    'system_result' => 'MARKED',
                                                    'status' => 'SATISFACTORY',
                                                    'user_ip' => request()->ip(),
                                                    'accessor_id' => null,
                                                    'accessed_at' => null,
                                                    'submitted_at' => null,
                                                    'created_at' => $currentDateTime,
                                                    'updated_at' => $currentDateTime,
                                                ]
                                            );

                                            if (!empty($attempt)) {
                                                self::updateOrCreateStudentActivity(
                                                    $attempt,
                                                    'ASSESSMENT MARKED',
                                                    $attempt->user_id,
                                                    [
                                                        'activity_on' => $attempt->getRawOriginal('updated_at'),
                                                        'student' => $attempt->user_id,
                                                        'status' => $attempt->status,
                                                        'accessor_id' => !empty($accessor) ? $accessor->id : null,
                                                        'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                                                        'accessed_at' => !empty($attempt->created_at) ? $attempt->created_at : $currentDateTime,
                                                    ]
                                                );
                                                $quizAttempt = $quizAttempts->where('id', $attempt->id)->first();
                                                if (empty($quizAttempt)) {
                                                    $quizAttempts->add($quizAttempt);
                                                }
                                                event(new \App\Events\QuizAttemptStatusChanged($attempt));
                                            }
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['marked_at'] = $attempt->getRawOriginal('updated_at') ?? $currentDateTime;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted_at'] = $attempt->getRawOriginal('updated_at') ?? $currentDateTime;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted'] = true;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed'] = true;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed_at'] = null;
                                        } else {
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['marked_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed_at'] = null;
                                        }
                                    }
                                }
                                $submitted_attempts = [];
                                foreach ($quizAttempts as $attempt) {
                                    if (empty($attempt)) {
                                        continue;
                                    }
                                    $evaluation = $attempt->evaluation?->first();
                                    $evaluation_time = null;
                                    if (!empty($evaluation)) {
                                        $evaluation_time = $evaluation->getRawOriginal('created_at');
                                        // Use the static formatDate method
                                        $evaluation_time = self::formatDate($evaluation_time, 'j F, Y g:i A');
                                    }
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['submitted'] = true;
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['attempted'] = true;
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['attempted_at'] = $attempt->getRawOriginal('submitted_at');
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['submitted_at'] = $attempt->getRawOriginal('submitted_at');
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['at'] = $attempt->getRawOriginal('created_at');
                                    $submitted_attempts[$attempt->quiz_id] = 1;

                                    if ($attempt->system_result === 'MARKED') {
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['marked_at'] = $attempt->getRawOriginal('updated_at');
                                    }

                                    if ($attempt->status === 'SATISFACTORY') {
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed_at'] = $evaluation_time ?? $attempt->getRawOriginal('updated_at');
                                        $quizzes['passed']++;
                                    } elseif (in_array($attempt->status, ['RETURNED', 'FAIL', 'NOT SATISFACTORY'])) {
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed_at'] = $evaluation_time ?? $attempt->getRawOriginal('updated_at');
                                        $quizzes['failed']++;
                                    }
                                    //                                    if($attempt->quiz_id == 669){
                                    //                                        dd($progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ][ 'quizzes' ][ 'list' ][ $attempt->quiz_id ], $attempt);
                                    //                                    }

                                    if ($isLessonAlreadyMarked) {
                                        if ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] == false) {
                                            $quizzes['passed']++;
                                        }
                                        if ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] == true) {
                                            $quizzes['failed']--;
                                        }
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['marked_at'] = $lessonMarkedAt;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['attempted_at'] = $lessonMarkedAt;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] = false;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed_at'] = null;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed_at'] = null;
                                    } elseif ($isTopicAlreadyMarked) {
                                        if ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] == false) {
                                            $quizzes['passed']++;
                                        }
                                        if ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] == true) {
                                            $quizzes['failed']--;
                                        }
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['marked_at'] = $topicMarkedAt;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['attempted_at'] = $topicMarkedAt;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] = false;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['failed_at'] = null;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$attempt->quiz_id]['passed_at'] = null;
                                    }
                                    //                                    dump( $attempt->status, $attempt->quiz_id );
                                }

                                $quizzes['submitted'] = count($submitted_attempts);
                                $quizzes['attempted'] = count($submitted_attempts);
                            } else {
                                //                                dump('not attempts', $quizIds, $isTopicAlreadyMarked);
                                if ($isTopicAlreadyMarked) {
                                    //                                    dump('topic marked', $isLessonAlreadyMarked, $isTopicAlreadyMarked, $quizRecords);
                                    $submitted_attempts = [];
                                    foreach ($quizIds as $quizId) {
                                        $quizData = $quizRecords->where('id', $quizId)->first();
                                        //                                        dump($quizData->created_at, $lessonMarkedAt, $topicMarkedAt);
                                        if (self::parseDate($quizData->created_at)->lessThanOrEqualTo(self::parseDate($lessonMarkedAt))
                                            || self::parseDate($quizData->created_at)->lessThanOrEqualTo(self::parseDate($topicMarkedAt))) {
                                            $attempt = QuizAttempt::firstOrCreate(['user_id' => $student_id, 'quiz_id' => $quizId], [
                                                'user_id' => $user_id,
                                                'course_id' => $progress['course'],
                                                'lesson_id' => $lesson_id,
                                                'topic_id' => $topic_id,
                                                'quiz_id' => $quizId,
                                                'questions' => [],
                                                'submitted_answers' => [],
                                                'attempt' => 1,
                                                'system_result' => 'MARKED',
                                                'status' => 'SATISFACTORY',
                                                'user_ip' => request()->ip(),
                                                'submitted_at' => null,
                                                'accessor_id' => null,
                                                'accessed_at' => null,
                                                'created_at' => $currentDateTime,
                                                'updated_at' => $currentDateTime,
                                            ]);
                                            if (!empty($attempt)) {
                                                $submitted_attempts[$attempt->quiz_id] = 1;
                                                self::updateOrCreateStudentActivity(
                                                    $attempt,
                                                    'ASSESSMENT MARKED',
                                                    $attempt->user_id,
                                                    [
                                                        'activity_on' => $attempt->getRawOriginal('updated_at'),
                                                        'student' => $attempt->user_id,
                                                        'status' => $attempt->status,
                                                        'accessor_id' => !empty($accessor) ? $accessor->id : null,
                                                        'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                                                        'accessed_at' => !empty($accessed_on) ? $accessed_on : $currentDateTime,
                                                    ]
                                                );
                                                event(new \App\Events\QuizAttemptStatusChanged($attempt));
                                            }
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['marked_at'] = $attempt->getRawOriginal('updated_at') ?? $currentDateTime;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted_at'] = $attempt->getRawOriginal('updated_at') ?? $currentDateTime;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed'] = true;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted'] = true;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed_at'] = null;
                                        } else {
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['marked_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['attempted'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed'] = false;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['failed_at'] = null;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quizId]['passed_at'] = null;
                                        }
                                    }
                                    $quizzes['submitted'] = count($submitted_attempts);
                                    $quizzes['attempted'] = count($submitted_attempts);
                                }
                            }
                        }
                        //                        dd('setting progress array');
                        // Quizzes
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] = $quizzes['count'];
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] = $quizzes['submitted'];
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['attempted'] = $quizzes['attempted'];
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] = (($isLessonAlreadyMarked || $isTopicAlreadyMarked) && $quizzes['submitted'] >= $quizzes['count']) ? $quizzes['count'] : $quizzes['passed'];
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['failed'] = $quizzes['failed'];

                        //                        if($topic_id === 555){
                        //                            dd($progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ][ 'quizzes' ],$submitted_attempts, $quizAttempts->toArray(), $quizIds);
                        //                        }
                        if ($isLessonAlreadyMarked) {
                            $topics['passed']++;
                            $topics['attempted']++;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = $lessonMarkedAt;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = $lessonMarkedAt;
                        } elseif ($isTopicAlreadyMarked) {
                            $topics['passed']++;
                            $topics['attempted']++;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = $topicMarkedAt;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = $topicMarkedAt;
                        } else {
                            $attempted = false;
                            if ($quizzes['count'] === $quizzes['submitted'] && $quizzes['count'] > 0) {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                                $topics['submitted']++;
                                $attempted = true;
                            } else {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'] = null;
                            }
                            if ($quizzes['count'] === $quizzes['passed'] && $quizzes['count'] > 0) {
                                $topics['passed']++;
                                $attempted = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                            } else {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'] = null;
                            }
                            if ($quizzes['count'] === $quizzes['attempted'] && $quizzes['count'] > 0) {
                                $attempted = true;
                            } else {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = false;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = null;
                                $attempted = false;
                            }

                            if ($attempted) {
                                $topics['attempted']++;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = $currentDateTime;
                            }
                        }

                        //                        \Log::debug('Debug reEvaluateProgress ', $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ]);
                        if (!empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'])
                            || (isset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted']) && $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] === true)
                            || (isset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted']) && $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] === true)) {
                            $topic_end_at = $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at']
                                ?? $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at']
                                ?? $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at']
                                ?? $currentDateTime;
                            $lastQuizItem = end($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list']);
                            if (!empty($lastQuizItem)) {
                                $topic_end_at = $lastQuizItem['marked_at'] ?? $lastQuizItem['completed_at'] ?? $lastQuizItem['attempted_at'] ?? '';
                            }
                            $beforeTimes = self::parseDate('2000-01-01');

                            $topicBeforeTimes = self::parseDate($topic_end_at)?->lessThan($beforeTimes);
                            if ($topicBeforeTimes) {
                                $topic_end_at = $progress['lessons']['list'][$lesson_id]['marked_at']
                                    ?? $progress['lessons']['list'][$lesson_id]['completed_at']
                                    ?? $progress['lessons']['list'][$lesson_id]['attempted_at']
                                    ?? $currentDateTime;
                            }

                            $topicModel = Topic::where('id', $topic_id)->first();

                            $topicTime[$lesson_id][$topic_id] = floatval($topicModel->estimated_time);

                            //                            \Log::debug('Debug reEvaluateProgress Topic END at '.$topic_end_at, $topicModel->toArray());

                            self::updateOrCreateStudentActivity(
                                $topicModel,
                                'TOPIC END',
                                $student_id,
                                [
                                    'activity_on' => $topic_end_at,
                                    'student' => $student_id,
                                    'total_quizzes' => $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'],
                                    'topic_time' => $topicModel->estimated_time,
                                    'end_at' => $topic_end_at,
                                    'accessor_id' => auth()->user()?->id,
                                    'accessor_role' => auth()->user()?->roleName(),
                                ]
                            );
                        }
                    }// Topic
                }
                $progress['lessons']['list'][$lesson_id]['topics']['count'] = $topics['count'];
                $progress['lessons']['list'][$lesson_id]['topics']['submitted'] = $topics['submitted'];
                $progress['lessons']['list'][$lesson_id]['topics']['attempted'] = $topics['attempted'];
                $progress['lessons']['list'][$lesson_id]['topics']['passed'] = ($isLessonAlreadyMarked) ? $topics['count'] : $topics['passed'];

                if ($isLessonAlreadyMarked) {
                    $lessons['passed']++;
                    $lessons['attempted']++;
                    $progress['lessons']['list'][$lesson_id]['completed'] = true;
                    $progress['lessons']['list'][$lesson_id]['attempted'] = true;
                    $progress['lessons']['list'][$lesson_id]['marked_at'] = $lessonMarkedAt;
                    $progress['lessons']['list'][$lesson_id]['attempted_at'] = $lessonMarkedAt;
                    $progress['lessons']['list'][$lesson_id]['lesson_end_at'] = $lessonMarkedAt;
                } else {
                    $attempted = false;
                    if ($topics['count'] === $topics['submitted'] && $topics['count'] > 0) {
                        $progress['lessons']['list'][$lesson_id]['submitted'] = true;
                        $lessons['submitted']++;
                        $attempted = true;
                    } else {
                        $progress['lessons']['list'][$lesson_id]['submitted'] = false;
                        $progress['lessons']['list'][$lesson_id]['submitted_at'] = null;
                    }
                    if ($topics['count'] === $topics['passed'] && $topics['count'] > 0) {
                        $progress['lessons']['list'][$lesson_id]['completed'] = true;
                        $lessons['passed']++;
                        $attempted = true;
                    } else {
                        $progress['lessons']['list'][$lesson_id]['completed'] = false;
                        $progress['lessons']['list'][$lesson_id]['completed_at'] = null;
                    }

                    if ($topics['count'] === $topics['attempted'] && $topics['count'] > 0) {
                        $attempted = true;
                    } else {
                        $progress['lessons']['list'][$lesson_id]['attempted'] = false;
                        $progress['lessons']['list'][$lesson_id]['attempted_at'] = null;
                        $attempted = false;
                    }
                    if ($attempted) {
                        $lessons['attempted']++;
                        $progress['lessons']['list'][$lesson_id]['attempted'] = true;
                        $progress['lessons']['list'][$lesson_id]['attempted_at'] = $currentDateTime;
                    }
                }

                if (!empty($progress['lessons']['list'][$lesson_id]['completed'])
                    || (isset($progress['lessons']['list'][$lesson_id]['submitted']) && $progress['lessons']['list'][$lesson_id]['submitted'] === true)
                    || (isset($progress['lessons']['list'][$lesson_id]['attempted']) && $progress['lessons']['list'][$lesson_id]['attempted'] === true)) {
                    $lesson_end_at = $progress['lessons']['list'][$lesson_id]['marked_at']
                        ?? $progress['lessons']['list'][$lesson_id]['completed_at']
                        ?? $progress['lessons']['list'][$lesson_id]['attempted_at']
                        ?? $currentDateTime;
                    $lastTopicItem = end($progress['lessons']['list'][$lesson_id]['topics']['list']);
                    if (!empty($lastTopicItem)) {
                        $lesson_end_at = $lastTopicItem['marked_at'] ?? $lastTopicItem['completed_at'] ?? $lastTopicItem['attempted_at'];
                    }
                    self::updateOrCreateStudentActivity(
                        Lesson::where('id', $lesson_id)->first(),
                        'LESSON END',
                        $student_id,
                        [
                            'activity_on' => $lesson_end_at,
                            'student' => $student_id,
                            'lesson_time' => !empty($topicTime[$lesson_id]) ? array_sum($topicTime[$lesson_id]) : 0.00,
                            'topics_time' => !empty($topicTime[$lesson_id]) ? $topicTime[$lesson_id] : 0.00,
                            'at' => $currentDateTime,
                            'id' => auth()->user()?->id,
                            'by' => auth()->user()?->roleName(),
                            'end_at' => $lesson_end_at,
                            'accessor_id' => auth()->user()?->id,
                            'accessor_role' => auth()->user()?->roleName(),
                        ]
                    );
                } else {
                    self::clearNotifications($lesson_id, $user_id, 'LESSON END');
                }
            }// Lesson
        }

        $progress['lessons']['count'] = $lessons['count'];
        $progress['lessons']['submitted'] = $lessons['submitted'];
        $progress['lessons']['attempted'] = $lessons['attempted'];
        $progress['lessons']['passed'] = ($lessons['passed'] > $lessons['count']) ? $lessons['count'] : $lessons['passed'];

        if ($lessons['count'] === $lessons['submitted'] && $lessons['count'] > 0) {
            $progress['completed'] = true;
        } else {
            $progress['completed'] = false;
        }

        //        dd( $progress );
        return $progress;
    }

    public static function updateOrCreateStudentActivity($model, $event, $student_id, $data)
    {
        if (empty($model)) {
            return false;
        }
        $activityService = app()[StudentActivityService::class];
        $activity = $activityService->getActivityWhere([
            'activity_event' => $event,
            'actionable_type' => $model::class,
            'actionable_id' => $model->id,
            'user_id' => $student_id,
        ]);
        if ($event === 'ASSESSMENT MARKED') {
            $activity = $activityService->getActivityWhere([
                'activity_event' => $event,
                'actionable_type' => $model::class,
                'actionable_id' => $model->id,
                'user_id' => $student_id,
            ]);
        }
        //        dd($activity->first());

        $data['user_id'] = $student_id ?? 0;

        if (empty($activity) || $activity->count() < 1) {
            return $activityService->setActivity([
                'activity_event' => $event,
                'activity_details' => $data,
                'user_id' => $student_id,
            ], $model);
        } else {
            return $activityService->updateActivity([
                'activity_details' => $data,
            ], $activity->first(), $model);
        }
    }

    public static function clearNotifications($lesson_id, $user_id, $event = 'LESSON END')
    {
        $activityService = app()[StudentActivityService::class];

        $activity = $activityService->getActivityWhere([
            'activity_event' => $event,
            'actionable_type' => Lesson::class,
            'actionable_id' => $lesson_id,
            'user_id' => $user_id,
        ])->first();
        if (!empty($activity)) {
            $activityService->delete($activity->id);

            return $activity->id;
        }

        return 0;
    }

    public static function updateStudentProgressWithModel($student_id, $courseProgresses = null)
    {
        if (empty($courseProgresses)) {
            $courseProgresses = CourseProgress::where('user_id', intval($student_id))->where('course_id', '!=', config('constants.precourse_quiz_id', 0))->get();
        }
        if (!empty($courseProgresses)) {
            foreach ($courseProgresses as $courseProgress) {
                if (!empty($courseProgress) && $courseProgress->course_enrolment_status !== 'DELIST') {
                    $progressDetails = $courseProgress->details ? $courseProgress->details->toArray() : [];
                    if (empty($progressDetails['models_attached'])) {
                        $courseProgress->details = self::attachModels($progressDetails);
                        $courseProgress->save();
                    }
                }
            }
        }
    }

    public static function getActivityTime($student_id, $course_id, $activityDot)
    {
        $progressObj = self::getProgress($student_id, $course_id);
        $progress = $progressObj && $progressObj->details ? $progressObj->details->toArray() : [];
        if (empty($progress)) {
            return;
        }
        $activity = \Arr::get($progress, $activityDot, null);
        if (empty($activity)) {
            return;
        }
        $activityTime = $activity['marked_at'] ?? $activity['completed_at'] ?? $activity['attempted_at'];

        $quizzes = \Arr::get($progress, $activityDot . '.quizzes.list', null);
        if (!empty($quizzes)) {
            $lastQuizItem = end($quizzes);
            if (!empty($lastQuizItem)) {
                $activityTime = $lastQuizItem['marked_at'] ?? $lastQuizItem['completed_at'] ?? $lastQuizItem['attempted_at'];
            }
        }

        return (empty($activityTime)) ? Carbon::now()->toDateTimeString() : self::parseDate($activityTime)->toDateTimeString();
    }

    // public static function getActivityTime( $student_id, $course_id, $activityDot ) {
    //     $progress = self::getProgress( $student_id, $course_id )?->details->toArray();
    //     if ( empty( $progress ) ) {
    //         return NULL;
    //     }
    //     $activity = \Arr::get( $progress, $activityDot, NULL );
    //     if ( empty( $activity ) ) {
    //         return NULL;
    //     }
    //     $activityTime = $activity[ 'submitted_at' ] ?? $activity[ 'attempted_at' ] ?? $activity[ 'marked_at' ];
    //     return ( empty( $activity ) || empty( $activityTime ) ) ? Carbon::now()->toDateString() : self::parseDate( $activityTime )->toDateString();
    // }

    public static function createStudentActivity($model, $event, $student_id, $data)
    {
        $activityService = app()[StudentActivityService::class];
        $activity = $activityService->getActivityWhere([
            'activity_event' => $event,
            'actionable_type' => $model::class,
            'actionable_id' => $model->id,
            'user_id' => $student_id,
        ]);
        //        \Log::debug('creating activity '.(empty($activity)?"EMPTY":"NOT EMPTY"));
        if (empty($activity) || $activity->count() < 1) {
            //            \Log::debug('creating activity details',[$model::class,$model->id, $event, $student_id, $data]);
            return $activityService->setActivity([
                'activity_event' => $event,
                'activity_details' => $data,
                'user_id' => $student_id,
            ], $model);
        }

        return false;
    }

    public static function markComplete($type, $option, $auto_correct = false)
    {
        if (empty($type) || empty($option) || empty($option['user_id'])) {
            return false;
        }

        $progress = CourseProgress::where('user_id', $option['user_id'])->where('course_id', $option['course_id'])->first();

        if (!empty($progress)) {
            $details = self::getProgressDetails($progress, $option);

            //        dump($details, $option);
            $progress->details = self::markProgressDetails($option['user_id'], $details, ['type' => $type, 'option' => $option], $auto_correct);
            $progress->percentage = self::getTotalCounts($option['user_id'], $progress->details);
            //        dd($progress->details);
            $progress->save();
        }
        $adminReportService = new AdminReportService($option['user_id'], $option['course_id']);
        $adminReportService->updateProgress();

        self::updateProgressSession($progress);

        // Update Student Course Stats
        $enrolment = StudentCourseEnrolment::with(['student', 'course', 'progress', 'enrolmentStats'])
            ->where('user_id', $option['user_id'])
            ->where('course_id', $option['course_id'])
            ->first();
        if ($enrolment) {
            $isMainCourse = $enrolment->course->is_main_course || !\Str::contains(\Str::lower($enrolment->course->title), 'emester 2');
            CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
        }

        return $progress;
    }

    public static function getProgressDetails($progress, $option)
    {
        if (!empty($progress)) {
            $details = $progress->details ? $progress->details->toArray() : [];
            if (empty($details['data'])) {
                $newProgress = self::reCalculateProgress($option['user_id'], $option['course_id']);
                $details = $newProgress->details ? self::attachModels($newProgress->details->toArray()) : [];
            }
        } else {
            $progress = new CourseProgress();
            $progress->user_id = $option['user_id'];
            $progress->course_id = $option['course_id'];
            $details = self::populateProgress($option['course_id']);
        }

        return $details;
    }

    public static function reCalculateProgress($user_id, $course_id)
    {
        $courseProgress = self::getProgress($user_id, $course_id);

        $newProgress = self::populateProgress($course_id);

        //        dd($courseProgress, $newProgress);

        if (empty($courseProgress)) {
            $courseProgress = CourseProgress::create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'percentage' => self::getTotalCounts($user_id, $newProgress),
                'details' => $newProgress,
            ]);
        }

        $existingProgress = $courseProgress->details ? $courseProgress->details->toArray() : [];
        $resultProgress = self::getCleanProgressDetails($existingProgress, $newProgress);

        $progress = self::reEvaluateProgress($user_id, $resultProgress);

        $totalCounts = self::getTotalCounts($user_id, $progress);
        //        dump(['reCalculateProgress Total Counts'=>$totalCounts]);
        $courseProgress->percentage = $totalCounts;
        $courseProgress->details = $progress;

        $courseProgress->save();

        self::updateAdminReportProgress($user_id, $course_id);

        return self::updateProgressSession($courseProgress);
    }

    /**
     * Sync course progress for frontend controllers
     * This method combines initProgressSession, reCalculateProgress, and related updates
     * to ensure all progress data is properly synchronized.
     *
     * @param int $user_id
     * @param int $course_id
     * @param StudentCourseEnrolment|null $enrolment
     * @return CourseProgress|null
     */
    public static function syncCourseProgress($user_id, $course_id, $enrolment = null)
    {
        // Initialize progress session (creates if doesn't exist, links to enrolment)
        $progress = self::initProgressSession($user_id, $course_id, $enrolment);

        if (!$progress) {
            return;
        }

        // Recalculate progress to ensure all data is fresh and accurate
        $courseProgress = self::reCalculateProgress($user_id, $course_id);

        if (!$courseProgress) {
            return;
        }

        // Get enrolment if not provided
        if (!$enrolment) {
            $enrolment = StudentCourseEnrolment::where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->first();
        }

        // Update course stats if enrolment exists
        if ($enrolment) {
            $isMainCourse = $enrolment->course?->is_main_course
                || !\Str::contains(\Str::lower($enrolment->course?->title), 'emester 2');

            self::updateStudentCourseStats($enrolment, $isMainCourse);
        }

        return $courseProgress;
    }

    public static function getCleanProgressDetails($existingProgress, $newProgress)
    {
        $newProgress['lessons']['passed'] = self::bestValue($newProgress['lessons']['passed'] ?? 0, $existingProgress['lessons']['passed'] ?? 0);
        $newProgress['lessons']['count'] = self::bestValue($newProgress['lessons']['count'] ?? 0, $existingProgress['lessons']['count'] ?? 0);
        $newProgress['lessons']['submitted'] = self::bestValue($newProgress['lessons']['submitted'] ?? 0, $existingProgress['lessons']['submitted'] ?? 0);

        if ($newProgress['lessons']['count'] > 0 && !empty($newProgress['lessons']['list'])) {
            foreach ($newProgress['lessons']['list'] as $lesson_id => $lesson) {
                if (isset($existingProgress['lessons']['list'][$lesson_id]) || isset($newProgress['lessons']['list'][$lesson_id])) {
                    $newProgress['lessons']['list'][$lesson_id]['completed'] = $existingProgress['lessons']['list'][$lesson_id]['completed'] ?? $newProgress['lessons']['list'][$lesson_id]['completed'] ?? false;
                    $newProgress['lessons']['list'][$lesson_id]['submitted'] = $existingProgress['lessons']['list'][$lesson_id]['submitted'] ?? $newProgress['lessons']['list'][$lesson_id]['submitted'] ?? false;
                    $newProgress['lessons']['list'][$lesson_id]['at'] = $existingProgress['lessons']['list'][$lesson_id]['at'] ?? $newProgress['lessons']['list'][$lesson_id]['at'] ?? null;
                    $newProgress['lessons']['list'][$lesson_id]['previous'] = $existingProgress['lessons']['list'][$lesson_id]['previous'] ?? $newProgress['lessons']['list'][$lesson_id]['previous'] ?? 0;
                    $newProgress['lessons']['list'][$lesson_id]['lesson_end_at'] = $existingProgress['lessons']['list'][$lesson_id]['lesson_end_at'] ?? $newProgress['lessons']['list'][$lesson_id]['lesson_end_at'] ?? null;
                    $newProgress['lessons']['list'][$lesson_id]['completed_at'] = $existingProgress['lessons']['list'][$lesson_id]['completed_at'] ?? $newProgress['lessons']['list'][$lesson_id]['completed_at'] ?? null;
                    $newProgress['lessons']['list'][$lesson_id]['submitted_at'] = $existingProgress['lessons']['list'][$lesson_id]['submitted_at'] ?? $newProgress['lessons']['list'][$lesson_id]['submitted_at'] ?? null;
                    $newProgress['lessons']['list'][$lesson_id]['marked_at'] = $existingProgress['lessons']['list'][$lesson_id]['marked_at'] ?? $newProgress['lessons']['list'][$lesson_id]['marked_at'] ?? null;

                    $newProgress['lessons']['list'][$lesson_id]['topics']['passed'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['passed'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['passed'] ?? 0);
                    $newProgress['lessons']['list'][$lesson_id]['topics']['count'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['count'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['count'] ?? 0);
                    $newProgress['lessons']['list'][$lesson_id]['topics']['submitted'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['submitted'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['submitted'] ?? 0);

                    if ($lesson['topics']['count'] > 0) {
                        foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] ?? false;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] ?? false;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['at'] ?? null;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['previous'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['previous'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['previous'] ?? 0;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'] ?? null;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'] ?? null;
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] ?? null;

                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] ?? 0);
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] ?? 0);
                            $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] = self::bestValue($existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] ?? 0, $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] ?? 0);

                            if ($newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] > 0) {
                                foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] ?? false;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed'] ?? false;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted'] ?? false;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['at'] ?? null;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['previous'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['previous'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['previous'] ?? 0;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['marked_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['marked_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['marked_at'] ?? null;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed_at'] ?? null;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['failed_at'] ?? null;
                                    $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted_at'] = $existingProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted_at'] ?? $newProgress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted_at'] ?? null;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $newProgress;
    }

    public static function bestValue($val1, $val2)
    {
        return max($val1, $val2);
    }

    public static function updateAdminReportProgress($user_id, $course_id)
    {
        $adminReportService = new AdminReportService($user_id, $course_id);

        return $adminReportService->updateProgress();
    }

    public static function markProgressDetails($student_id, $progress, $data, $auto_correct = false)
    {
        if (empty($progress)) {
            return [];
        }
        $topicTime = [];
        if (empty($student_id)) {
            $student_id = $data['option']['user_id'] ?? $data['user_id'] ?? null;
        }
        if (empty($student_id)) {
            return [];
        }
        if (isset($data['option']['lesson'])) {
            $lesson_id = $data['option']['lesson'];
            $marked_at_time = time();

            $lessonMarkedActivity = app()[StudentActivityService::class]->getActivityWhere([
                'activity_event' => 'LESSON MARKED',
                'actionable_type' => Lesson::class,
                'actionable_id' => $lesson_id,
            ]);
            $accessor = null;
            if (!empty($lessonMarkedActivity) || $lessonMarkedActivity->count() > 1) {
                $lessonMarkedActivity = $lessonMarkedActivity->first();
                if (!empty($lessonMarkedActivity)) {
                    $uId = json_decode($lessonMarkedActivity->getRawOriginal('activity_details'), true)['by'];
                    $accessor = User::find(intval($uId));
                    $accessed_on = $lessonMarkedActivity->activity_on;
                }
            }

            if (empty($accessor)) {
                $accessor = !empty(auth()->user()) ? auth()->user() : null;
            }
            if (empty($accessed_on)) {
                $accessed_on = Carbon::now()->toDateTimeString();
            }
            if (isset($data['option']['topic'])) {
                $topicTime[$lesson_id] = [];
                $topic_id = $data['option']['topic'];
                if (!isset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'])) {
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = false;
                }
                //                if ( !isset( $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'submitted' ] )
                //                    ||  !isset( $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'passed' ] ) ) {
                //                    $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'submitted' ] = 0;
                //                }
                //                if(!empty($progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'passed' ])) {
                //                    $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'passed' ]++;
                //                }else{
                //                    $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'passed' ] = 1;
                //                }

                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = $marked_at_time;
                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = $marked_at_time;
                if (!empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']) && $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] > 0) {
                    foreach ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'] as $quiz_id => $quiz) {
                        $attempt = QuizAttempt::updateOrCreate(['user_id' => $data['option']['user_id'] ?? $student_id, 'quiz_id' => $quiz_id], [
                            'user_id' => $data['option']['user_id'] ?? $student_id,
                            'course_id' => $data['option']['course_id'],
                            'lesson_id' => $lesson_id,
                            'topic_id' => $topic_id,
                            'quiz_id' => $quiz_id,
                            'questions' => [],
                            'submitted_answers' => [],
                            'attempt' => 1,
                            'system_result' => 'MARKED',
                            'status' => 'SATISFACTORY',
                            'user_ip' => request()->ip(),
                            'submitted_at' => null,
                            'created_at' => $accessed_on,
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]);
                        if (!empty($attempt)) {
                            self::updateOrCreateStudentActivity(
                                $attempt,
                                'ASSESSMENT MARKED',
                                $student_id,
                                [
                                    'activity_on' => $attempt->updated_at,
                                    'student' => $student_id,
                                    'status' => $attempt->status,
                                    'accessor_id' => !empty($accessor) ? $accessor->id : null,
                                    'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                                    'accessed_at' => !empty($accessed_on) ? $accessed_on : Carbon::now()->toDateTimeString(),
                                ]
                            );
                            event(new \App\Events\QuizAttemptStatusChanged($attempt));
                        }

                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['marked_at'] = $attempt->getRawOriginal('updated_at') ?? $marked_at_time;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['attempted_at'] = $attempt->getRawOriginal('updated_at') ?? $marked_at_time;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] = true;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['attempted'] = true;
                    }
                }

                $progress['lessons']['list'][$lesson_id]['topics']['count'] = 0;
                $progress['lessons']['list'][$lesson_id]['topics']['passed'] = 0;
                $progress['lessons']['list'][$lesson_id]['topics']['submitted'] = 0;
                foreach ($progress['lessons']['list'][$lesson_id]['topics']['list'] as $topic_id => $topic) {
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                    $progress['lessons']['list'][$lesson_id]['topics']['count']++;
                    if ($topic['completed']) {
                        $progress['lessons']['list'][$lesson_id]['topics']['passed']++;
                    }
                    if ($topic['submitted']) {
                        $progress['lessons']['list'][$lesson_id]['topics']['submitted']++;
                    }
                    $topicModel = Topic::where('id', $topic_id)->first();
                    $topicTime[$lesson_id][$topic_id] = floatval($topicModel->estimated_time);

                    self::updateOrCreateStudentActivity(
                        $topicModel,
                        'TOPIC MARKED',
                        $student_id,
                        [
                            'activity_on' => $marked_at_time,
                            'total_quizzes' => $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'],
                            'topic_time' => $topicModel->estimated_time,
                            'marked_by_id' => !empty($accessor) ? $accessor->id : null,
                            'marked_by_role' => !empty($accessor) ? $accessor->roleName() : null,
                            'marked_at' => $marked_at_time,
                        ]
                    );
                }
            } else {
                $topicTime[$lesson_id] = [];
                if (!isset($progress['lessons']['list'][$lesson_id]['submitted'])) {
                    $progress['lessons']['list'][$lesson_id]['submitted'] = false;
                }
                if (!isset($progress['lessons']['submitted'])) {
                    $progress['lessons']['submitted'] = 0;
                }
                if (!isset($progress['lessons']['passed'])) {
                    $progress['lessons']['passed'] = 0;
                }
                $progress['lessons']['submitted']++;
                $progress['lessons']['passed']++;
                $progress['lessons']['list'][$lesson_id]['attempted'] = true;
                $progress['lessons']['list'][$lesson_id]['completed'] = true;
                $progress['lessons']['list'][$lesson_id]['marked_at'] = $marked_at_time;
                $progress['lessons']['list'][$lesson_id]['attempted_at'] = $marked_at_time;
                if (auth()->user()->can('update students')) {
                    if (count($progress['lessons']['list'][$lesson_id]['topics']['list']) > 0) {
                        $progress['lessons']['list'][$lesson_id]['topics']['count'] = count($progress['lessons']['list'][$lesson_id]['topics']['list']);
                        $progress['lessons']['list'][$lesson_id]['topics']['passed'] = $progress['lessons']['list'][$lesson_id]['topics']['submitted'] ?? 0;

                        if ($auto_correct) {
                            $progress['lessons']['list'][$lesson_id]['topics']['passed'] = $progress['lessons']['list'][$lesson_id]['topics']['count'] ?? 0;
                        }

                        foreach ($progress['lessons']['list'][$lesson_id]['topics']['list'] as $topic_id => $topic) {
                            if (!empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted']) || $auto_correct) {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = $marked_at_time;
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = $marked_at_time;

                                $topicModel = Topic::where('id', $topic_id)->first();
                                $topicTime[$lesson_id][$topic_id] = floatval($topicModel->estimated_time);

                                self::updateOrCreateStudentActivity(
                                    $topicModel,
                                    'TOPIC MARKED',
                                    $student_id,
                                    [
                                        'activity_on' => $marked_at_time,
                                        'total_quizzes' => $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'],
                                        'topic_time' => $topicModel->estimated_time,
                                        'marked_by_id' => !empty($accessor) ? $accessor->id : null,
                                        'marked_by_role' => !empty($accessor) ? $accessor->roleName() : null,
                                        'marked_at' => $marked_at_time,
                                    ]
                                );
                            }
                            if (count($topic['quizzes']['list']) > 0) {
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] = count($topic['quizzes']['list']);
                                $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] = $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] ?? 0;

                                if ($auto_correct) {
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] = $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] ?? 0;
                                }
                                if ($topic['quizzes']['count'] > 0) {
                                    foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                        $attempt = QuizAttempt::updateOrCreate(['user_id' => $data['option']['user_id'] ?? $student_id, 'quiz_id' => $quiz_id], [
                                            'user_id' => $data['option']['user_id'] ?? $student_id,
                                            'course_id' => $data['option']['course_id'],
                                            'lesson_id' => $lesson_id,
                                            'topic_id' => $topic_id,
                                            'quiz_id' => $quiz_id,
                                            'questions' => [],
                                            'submitted_answers' => [],
                                            'attempt' => 1,
                                            'system_result' => 'MARKED',
                                            'status' => 'SATISFACTORY',
                                            'user_ip' => request()->ip(),
                                            'submitted_at' => null,
                                            'created_at' => $accessed_on,
                                            'updated_at' => Carbon::now()->toDateTimeString(),
                                        ]);
                                        if (!empty($attempt)) {
                                            self::updateOrCreateStudentActivity(
                                                $attempt,
                                                'ASSESSMENT MARKED',
                                                $student_id,
                                                [
                                                    'activity_on' => $attempt->updated_at,
                                                    'student' => $student_id,
                                                    'status' => $attempt->status,
                                                    'accessor_id' => !empty($accessor) ? $accessor->id : null,
                                                    'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                                                    'accessed_at' => !empty($accessed_on) ? $accessed_on : Carbon::now()->toDateTimeString(),
                                                ]
                                            );
                                            event(new \App\Events\QuizAttemptStatusChanged($attempt));
                                        }
                                        if (!empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['submitted'])
                                            || $attempt->system_result === 'MARKED' || $auto_correct) {
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['marked_at'] = $attempt->getRawOriginal('updated_at') ?? $marked_at_time;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['attempted_at'] = $attempt->getRawOriginal('updated_at') ?? $marked_at_time;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] = true;
                                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['attempted'] = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($progress['lessons']['list'][$lesson_id]['completed'])) {
                $lesson_end_at = $progress['lessons']['list'][$lesson_id]['marked_at']
                    ?? $progress['lessons']['list'][$lesson_id]['completed_at']
                    ?? $progress['lessons']['list'][$lesson_id]['attempted_at']
                    ?? Carbon::now()->toDateTimeString();
                $lastTopicItem = end($progress['lessons']['list'][$lesson_id]['topics']['list']);
                if (!empty($lastTopicItem['completed'])) {
                    $lesson_end_at = $lastTopicItem['marked_at'] ?? $lastTopicItem['completed_at'] ?? $lastTopicItem['attempted_at'];
                }
                self::updateOrCreateStudentActivity(
                    Lesson::where('id', $lesson_id)->first(),
                    'LESSON END',
                    $student_id,
                    [
                        'activity_on' => $lesson_end_at,
                        'student' => $student_id,
                        'lesson_time' => !empty($topicTime[$lesson_id]) ? array_sum($topicTime[$lesson_id]) : 0.00,
                        'topics_time' => !empty($topicTime[$lesson_id]) ? $topicTime[$lesson_id] : 0.00,
                        'at' => Carbon::now()->toDateTimeString(),
                        'id' => auth()->user()?->id,
                        'by' => auth()->user()?->roleName(),
                        'end_at' => $lesson_end_at,
                        'accessor_id' => auth()->user()?->id,
                        'accessor_role' => auth()->user()?->roleName(),
                    ]
                );
            }
            if ($progress['lessons']['list'][$lesson_id]['topics']['count'] > 0) {
                foreach ($progress['lessons']['list'][$lesson_id]['topics']['list'] as $topic_id => $topic) {
                    if (!empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'])) {
                        $topic_end_at = $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at']
                            ?? $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at']
                            ?? $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at']
                            ?? Carbon::now()->toDateTimeString();
                        $lastQuizItem = end($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list']);
                        if (!empty($lastQuizItem['completed'])) {
                            $topic_end_at = $lastQuizItem['marked_at'] ?? $lastQuizItem['completed_at'] ?? $lastQuizItem['attempted_at'];
                        }
                        $topicModel = Topic::where('id', $topic_id)->first();
                        self::updateOrCreateStudentActivity(
                            $topicModel,
                            'TOPIC END',
                            $student_id,
                            [
                                'activity_on' => $topic_end_at,
                                'student' => $student_id,
                                'total_quizzes' => $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'],
                                'topic_time' => $topicModel->estimated_time,
                                'end_at' => $topic_end_at,
                                'accessor_id' => auth()->user()?->id,
                                'accessor_role' => auth()->user()?->roleName(),
                            ]
                        );
                    }
                }
            }
        }

        return self::reEvaluateProgress($data['option']['user_id'] ?? $student_id, $progress);
        // return self::refreshProgress($progress, false, $data['option']);
    }

    public static function correctProgressDetails($student_id, $progress, $data): array
    {
        return self::markProgressDetails($student_id, $progress, $data, true);
    }

    public static function refreshProgress($progress, $reEvaluate = true, $option = [])
    {
        $activityService = app()[StudentActivityService::class];

        $student_id = $data['option']['user_id'] ?? $option['user_id'];

        if (env('APP_ENV') === 'local') {
            $activityService->setActivity([
                'user_id' => $student_id,
                'activity_event' => 'PROGRESS REFRESH',
                'activity_details' => [
                    'user_id' => $student_id,
                    'reEval' => $reEvaluate,
                    'progress' => $progress,
                    'at' => Carbon::now()->toDateTimeString(),
                ],
            ], auth()->user());
        }
        foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {
            // LESSONS
            $isLessonAlreadyMarked = !empty($lesson['marked_at']);

            if ($reEvaluate) {
                foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                    // TOPICS
                    $countQuizzes = self::getCountQuizzes($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id], $isLessonAlreadyMarked);
                    //                    dd($countQuizzes,$progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]);
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted'] = $countQuizzes['submitted'];
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed'] = $countQuizzes['passed'];
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['failed'] = $countQuizzes['failed'] ?? 0;
                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] = $countQuizzes['count'];

                    if ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count'] !== 0 || $isLessonAlreadyMarked) {
                        $quizzesPassed = ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['passed']
                            === $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count']);
                        $quizzesSubmitted = ($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['submitted']
                            === $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count']);

                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted'] = $quizzesSubmitted;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = $quizzesPassed;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['at'] = time();
                        if ($quizzesPassed || $quizzesSubmitted) {
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted'] = true;
                        }

                        if ($quizzesSubmitted && empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'])) {
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['submitted_at'] = Carbon::now()->toDateTimeString();
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = Carbon::now()->toDateTimeString();
                        }
                        if ($quizzesPassed && empty($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'])) {
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed_at'] = Carbon::now()->toDateTimeString();
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['attempted_at'] = Carbon::now()->toDateTimeString();
                        }
                    }
                    // TOPICS
                }
            }
            $countTopics = self::getCountTopics($progress['lessons']['list'][$lesson_id], $isLessonAlreadyMarked);

            $progress['lessons']['list'][$lesson_id]['topics']['passed'] = $countTopics['passed'];
            $progress['lessons']['list'][$lesson_id]['topics']['submitted'] = $countTopics['submitted'];
            $progress['lessons']['list'][$lesson_id]['topics']['count'] = $countTopics['count'];

            if ((!$reEvaluate && $lesson_id === $option['lesson'])
                || ($reEvaluate && $progress['lessons']['list'][$lesson_id]['topics']['count'] !== 0) || $isLessonAlreadyMarked) {
                $topicsPassed = ($progress['lessons']['list'][$lesson_id]['topics']['passed']
                    === $progress['lessons']['list'][$lesson_id]['topics']['count']);
                $topicsSubmitted = ($progress['lessons']['list'][$lesson_id]['topics']['submitted']
                    === $progress['lessons']['list'][$lesson_id]['topics']['count']);

                $progress['lessons']['list'][$lesson_id]['submitted'] = $topicsSubmitted;
                $progress['lessons']['list'][$lesson_id]['completed'] = $topicsPassed;
                $progress['lessons']['list'][$lesson_id]['at'] = time();
                if ($topicsPassed || $topicsSubmitted) {
                    $progress['lessons']['list'][$lesson_id]['attempted'] = true;
                }
                if ($topicsSubmitted && empty($progress['lessons']['list'][$lesson_id]['submitted_at'])) {
                    $progress['lessons']['list'][$lesson_id]['submitted_at'] = Carbon::now()->toDateTimeString();
                    $progress['lessons']['list'][$lesson_id]['attempted_at'] = Carbon::now()->toDateTimeString();
                }
                if ($topicsPassed && empty($progress['lessons']['list'][$lesson_id]['completed_at'])) {
                    $progress['lessons']['list'][$lesson_id]['completed_at'] = Carbon::now()->toDateTimeString();
                    $progress['lessons']['list'][$lesson_id]['attempted_at'] = Carbon::now()->toDateTimeString();
                }
            }
            if ((($countTopics['empty'] + $countTopics['submitted']) === $countTopics['count'])
                || ($progress['lessons']['list'][$lesson_id]['topics']['submitted'] === $progress['lessons']['list'][$lesson_id]['topics']['count'])) {
                $progress['lessons']['list'][$lesson_id]['lesson_end_at'] = Carbon::now()->toDateTimeString();
                $isLessonAlreadyEnded = $activityService->getActivityWhere(['actionable_id' => $lesson_id, 'user_id' => $student_id, 'activity_event' => 'LESSON END'])->count() < 1;

                if ($isLessonAlreadyEnded) {
                    $activityService->setActivity([
                        'activity_event' => 'LESSON END',
                        'activity_details' => [
                            'user_id' => $student_id,
                            'student' => $student_id,
                            'at' => Carbon::now()->toDateTimeString(),
                            'id' => auth()->user()?->id,
                            'by' => auth()->user()?->roleName(),
                            'end_at' => $lesson['submitted_at'] ?? Carbon::now()->toDateTimeString(),
                            'accessor_id' => auth()->user()?->id,
                            'accessor_role' => auth()->user()?->roleName(),
                        ],
                        'user_id' => $student_id,
                    ], Lesson::where('id', $lesson_id)->first());
                }
            } else {
                $progress['lessons']['list'][$lesson_id]['lesson_end_at'] = ($isLessonAlreadyMarked) ? $lesson['marked_at'] : null;
                if (!$isLessonAlreadyMarked) {
                    $activityLesson = $activityService->getActivityWhere(['actionable_id' => $lesson_id, 'user_id' => $student_id, 'activity_event' => 'LESSON END']);

                    if ($activityLesson->count() > 0) {
                        $activityId = $activityLesson->first();
                        $activityService->delete($activityId->id);
                    }
                }
            }
            // LESSONS
        }
        $countLessons = self::getCountLessons($progress);
        $progress['lessons']['passed'] = $countLessons['passed'];
        $progress['lessons']['submitted'] = $countLessons['submitted'];
        $progress['lessons']['count'] = $countLessons['count'];
        $lessonsPassed = ($progress['lessons']['count']
            === $progress['lessons']['passed']);

        $progress['completed'] = $lessonsPassed;
        $progress['at'] = time();

        return $progress;
    }

    public static function getCountQuizzes($topic, $isLessonAlreadyEnded = false): array
    {
        $total = 0;
        $passed = 0;
        $failed = 0;
        $submitted = 0;
        $quizzes = [];

        if (!empty($topic['quizzes'])) {
            foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                if (!empty($quiz)) {
                    $total++;
                }
                if (!empty($quiz['passed'])) {
                    $quizzes[$quiz_id] = 1;
                    //                    $passed++;
                } elseif ($isLessonAlreadyEnded) {
                    $quizzes[$quiz_id] = 1;
                    //                    $passed++;
                }
                if (!empty($quiz['failed'])) {
                    $quizzes[$quiz_id] = 0;
                    //                    $failed++;
                }
                if (!empty($quiz['submitted'])) {
                    $quizzes[$quiz_id] = 2;
                    //                    $submitted++;
                }
            }
        }
        foreach ($quizzes as $quiz) {
            if ($quiz == 1) {
                $passed++;
            } elseif ($quiz == 0) {
                $failed++;
            } elseif ($quiz == 2) {
                $submitted++;
            }
        }
        $result = ['passed' => $passed, 'failed' => $failed, 'submitted' => $submitted, 'count' => $total];

        return $result;
    }

    public static function getCountTopics($lesson, $isLessonAlreadyEnded = false): array
    {
        $total = 0;
        $passed = 0;
        $submitted = 0;
        $empty = 0;
        $attempted = 0;
        if (!empty($lesson['topics'])) {
            foreach ($lesson['topics']['list'] as $topic) {
                if (!empty($topic)) {
                    $total++;
                }
                if (!empty($topic['completed'])) {
                    $passed++;
                } elseif ($isLessonAlreadyEnded) {
                    $passed++;
                } else {
                    $topicPassed = self::getCountQuizzes($topic, $isLessonAlreadyEnded);
                    if ($topicPassed['passed'] === $topicPassed['count'] && $topicPassed['count'] > 0) {
                        $passed++;
                    }
                }
                if (!empty($topic['submitted'])) {
                    $submitted++;
                } else {
                    $topicPassed = self::getCountQuizzes($topic, $isLessonAlreadyEnded);
                    if ($topicPassed['submitted'] === $topicPassed['count'] && $topicPassed['count'] > 0) {
                        $submitted++;
                    }
                }
                if (!empty($topic['attempted'])) {
                    $attempted++;
                }
            }
        }

        return ['passed' => $passed, 'submitted' => $submitted, 'attempted' => $attempted, 'count' => $total, 'empty' => $empty];
    }

    public static function getCountLessons($progress): array
    {
        $total = 0;
        $passed = 0;
        $submitted = 0;
        $empty = 0;
        if (!empty($progress['lessons'])) {
            foreach ($progress['lessons']['list'] as $lesson) {
                if (empty($lesson)) {
                    continue;
                }
                $total++;
                if (!empty($lesson['completed'])) {
                    $passed++;
                }
                if (!empty($lesson['submitted'])) {
                    $submitted++;
                }
                if ($lesson['topics']['count'] === 0) {
                    $empty++;
                }
            }
        }

        return ['passed' => $passed, 'submitted' => $submitted, 'count' => $total, 'empty' => $empty];
    }

    public static function getCourseStats($user_id, $course_id)
    {
        $return = [];
        $courseProgress = self::getProgress($user_id, $course_id);
        if (empty($courseProgress)) {
            $newProgress = self::populateProgress($course_id);
            $courseProgress = CourseProgress::create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'percentage' => self::getTotalCounts($user_id, $newProgress),
                'details' => $newProgress,
            ]);
        }
        //        Helper::debug([$courseProgress, $user_id, $course_id]);
        if (!empty($courseProgress)) {
            $courseProgressDetails = $courseProgress->details;

            // Add course data for LLND logic in getTotalQuizzes
            $courseProgressDetails['course'] = $course_id;

            // Use getTotalQuizzes for assessment progress (quiz counts only)
            $quizzesCount = self::getTotalQuizzes($courseProgressDetails, $user_id);

            $hoursReported = self::hoursReported($courseProgressDetails, $user_id);

            // Recalculate current course progress from fresh details instead of using stored percentage
            $totalCounts = self::getTotalCounts($user_id, $courseProgressDetails);
            $current_course_progress = self::calculatePercentage($totalCounts, $user_id, $course_id);

            // Use the recalculated totalCounts for expected percentage calculation
            $expectedPercentage = self::expectedPercentage($user_id, $course_id, $totalCounts);

            $totalLessons = Lesson::where('course_id', $course_id)
                ->whereRaw('LOWER(title) != "study tips"')->count();

            $totalCompetenciesIssued = Competency::where('is_competent', 1)
                ->where('user_id', $user_id)
                ->where('course_id', $course_id)
                ->count();
            $gap = ($expectedPercentage >= $current_course_progress) ?
                ($expectedPercentage - $current_course_progress) : 0;
            // dump('=== getCourseStats Percentage Debug ===');
            // dump('Raw percentage from database: ' . $courseProgress->getRawOriginal('percentage'));
            // dump('Calculated percentage (with LLND): ' . $current_course_progress);
            // dump('Expected percentage: ' . $expectedPercentage);
            // dump('Gap: ' . $gap);
            // dump('=== CourseProgress Accessor Debug ===');
            // dump('CourseProgress ID: ' . $courseProgress->id);
            // dump('Course ID: ' . $courseProgress->course_id);
            // dump('User ID: ' . $courseProgress->user_id);
            $return = [
                'id' => $courseProgress->id,
                'percentage' => $current_course_progress,
                'details' => $courseProgressDetails,
                'current_course_progress' => $current_course_progress,
                'expected_course_progress' => $expectedPercentage,
                'total_assignments' => $quizzesCount['total'],
                'total_assignments_remaining' => $quizzesCount['remaining'],
                'total_assignments_submitted' => $quizzesCount['submitted'],
                'total_assignments_satisfactory' => $quizzesCount['passed'],
                'total_assignments_not_satisfactory' => (!empty($quizzesCount['failed']) && $quizzesCount['failed'] > 0) ? $quizzesCount['failed'] : 0,
                'actual_reported' => (isset($hoursReported['actual']) ? $hoursReported['actual']['total'] : 0.00),
                'hours_reported' => (isset($hoursReported['reported']) ? $hoursReported['reported']['total'] : 0.00),
                'hours_details' => $hoursReported,
                'course_status' => ((isset($courseProgressDetails['completed'])) && ($courseProgressDetails['completed'] || $current_course_progress === 100.00)) ? 'COMPLETE' : 'NOT COMPLETE',
                'behind_schedule' => !(($gap <= 30)),
                'total_lessons' => $totalLessons,
                'total_competencies_issued' => $totalCompetenciesIssued,
                'is_course_completed' => ($totalLessons > 0) && ($totalLessons === $totalCompetenciesIssued),
            ];
        }

        // dd($return);
        return $return;
    }

    public static function getTotalQuizzes($progress, $user_id = null): array
    {
        $totalQuizzes = 0;
        $passedQuizzes = 0;
        $failedQuizzes = 0;
        $submittedQuizzes = 0;
        $remainingQuizzes = 0;
        $attemptingQuizzes = 0;

        // Convert progress to array if it's a Collection
        if ($progress instanceof \Illuminate\Support\Collection) {
            $progress = $progress->toArray();
        }

        // dump('=== getTotalQuizzes START ===');
        // dump('Progress data keys: ' . json_encode(array_keys($progress)));

        // Check if this is a main course for LLND logic
        $isMainCourse = false;
        if (isset($progress['course'])) {
            $course = $progress['course'];
            if (is_int($course) || is_string($course)) {
                $course = \App\Models\Course::find($course);
            }
            if ($course) {
                $isMainCourse = $course->is_main_course == 1 || !str_contains(strtolower($course->title), 'semester 2');
                // dump("getTotalQuizzes Course check - ID: {$course->id}, Title: {$course->title}, is_main_course: {$course->is_main_course}, isMainCourse: " . ($isMainCourse ? 'true' : 'false'));
            }
        }

        //        dump($progress['data']);
        if (!empty($progress['lessons']) && !empty($progress['lessons']['list'])) {
            $lessonIndex = 0;
            $lessonsList = is_array($progress['lessons']['list']) ? $progress['lessons']['list'] : $progress['lessons']['list']->toArray();
            foreach ($lessonsList as $lesson) {
                $isFirstLesson = $lessonIndex === 0;
                $lessonIndex++;

                $topicIndex = 0;
                $topicsList = is_array($lesson['topics']['list']) ? $lesson['topics']['list'] : $lesson['topics']['list']->toArray();
                foreach ($topicsList as $topic_id => $topic) {
                    $isFirstTopic = $isFirstLesson && $topicIndex === 0;
                    $topicIndex++;
                    if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                        if (!empty($user_id)) {
                            $quizzes = [];
                            $quizzesList = is_array($topic['quizzes']['list']) ? $topic['quizzes']['list'] : $topic['quizzes']['list']->toArray();
                            $quizIds = array_keys($quizzesList);
                            $quizIndex = 0;

                            // First, try to use progress details if they contain quiz data
                            $hasProgressData = false;
                            foreach ($quizzesList as $quiz_id => $quiz) {
                                if (isset($quiz['submitted']) && $quiz['submitted']) {
                                    $hasProgressData = true;

                                    break;
                                }
                            }

                            if ($hasProgressData) {
                                // Use progress details instead of database query
                                $totalQuizzes += $topic['quizzes']['count'] ?? 0;
                                $passedQuizzes += $topic['quizzes']['passed'] ?? 0;
                                $failedQuizzes += $topic['quizzes']['failed'] ?? 0;
                                $submittedQuizzes += $topic['quizzes']['submitted'] ?? 0;
                                $remainingQuizzes = $submittedQuizzes - $passedQuizzes - $failedQuizzes;

                                continue; // Skip database query
                            }

                            $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)
                                ->where('user_id', $user_id)
                                ->latestAttemptSubmittedOnly()
                                ->get();
                            $attempts = $quizAttempts->mapWithKeys(function ($item, $key) {
                                return [$item['quiz_id'] => $item->toArray()];
                            })->toArray();
                            $quizIds = array_keys($attempts);

                            // Check if first quiz is already passed (old registration)
                            $firstQuizAlreadyPassed = false;
                            if ($isFirstTopic && !empty($quizzesList)) {
                                $firstQuiz = reset($quizzesList);
                                $firstQuizAlreadyPassed = !empty($firstQuiz['status']) && $firstQuiz['status'] === 'SATISFACTORY';
                            }

                            // Apply LLND logic only for main courses and when first quiz is not already passed
                            if ($isMainCourse && $isFirstTopic && !$firstQuizAlreadyPassed) {
                                // dump("getTotalQuizzes LLND Logic Applied - Quiz ID: " . config('lln.quiz_id') . ", User ID: {$user_id}");

                                $llnQuizId = config('lln.quiz_id');

                                // Get LLND quiz attempts
                                $llnAttempts = \App\Models\QuizAttempt::where('user_id', $user_id)
                                    ->where('quiz_id', $llnQuizId)
                                    ->get();

                                $llnSubmitted = $llnAttempts->count();
                                $llnPassed = $llnAttempts->where('status', 'SATISFACTORY')->count();
                                $llnFailed = $llnAttempts->whereIn('status', ['FAIL', 'RETURNED'])->count();

                                // dump("getTotalQuizzes LLND Attempts - Total: {$llnSubmitted}, Passed: {$llnPassed}, Failed: {$llnFailed}");

                                // Replace first quiz with LLND quiz
                                if ($llnSubmitted > 0) {
                                    $totalQuizzes++; // Add LLND quiz to total
                                    $submittedQuizzes += $llnSubmitted;

                                    if ($llnPassed > 0) {
                                        $passedQuizzes++;
                                    } elseif ($llnFailed > 0) {
                                        $failedQuizzes++;
                                    }

                                    // dump("getTotalQuizzes LLND Applied - Updated counts - total: {$totalQuizzes}, submitted: {$submittedQuizzes}, passed: {$passedQuizzes}");
                                }

                                // Skip the original first quiz
                                continue;
                            }

                            // dump("getTotalQuizzes Processing topic quizzes - count: " . count($quizzesList));
                            //                            dump($quizIds);
                            //                            dump([$topic_id => count($topic[ 'quizzes' ][ 'list' ])] );
                            //                            $totalQuizzes += count( $topic[ 'quizzes' ][ 'list' ] );
                            foreach ($quizzesList as $quiz_id => $quiz) {
                                $totalQuizzes++;
                                // dump("getTotalQuizzes Processing quiz {$quiz_id} - total so far: {$totalQuizzes}");
                                if (in_array($quiz_id, $quizIds)) {
                                    $quizzes[$quiz_id] = 2;
                                    if (!empty($attempts[$quiz_id]['status'])) {
                                        if ($attempts[$quiz_id]['status'] === 'ATTEMPTING') {
                                            $quizzes[$quiz_id] = 4;
                                        } elseif (in_array($attempts[$quiz_id]['status'], ['REVIEWING', 'SUBMITTED'])) {
                                            $quizzes[$quiz_id] = 3;
                                        } elseif (in_array($attempts[$quiz_id]['status'], ['RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'])) {
                                            $quizzes[$quiz_id] = 0;
                                        } elseif ($attempts[$quiz_id]['status'] === 'SATISFACTORY') {
                                            $quizzes[$quiz_id] = 1;
                                        }
                                    }
                                }
                                $quizIndex++;
                            }
                            foreach ($quizzes as $quiz) {
                                if ($quiz == 0) {// 'RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'
                                    $failedQuizzes++;
                                } elseif ($quiz == 1) {// SATISFACTORY
                                    $passedQuizzes++;
                                } elseif ($quiz == 3) {// 'REVIEWING', 'SUBMITTED'
                                    $remainingQuizzes++;
                                } elseif ($quiz == 4) {// ATTEMPTING
                                    $attemptingQuizzes++;
                                }
                                $submittedQuizzes++;
                            }
                        } else {
                            // Use progress details when user_id is not provided
                            $totalQuizzes += $topic['quizzes']['count'] ?? 0;
                            $passedQuizzes += $topic['quizzes']['passed'] ?? 0;
                            $failedQuizzes += $topic['quizzes']['failed'] ?? 0;
                            $submittedQuizzes += $topic['quizzes']['submitted'] ?? 0;
                            $remainingQuizzes = $submittedQuizzes - $passedQuizzes - $failedQuizzes;
                        }
                    }
                }
            }
        }
        if ($failedQuizzes < 0) {
            $failedQuizzes = 0;
        }
        if ($passedQuizzes < 0) {
            $passedQuizzes = 0;
        }
        if ($submittedQuizzes < 0) {
            $submittedQuizzes = 0;
        }
        if ($totalQuizzes < 0) {
            $totalQuizzes = 0;
        }
        // LLND adjustments are already applied in the main loop above

        if ($remainingQuizzes < 0 && $remainingQuizzes > $totalQuizzes) {
            $remainingQuizzes = 0;
        }

        $result = ['passed' => $passedQuizzes, 'failed' => $failedQuizzes, 'remaining' => $remainingQuizzes, 'submitted' => $submittedQuizzes, 'attempting' => $attemptingQuizzes, 'total' => $totalQuizzes];

        // dump("=== getTotalQuizzes FINAL RESULTS ===");
        // dump("Final counts - total: {$totalQuizzes}, passed: {$passedQuizzes}, failed: {$failedQuizzes}, submitted: {$submittedQuizzes}, remaining: {$remainingQuizzes}");
        // dump("Return array: " . json_encode($result));

        // dd($result);
        return $result;
    }

    public static function getCourseStatus(StudentCourseEnrolment $enrolment)
    {
        $enrolment->load('progress');
        $courseProgress = $enrolment->progress;
        $user_id = $enrolment->user_id;
        $course_id = $enrolment->course_id;

        $student = $enrolment->student;
        if (!empty($student) && \Str::lower($student->detail->status) === 'enrolled' && (!empty($student) && intval($student->is_active) === 1)) {
            return 'NOT STARTED';
        }

        if (!empty($courseProgress) && !empty($courseProgress->details)) {
            if ($enrolment->status === 'DELIST') {
                return 'DELIST';
            }

            // IF CP IS 100% but still have returned and pending assessments => ON SCHEDULE
            // if CP 100%, pending = 0,returned = 0 => COMPLETED
            if ((!empty($courseProgress->details['completed']) || $courseProgress->percentage === 100.00)) {
                $quizzesCount = CourseProgressService::getTotalQuizzes($courseProgress->details, $user_id);
                if ($quizzesCount['remaining'] > 0 || $quizzesCount['failed'] > 0) {
                    return 'ON SCHEDULE';
                }

                return 'COMPLETED';
            }

            if (self::parseDate($enrolment->getRawOriginal('course_ends_at'))->lessThan(Carbon::today(Helper::getTimeZone()))) {
                return 'BEHIND SCHEDULE';
            }
            //            if(auth()->user()->id === 1) {
            //                dump($courseProgress, 'NOT COMPLETED');
            //                dump(self::parseDate( $enrolment->getRawOriginal( 'course_ends_at' ) )->lessThan( Carbon::today( Helper::getTimeZone() )));
            //                dump($enrolment);
            //                dd(self::parseDate( $enrolment->getRawOriginal( 'course_ends_at' ) )->toDateString());
            //            }

            $percentage = (array)json_decode($courseProgress->getRawOriginal('percentage'));
            $current_course_progress = $courseProgress->percentage;

            $expected_course_progress = CourseProgressService::expectedPercentage($user_id, $course_id, $percentage);
            $gap = ($expected_course_progress >= $current_course_progress) ?
                ($expected_course_progress - $current_course_progress) : 0;

            //            if(auth()->user()->id === 1) {
            //                dd($courseProgress, 'NOT BEHIND SCHEDULE',$expected_course_progress,$current_course_progress,  $gap);
            //            }
            // ON SCHEDULE => GAP < 30, BEHIND SCHEDULE > 30 and end date passed
            return ($gap <= 30) ? 'ON SCHEDULE' : 'BEHIND SCHEDULE';
        }

        return;
    }

    public static function hoursReported($progress, $user_id): array
    {
        $totalTime = 0;
        $reportedTime = 0;
        $reportedTimeLastWeek = 0;
        $today = Carbon::today(Helper::getTimeZone());
        //        $thisWeek = $today->startOfWeek();
        //        $startThisWeek = clone( $thisWeek );
        $lastWeekStartDate = $today->subWeek();

        if (!empty($progress['lessons']) && !empty($progress['lessons']['list'])) {
            foreach ($progress['lessons']['list'] as $lesson) {
                foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                    $topicData = Topic::where('id', $topic_id)?->first();
                    $topic['data'] = !empty($topicData) ? $topicData->toArray() : (!empty($topic['data']) ? $topic['data'] : null);
                    if (!empty($topic['data'])) {
                        $totalTime += $topic['data']['estimated_time'];
                        if ($topic['data']['estimated_time'] > 0) {
                            if ($topic['completed'] || (isset($topic['submitted']) && $topic['submitted'] == true) || (isset($topic['attempted']) && $topic['attempted'] == true)) {
                                $reportedTime += floatval($topic['data']['estimated_time']);
                                $datetime = self::parseDate($topic['marked_at'] ?? $topic['submitted_at'] ?? $topic['attempted_at'] ?? $topic['at']);
                                if (!empty($datetime)) {
                                    $activity = $topicData->studentActivity()->where('activity_event', 'TOPIC END')->where('user_id', $user_id)->first();
                                    $activityOn = !empty($activity) ? self::parseDate($activity->activity_on) : Carbon::now();
                                    $submittedOn = (!empty($activityOn) && $activityOn->lessThanOrEqualTo($datetime)) ? $activityOn : $datetime;
                                    //                                    dump($activityOn, $activity->toArray(), $submittedOn);
                                    //                                    if(session()->get('student_id') === 3){
                                    //                                        dump([
                                    //                                            $topic[ 'submitted_at' ],
                                    //                                            $topic[ 'attempted_at' ],
                                    //                                            $topic[ 'at' ],
                                    //                                            $submittedOn->toDayDateTimeString(),
                                    //                                            $lastWeekStartDate->toDayDateTimeString(),
                                    //                                            $startThisWeek->toDayDateTimeString(),
                                    //                                            Carbon::today()->toDayDateTimeString(),
                                    //                                            $submittedOn->greaterThanOrEqualTo( $lastWeekStartDate ),
                                    //                                            $submittedOn->lessThan( $startThisWeek ),
                                    //                                            $submittedOn->lessThanOrEqualTo( Carbon::today() )
                                    //                                        ]);
                                    //                                    }
                                    $startLastWeekStartDate = $lastWeekStartDate->clone()->startOfWeek();
                                    $endLastWeekDate = $lastWeekStartDate->clone()->endOfWeek();
                                    //                                    dump($startLastWeekStartDate, $endLastWeekDate);
                                    if (!empty($submittedOn) && $submittedOn->greaterThanOrEqualTo($startLastWeekStartDate) && $submittedOn->lessThan($endLastWeekDate)) {
                                        $reportedTimeLastWeek += floatval($topic['data']['estimated_time']);
                                    }
                                }
                            }
                            /*elseif ( $topic[ 'quizzes' ][ 'count' ] > 0 ) {//only if we track incomplete topic progress
                                $submittedQuizzes = 0;
                                $submittedQuizzesLastWeek = 0;
                                foreach ( $topic[ 'quizzes' ][ 'list' ] as $quiz_id => $quiz ) {
                                    if ( $quiz[ 'passed' ] || ( isset( $quiz[ 'submitted' ] ) && $quiz[ 'submitted' ] == TRUE ) ) {
                                        $submittedQuizzes++;
                                        $submittedOn = self::parseDate( $quiz[ 'submitted_at' ] );
                                        if ( $submittedOn->greaterThanOrEqualTo( $lastWeekStartDate ) ) {
                                            $submittedQuizzesLastWeek++;
                                        }
                                    }
                                }
                                $f = floatval( $topic[ 'data' ][ 'estimated_time' ] ) / $topic[ 'quizzes' ][ 'count' ];
                                if ( $f > 0 ) {
                                    $reportedTime += ( $f * $submittedQuizzes );
                                    $reportedTimeLastWeek += ( $f * $submittedQuizzesLastWeek );
                                }
                            }*/
                        }
                    }
                }
            }
        }

        $totalTimeS = Helper::formatHoursToHHMM($totalTime); // sprintf( '%02d:%02d', (int)$totalTime, fmod( $totalTime, 1 ) * 60 );
        $totalTimeT = explode(':', $totalTimeS);
        $totalHours = $totalTimeT[0] ?? 0;
        $totalMinutes = $totalTimeT[1] ?? 0;

        $reportedTimeS = Helper::formatHoursToHHMM($reportedTime); // sprintf( '%02d:%02d', (int)$reportedTime, fmod( $reportedTime, 1 ) * 60 );
        $reportedTimeT = explode(':', $reportedTimeS);
        $reportedHours = $reportedTimeT[0] ?? 0;
        $reportedMinutes = $reportedTimeT[1] ?? 0;

        $reportedTimeLastWeekS = Helper::formatHoursToHHMM($reportedTimeLastWeek); // sprintf( '%02d:%02d', (int)$reportedTimeLastWeek, fmod( $reportedTimeLastWeek, 1 ) * 60 );
        $reportedTimeLastWeekT = explode(':', $reportedTimeLastWeekS);
        $reportedHoursLastWeek = $reportedTimeLastWeekT[0] ?? 0;
        $reportedMinutesLastWeek = $reportedTimeLastWeekT[1] ?? 0;

        return [
            'actual' => [
                'total' => $totalTime, 'hours' => $totalHours, 'minutes' => $totalMinutes, 'time' => $totalTimeS,
            ],
            'reported' => [
                'total' => $reportedTime, 'hours' => $reportedHours, 'minutes' => $reportedMinutes, 'time' => $reportedTimeS,
            ],
            'last_week' => [
                'total' => $reportedTimeLastWeek, 'hours' => $reportedHoursLastWeek, 'minutes' => $reportedMinutesLastWeek, 'time' => $reportedTimeLastWeekS,
            ],
        ];
    }

    public static function expectedPercentage($user_id, $course_id, $percentage)
    {
        if ($percentage === 0) {
            return 0;
        }

        $resp = 0;
        $enrolment = StudentCourseEnrolment::where('user_id', $user_id)->where('course_id', $course_id)->first();

        if (!empty($enrolment)) {
            $startDate = self::parseDate($enrolment->getRawOriginal('course_start_at'));
            $endDate = self::parseDate($enrolment->getRawOriginal('course_ends_at'));
            $now = Carbon::now()->toDateTimeString();

            if ($startDate->greaterThan($now)) {// COURSE NOT STARTED YET
                return 0;
            }

            if ($endDate->lessThanOrEqualTo($now)) {// COURSE END DATE ALREADY REACHED
                return 100;
            }

            $totals = $startDate->diff($endDate)->days;
            $diff = $startDate->diff($now)->days;

            $expectedVal = number_format(floatval((($diff / $totals) * 100)), 2);

            $resp = (($totals <= 0) ? 0 : (($expectedVal <= 100) ? $expectedVal : 100));
        }

        return $resp;
    }

    public static function getLessonQuizzes($progress, $lesson_id): array
    {
        $totalQuizzes = 0;
        $passedQuizzes = 0;
        $failedQuizzes = 0;
        $submittedQuizzes = 0;
        if (!empty($progress['lessons'])) {
            foreach ($progress['lessons']['list'] as $lid => $lesson) {
                if ($lid === $lesson_id && isset($topic['topics']['count']) && $lesson['topics']['count'] > 0) {
                    foreach ($lesson['topics']['list'] as $tid => $topic) {
                        if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                            $totalQuizzes += $topic['quizzes']['count'];
                            $passedQuizzes += $topic['quizzes']['passed'];
                            $failedQuizzes += $topic['quizzes']['failed'] ?? 0;
                            $submittedQuizzes += $topic['quizzes']['submitted'];
                        }
                    }

                    break;
                }
            }
        }

        return ['passed' => $passedQuizzes, 'failed' => $failedQuizzes, 'submitted' => $submittedQuizzes, 'total' => $totalQuizzes];
    }

    public static function getTopicQuizzes($progress, $topic_id): array
    {
        $totalQuizzes = 0;
        $passedQuizzes = 0;
        $failedQuizzes = 0;
        $submittedQuizzes = 0;
        if (!empty($progress['lessons'])) {
            foreach ($progress['lessons']['list'] as $lesson) {
                foreach ($lesson['topics']['list'] as $tid => $topic) {
                    if ($topic_id === $tid) {
                        if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                            $totalQuizzes += $topic['quizzes']['count'];
                            $passedQuizzes += $topic['quizzes']['passed'];
                            $failedQuizzes += $topic['quizzes']['failed'] ?? 0;
                            $submittedQuizzes += $topic['quizzes']['submitted'];
                        }

                        break 2;
                    }
                }
            }
        }

        return ['passed' => $passedQuizzes, 'failed' => $failedQuizzes, 'submitted' => $submittedQuizzes, 'total' => $totalQuizzes];
    }

    public static function getUpdatedQuizCounts($course_id, $user_id)
    {
        $total = 0;
        $passed = 0;
        $failed = 0;
        $submitted = 0;

        $courseProgress = self::getProgress($user_id, $course_id);
        //        dd($courseProgress);
        if (empty($courseProgress)) {
            return [
                'submitted' => $submitted,
                'passed' => $passed,
                'failed' => $failed,
                'total' => $total,
            ];
        }
        $progress = $courseProgress->details ? $courseProgress->details->toArray() : [];

        if (!empty($progress['lessons'])) {
            foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {
                //                dump( 'lessons' );
                if (!empty($lesson['topics']['list'])) {
                    foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                        //                        dump( 'topics' );
                        if (isset($topic['quizzes']['count']) && $topic['quizzes']['count'] > 0) {
                            //                            dump( 'has quiz count', $topic[ 'quizzes' ][ 'count' ] );
                            //                            $quizAttempts = QuizAttempt::whereIn( 'quiz_id', array_keys( $topic[ 'quizzes' ][ 'list' ] ) )
                            //                                                       ->where( 'user_id', $user_id )
                            //                                                       ->where( 'system_result', '!=', 'INPROGRESS' )->get();
                            //                            $attempts = $quizAttempts->mapWithKeys( function ( $item, $key ) {
                            //                                return [ $item[ 'id' ] => $item->toArray() ];
                            //                            } )->toArray();

                            $quizIds = array_keys($topic['quizzes']['list']);
                            $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)
                                ->where('user_id', $user_id)
                                ->where('system_result', '!=', 'INPROGRESS')->get();
                            $attempts = $quizAttempts->mapWithKeys(function ($item, $key) {
                                return [$item['quiz_id'] => $item->toArray()];
                            })->toArray();

                            //                            if(auth()->user()->id === 1261){
                            //                                dd($quizIds, array_keys($attempts), $quizAttempts, $attempts);
                            //                            }
                            $quizIds = array_keys($attempts);
                            $total = count($topic['quizzes']['list']);
                            foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                if (in_array($quiz_id, $quizIds)) {
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['submitted'] = true;
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['submitted_at'] = $attempts[$quiz_id]['created_at'];
                                    $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['at'] = $attempts[$quiz_id]['created_at'];

                                    if (in_array($attempts[$quiz_id]['status'], ['RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'])) {
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['passed'] = false;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['failed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['failed_at'] = $attempts[$quiz_id]['updated_at'];
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['at'] = $attempts[$quiz_id]['created_at'];
                                    }
                                    if ($attempts[$quiz_id]['status'] === 'SATISFACTORY') {
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['failed'] = false;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['passed'] = true;
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['passed_at'] = $attempts[$quiz_id]['updated_at'];
                                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes'][$quiz_id]['at'] = $attempts[$quiz_id]['created_at'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //            dd($progress);
            $courseProgress->details = $progress;
            //            $courseProgress->save();
        }

        return [
            'submitted' => $submitted,
            'passed' => $passed,
            'failed' => $failed,
            'total' => $total,
        ];
    }

    public static function getTrainingPlan($user_id, $raw = false): \Illuminate\Http\JsonResponse|array
    {
        $output = [];
        //        dd($user_id);
        $progresses = self::getStudentProgress($user_id);
        //        dd($user_id, $progresses);
        $loop = 0;
        if (!empty($progresses)) {
            foreach ($progresses as $progress) {
                if (empty($progress['course_enrolment_status']) || $progress['course_enrolment_status'] === 'DELIST') {
                    continue;
                }

                if (empty($progress['course_id'])) {
                    continue;
                }
                $course_id = intval($progress['course_id']);
                //                if($course_id !== 25){
                //                    continue;
                //                }
                $details = $progress['details'];
                if (empty($details['data'])) {
                    //                    $details['data'] = Course::where('id', intval($progress['course_id']))->get()->toArray();
                    $newProgress = self::reCalculateProgress($user_id, $course_id);
                    $details = self::attachModels($newProgress->details?->toArray());
                }

                if (empty($details)) {
                    continue;
                }

                $temp = [
                    'user_id' => $user_id,
                    'type' => 'course',
                    'title' => $details['data']['title'] ?? '',
                    'link' => route('lms.courses.show', $details['data']['id'] ?? ''),
                    'stats' => ['completed' => boolval($details['completed'])],
                    'status' => self::getStatus($details),
                    'percentage' => self::calculatePercentage($progress['percentage'], $progress['user_id'], $progress['course_id']),
                    'expected_percentage' => self::expectedPercentage($progress['user_id'], $progress['course_id'], $progress['percentage']),
                    //                    'data' => $details
                ];
                if ($details['lessons']['count'] > 0 && !empty($details['lessons']['list'])) {
                    foreach ($details['lessons']['list'] as $lesson_id => $lesson) {
                        //                        dd($lesson, boolval($lesson['completed']??false),boolval($lesson['submitted']??false));
                        $lesson_id = intval($lesson_id);
                        $lessonData = Lesson::where('id', $lesson_id)->first()?->toArray();
                        $competency = StudentCourseService::getCompetency($user_id, $lesson_id);
                        //                        $evidence = StudentCourseService::evidenceDetails( $user_id, $lesson_id );
                        //                        dump($lessonData);
                        $LLNLessonComplete = false;
                        if (
                            !empty($lessonData)
                            && (intval($lessonData['order']) === 0)
                            && (boolval($lesson['marked_at'] ?? $lesson['completed'] ?? false))
                        ) {
                            $LLNLessonComplete = true;
                        }

                        //                        dd( $user_id, $course_id, $LLNLessonComplete, $lesson );

                        if (empty($lessonData)) {
                            \Log::debug('Missing Lesson', ['user_id' => $user_id, 'lesson_id' => $lesson_id, 'data' => $lessonData]);

                            continue;
                        }

                        $lesson['data'] = $lessonData;
                        $temp['children'][$lesson_id] = [
                            'user_id' => $user_id,
                            'type' => 'lesson',
                            'title' => $lesson['data']['title'],
                            'link' => route('lms.lessons.show', $lesson['data']['id']),
                            'stats' => [
                                'completed' => boolval($lesson['completed'] ?? false),
                                'submitted' => boolval($lesson['submitted'] ?? false),
                                'is_marked_complete' => boolval($lesson['marked_at'] ?? false),
                            ],
                            'marked_at' => $lesson['marked_at'] ?? '',
                            'competency' => (!empty($competency) ? $competency->toArray() : false),
                            'status' => self::getStatus($lesson),
                            //                            'status' => isset($lesson['completed']) && $lesson['completed'] ? "COMPLETED" : (isset($lesson['submitted']) && $lesson['submitted'] ? "SUBMITTED" : "ATTEMPTING"),
                            'data' => !empty($lesson['data']) ? $lesson['data'] : [],
                        ];
                        $temp['children'][$lesson_id]['topic_count'] = 0;
                        $temp['children'][$lesson_id]['quiz_count'] = 0;
                        $temp['children'][$lesson_id]['evidence'] = ['status' => 'NOT COMPLETED'];
                        if (!empty($evidence)) {
                            $temp['children'][$lesson_id]['evidence'] = [
                                'status' => 'COMPLETED',
                                'created_at' => $evidence->created_at,
                                'lesson_id' => $lesson_id,
                                'user_id' => $user_id,
                                'file' => Storage::url($evidence->properties['file']['destination']),
                                'event' => $evidence->event,
                            ];
                        }

                        if ($lesson['topics']['count'] > 0 || $lessonData['has_topic'] > 0) {
                            $topicCount = 0;
                            $totalTopics = intval($lesson['topics']['count']);
                            $temp['children'][$lesson_id]['topic_count'] = $totalTopics;
                            foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                                $topicData = Topic::where('id', $topic_id)->first()?->toArray();
                                //                                dd($topicData);
                                if (empty($topicData)) {
                                    \Log::debug('Missing Topic', ['user_id' => $user_id, 'topic_id' => $topic_id, 'data' => $topicData]);

                                    continue;
                                }
                                $topic['data'] = $topicData;
                                $temp['children'][$lesson_id]['children'][$topic_id] = [
                                    'user_id' => $user_id,
                                    'type' => 'topic',
                                    'title' => $topic['data']['title'],
                                    'link' => route('lms.topics.show', $topic['data']['id']),
                                    'stats' => [
                                        'completed' => boolval($topic['completed'] ?? false),
                                        'submitted' => boolval($topic['submitted'] ?? false),
                                        'is_marked_complete' => boolval($topic['marked_at'] ?? false),
                                    ],
                                    'status' => self::getStatus($topic),
                                    //                                    'status' => $topic['completed'] ? "COMPLETED" : (isset($topic['submitted']) && $topic['submitted'] ? "SUBMITTED" : "ATTEMPTING"),
                                    'data' => !empty($topic['data']) ? $topic['data'] : [],
                                ];
                                //                                $lastTopic = Arr::last($lesson[ 'topics' ][ 'list' ] );
                                //                                $firstTopic = Arr::first($lesson[ 'topics' ][ 'list' ] );
                                if ($topic['quizzes']['count'] > 0 || $topicData['has_quiz'] > 0) {
                                    //                                    if($topicData[ 'order' ] > $lastTopic[ 'order' ]) {
                                    //                                        $lastTopic = $topicData;
                                    //                                    }
                                    //                                    if($topicData[ 'order' ] < $firstTopic[ 'order' ]) {
                                    //                                        $firstTopic = $topicData;
                                    //                                    }

                                    $quizCount = 0;
                                    $totalQuizzes = intval($topic['quizzes']['count']);
                                    $temp['children'][$lesson_id]['quiz_count'] = $totalQuizzes;
                                    $pendingChecklist = false;
                                    $notSatisfactoryChecklist = false;
                                    //                                    dd($topic['quizzes']);
                                    foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                        $attemptTemp = [];
                                        $quizAttemptCount = 0;
                                        $quizData = Quiz::where('id', $quiz_id)->first();
                                        if (empty($quizData)) {
                                            \Log::debug('Missing Quiz', ['user_id' => $user_id, 'quiz_id' => $quiz_id, 'data' => $quizData]);

                                            continue;
                                        }
                                        $quiz['data'] = $quizData?->toArray();

                                        $quizAttempts = QuizAttempt::where('user_id', $user_id)
                                            ->where('quiz_id', $quiz_id)
                                            ->latestThreeAttempts()->orderBy('created_at', 'ASC');
                                        //                                        if($course_id === 25 && $user_id === 2106){
                                        //                                            dump( $LLNLessonComplete, $quizAttempts->count() );
                                        //                                        }

                                        if ($LLNLessonComplete && $quizAttempts->count() === 0) {
                                            $currentDateTime = Carbon::now()->toDateTimeString();
                                            //                                            dd($currentDateTime);
                                            $attempt = QuizAttempt::firstOrCreate(
                                                ['user_id' => $user_id, 'quiz_id' => $quiz_id],
                                                [
                                                    'user_id' => $user_id,
                                                    'course_id' => $course_id,
                                                    'lesson_id' => $lesson_id,
                                                    'topic_id' => $topic_id,
                                                    'quiz_id' => $quiz_id,
                                                    'questions' => [],
                                                    'submitted_answers' => [],
                                                    'attempt' => 1,
                                                    'system_result' => 'MARKED',
                                                    'status' => 'SATISFACTORY',
                                                    'user_ip' => request()->ip(),
                                                    'accessor_id' => null,
                                                    'accessed_at' => null,
                                                    'submitted_at' => null,
                                                    'created_at' => $currentDateTime,
                                                    'updated_at' => $currentDateTime,
                                                ]
                                            );

                                            if (!empty($attempt)) {
                                                self::updateOrCreateStudentActivity(
                                                    $attempt,
                                                    'ASSESSMENT MARKED',
                                                    $attempt->user_id,
                                                    [
                                                        'activity_on' => $attempt->getRawOriginal('updated_at'),
                                                        'student' => $attempt->user_id,
                                                        'status' => $attempt->status,
                                                        'accessor_id' => !empty($accessor) ? $accessor->id : null,
                                                        'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                                                        'accessed_at' => !empty($attempt->created_at) ? $attempt->created_at : $currentDateTime,
                                                    ]
                                                );
                                                $quizAttempt = $quizAttempts->where('id', $attempt->id)->first();
                                                if (empty($quizAttempt)) {
                                                    $quizAttempts->add($quizAttempt);
                                                }
                                                $quiz['passed'] = true;
                                                // Update LLND progress to ensure proper percentage calculation
                                                self::updateLLNDProgress($user_id, $course_id, $lesson_id, $topic_id, $quiz_id);
                                                self::reCalculateProgress($user_id, $course_id);
                                                event(new \App\Events\QuizAttemptStatusChanged($attempt));
                                            }
                                        }
                                        //                                        dd($quizAttempts);
                                        $temp['children'][$lesson_id]['children'][$topic_id]['children'][$quiz_id] = [
                                            'user_id' => $user_id,
                                            'type' => 'quiz',
                                            'title' => $quizData?->title,
                                            'link' => route('lms.quizzes.show', $quizData?->id),
                                            'stats' => [
                                                'passed' => isset($quiz['passed']) && boolval($quiz['passed']),
                                                'submitted' => isset($quiz['submitted']) && boolval($quiz['submitted']),
                                            ],
                                            'status' => (isset($quiz['passed']) && $quiz['passed']) ? 'SATISFACTORY' : ((isset($quiz['failed']) && $quiz['failed']) ? 'NOT SATISFACTORY' : (isset($quiz['submitted']) && $quiz['submitted'] ? 'SUBMITTED' : 'ATTEMPTING')),
                                            'data' => !empty($quiz['data']) ? $quiz['data'] : [],
                                        ];

                                        $hasChecklist = $quizData->hasChecklist();
                                        $statusChecklist = [];
                                        $quizAttemptCount = $quizAttempts->count();
                                        if ($hasChecklist) {
                                            $tempChecklist = [
                                                'status' => '',
                                                'failed' => false,
                                                'current_quiz' => $quiz_id,
                                                'user_id' => $user_id,
                                                'attempts' => $quizAttemptCount,
                                            ];
                                            if (!$pendingChecklist && !$notSatisfactoryChecklist) {
                                                $statusChecklist = self::getCurrentChecklistStatus($quizData, $user_id, (bool)$quiz['submitted']);

                                                $quizKeys = array_keys($topic['quizzes']['list']);

                                                if (!empty($quizKeys[$quizCount + 1])
                                                    && (!empty($quizAttempts) && $quizAttemptCount > 0)) {
                                                    $nextQuiz = Quiz::find($quizKeys[$quizCount + 1] ?? 0) ?? null;
                                                    if (!empty($nextQuiz)) {
                                                        $submittedNextQuiz = (bool)$topic['quizzes']['list'][$quizKeys[$quizCount + 1]]['submitted'];
                                                        $tempChecklist['next_quiz'] = [
                                                            'data' => $nextQuiz->toArray(),
                                                            'is_submitted' => $submittedNextQuiz,
                                                        ];
                                                        if ($statusChecklist['status'] === 'NOT ATTEMPTED' && $submittedNextQuiz) {
                                                            $pendingChecklist = true;
                                                        }
                                                    }
                                                }

                                                if (!empty($statusChecklist['failed']) && $statusChecklist['failed'] === true) {
                                                    $notSatisfactoryChecklist = true;
                                                }

                                                /*if( !empty( $quizKeys[ $quizCount - 1])
                                                    && ( !empty( $quizAttempts ) && $quizAttempts->count() > 0 )){
                                                    $previousQuiz = Quiz::find( $quizKeys[ $quizCount -1 ] ?? 0 ) ?? NULL;
                                                    if(!empty($previousQuiz)){
                                                        $submittedPreviousQuiz = !!$topic[ 'quizzes' ][ 'list' ][$quizKeys[ $quizCount - 1 ]]['submitted'];
                                                        $tempChecklist[ 'prev_quiz' ] = [
                                                            'data' => $previousQuiz->toArray(),
                                                            'is_submitted' => $submittedPreviousQuiz,
                                                        ];
                                                    }
                                                }*/

                                                $tempChecklist = array_merge($tempChecklist, $statusChecklist);
                                                $tempChecklist[$topic_id]['details'][$quiz_id] = $statusChecklist;
                                            } elseif ($notSatisfactoryChecklist) {
                                                $tempChecklist['status'] = 'FAILED';
                                            } else {
                                                $tempChecklist['status'] = 'NOT ATTEMPTED';
                                            }
                                            $temp['children'][$lesson_id]['checklist'] = $tempChecklist;
                                            $temp['children'][$lesson_id]['children'][$topic_id]['checklist'] = $tempChecklist;
                                            $temp['children'][$lesson_id]['children'][$topic_id]['children'][$quiz_id]['checklist'] = $statusChecklist;
                                        }
                                        if (!empty($quizAttempts) && $quizAttemptCount > 0) {
                                            // As we are getting the desc submitteed quiz attempt
                                            $attempts = $quizAttempts->limit(3)->get();

                                            $firstAttempt = $attempts->first();
                                            //                                            if($quizAttemptCount > 1) {
                                            //                                                $firstAttempt = $quizAttempts->orderBy( 'id', 'ASC' )->first();
                                            //                                            }
                                            $firstAttemptArray = $firstAttempt->toArray();
                                            //                                            dd($attempts, $firstAttempt, $firstAttemptArray);
                                            $attemptCount = 0;
                                            $passedAttempt = null;
                                            if (!empty($attempts)) {
                                                foreach ($attempts as $attempt) {
                                                    $attemptCount++;

                                                    if ($attempt->status === 'SATISFACTORY' && empty($passedAttempt)) {
                                                        $passedAttempt = $attempt;
                                                        //                                                        $temp[ 'children' ][ $lesson_id ][ 'children' ][ $topic_id ][ 'children' ][ $quiz_id ][ 'children' ]['passed_attempt'] = $passedAttempt;
                                                    }

                                                    $attemptTemp = [
                                                        'user_id' => $user_id,
                                                        'type' => 'attempt',
                                                        'link' => route('assessments.show', $attempt->id),
                                                        'data' => $attempt->toArray(),
                                                        'passedAttempt' => $passedAttempt,
                                                        'status' => $attempt->status,
                                                    ];
                                                    $temp['children'][$lesson_id]['children'][$topic_id]['children'][$quiz_id]['children'][$attempt->id] = $attemptTemp;
                                                    //                                                    $temp[ 'children' ][ $lesson_id ][ 'children' ][ $topic_id ][ 'children' ][ $quiz_id ][ 'children' ]['attempts'] = count($attempts);

                                                    if ($attemptCount > 1) {
                                                        continue;
                                                    }

                                                    //                                                    $temp[ 'children' ][ $lesson_id ][ 'children' ][ $topic_id ][ 'children' ][ $quiz_id ][ 'children' ]['attempt_count'] = $attemptCount;

                                                    $lessonStartDate = !empty($firstAttempt->submitted_at) ? $firstAttempt->getRawOriginal('submitted_at') : $firstAttempt->getRawOriginal('created_at');
                                                    if (empty($temp['children'][$lesson_id]['start_date'])) {
                                                        $temp['children'][$lesson_id]['start_date'] = $lessonStartDate;
                                                    } elseif (self::parseDate($lessonStartDate)
                                                        ->lessThanOrEqualTo(self::parseDate($temp['children'][$lesson_id]['start_date']))) {
                                                        $temp['children'][$lesson_id]['start_date'] = $lessonStartDate;
                                                        $temp['children'][$lesson_id]['children'][$topic_id]['first_attempt'] = ['quiz' => $quiz, 'attempt' => $firstAttemptArray];
                                                        $temp['children'][$lesson_id]['first_attempt'] = ['quiz' => $quiz, 'attempt' => $firstAttemptArray];
                                                    }
                                                }
                                            }
                                            $passedAttempt = QuizAttempt::where('user_id', $user_id)
                                                ->where('quiz_id', $quiz_id)
                                                ->latestPassed()
                                                ->orderBy('created_at', 'DESC')->first();

                                            if (!empty($passedAttempt) && $topic['quizzes']['passed'] === $totalQuizzes
                                                && $passedAttempt->status === 'SATISFACTORY'
                                                && $temp['children'][$lesson_id]['status'] === 'COMPLETED') {
                                                $lastAttempt = ['quiz' => $quiz, 'attempt' => $passedAttempt->toArray()];
                                                $accessedAt = self::parseDate($passedAttempt->accessed_at);
                                                if (empty($temp['children'][$lesson_id]['last_quiz'])) {
                                                    $temp['children'][$lesson_id]['last_quiz'] = $lastAttempt;
                                                } elseif (!empty($accessedAt) && $accessedAt
                                                    ->greaterThanOrEqualTo(self::parseDate($temp['children'][$lesson_id]['last_quiz']['attempt']['accessed_at']))) {
                                                    $temp['children'][$lesson_id]['last_quiz'] = $lastAttempt;
                                                }
                                            }
                                        }
                                        $quizCount++;
                                    }
                                }
                                $topicCount++;
                                if ($topicCount === $totalTopics) {
                                    $temp['children'][$lesson_id]['last_topic'] = $topic;
                                }
                            }
                        }
                    }
                }
                $output[] = $temp;
            }
            if ($raw) {
                return $output;
            }

            return Helper::successResponse($output, 'Training Plan For ' . count($progresses) . ' Course(s)');
        }
        if ($raw) {
            return [];
        }

        return Helper::errorResponse('No progress made yet.', 404);
    }

    public static function getCurrentChecklistStatus(Quiz $quiz, $user_id, $submitted)
    {
        $tempChecklist = [
            'status' => 'NOT ATTEMPTED',
            'is_submitted' => $submitted,
            'quiz_id' => $quiz->id,
        ];

        if (!empty($quiz) && $submitted) {
            $currentChecklists = $quiz->attachedChecklistsFor($user_id);
            $checklistCount = $currentChecklists->count();

            $tempChecklist['is_submitted'] = true;
            $tempChecklist['count'] = $checklistCount;
            $tempChecklist['query'] = $currentChecklists->toSql();
            $tempChecklist['items'] = $currentChecklists->get()->toArray();

            $tempCL = $currentChecklists->orderBy('id', 'desc');
            $notSatisfactory = 0;
            foreach ($tempCL->get() as $checkListItem) {
                $status = $checkListItem->properties['status'] ?? 'N/A';
                if ($status === 'NOT SATISFACTORY') {
                    $notSatisfactory++;
                }
            }
            $firstChecklist = $tempCL->first();
            $tempChecklist['firstChecklist'] = $firstChecklist;

            if (!empty($firstChecklist)
                && !empty($firstChecklist->properties['status'])) {
                if ($firstChecklist->properties['status'] === 'NOT SATISFACTORY' || $notSatisfactory === 3) {
                    $tempChecklist['failed'] = true;
                    $tempChecklist['status'] = 'FAILED';
                    $tempChecklist['failed_on'] = $firstChecklist->created_at;
                } elseif ($firstChecklist->properties['status'] === 'SATISFACTORY') {
                    $tempChecklist['failed'] = false;
                    $tempChecklist['status'] = 'COMPLETED';
                    $tempChecklist['completed_on'] = $firstChecklist->submitted_at ?? $firstChecklist->created_at;
                }
            }
        }

        return $tempChecklist;
    }

    public static function getStudentProgress($user_id)
    {
        $courseProgress = CourseProgress::where('user_id', $user_id)
            ->where('course_id', '!=', config('constants.precourse_quiz_id', 0))
            ->get()?->toArray();

        return $courseProgress;
    }

    public static function getStatus($for): string
    {
        $status = 'ATTEMPTING';

        if ($for['completed'] || !empty($for['marked_at'])) {
            $status = 'COMPLETED';
        } elseif (!empty($for['submitted'])) {
            $status = 'SUBMITTED';
        }

        return $status;

        //        return isset($for['completed']) && $for['completed'] ? "COMPLETED" : (isset($for['submitted']) && $for['submitted'] ?  : "ATTEMPTING");
    }

    public static function fetchLLNDetails($user_id, $progress)
    {
        $isMainCourse = $progress->course && $progress->course->is_main_course;
        if ($isMainCourse) {
            // Fetch latest old LLN attempt status
            $oldLlnQuizId = config('constants.precourse_quiz_id', 0);
            $oldLlnAttempt = QuizAttempt::where('user_id', $user_id)
                ->where('quiz_id', $oldLlnQuizId)
                ->latest('id')
                ->first();

            // Fetch latest new LLN attempt status
            $newLlnQuizId = config('lln.quiz_id');
            $newLlnAttempt = QuizAttempt::where('user_id', $user_id)
                ->where('quiz_id', $newLlnQuizId)
                ->latest('id')
                ->first();

            return [
                'is_lln_lesson' => $newLlnAttempt?->status === 'SATISFACTORY' || $oldLlnAttempt?->status === 'SATISFACTORY',
                'lln_new_status' => $newLlnAttempt?->status,
                'lln_old_status' => $oldLlnAttempt?->status,
            ];
        } else {
            return [
                'is_lln_lesson' => false,
                'lln_new_status' => null,
                'lln_old_status' => null,
            ];
        }
    }

    public static function calculatePercentage($data, $user_id, $course_id): float
    {
        //        Helper::debug(['CourseProgress Service calculate Percentage',$data],'dd');
        //{"passed":55,"failed":0,"processed":87,"submitted":32,"total":83,"quizzes_passed":32,"quizzes_failed":0,"course_completed":false,"empty":22}
        if ($data['course_completed']) {
            return 100.00;
        }
        $percentage = 0.00;
        $adjust = ($data['passed'] === $data['empty']) ? $data['passed'] : ($data['passed'] - $data['empty']);
        $adjust = $data['total'] - $adjust;

        if (intval($data['total']) === 0) {
            $percentage = $data['course_completed'] ? 100.00 : 0.00;
        } else {
            $isMainCourse = cache()->remember("is_main_course_{$course_id}", now()->addHours(12), function () use ($course_id) {
                return Course::mainCourseOnly()->where('id', $course_id)->exists();
            });
            $user = User::find($user_id);
            $onboarded = $user->detail->onboard_at;
            //calculate percentage
            $percent = (($data['processed']) / $data['total']) * 100;
            //adjustment for main course
            if ($isMainCourse) {
                $percentage = ((empty($onboarded)) ? 0.00 : 5.00) + ($percent * 0.95);
            } else {
                $percentage = $percent;
            }

            $percentage = floatval(number_format($percentage, 2));

            if ($percentage > 100) {
                $percentage = 100.00;
            }
        }

        // Ensure LLND adjustments are properly reflected in percentage
        // $percentage = self::adjustPercentageForLLND($data, $percentage); // This is overriding our correct calculation

        //        if(auth()->user()->id === 1){
        //            dd($data, $percentage);
        //        }
        return $percentage;
    }

    public static function lastEvaluationOn($user_id, $quiz_id, $status = null)
    {
        $evaluation = Evaluation::where('student_id', $user_id)
            ->where('evaluable_type', 'App\Models\QuizAttempt')
            ->where('evaluable_id', $quiz_id);
        if (!empty($status)) {
            $evaluation = $evaluation->where('status', $status);
        }
        if (empty($evaluation)) {
            return '';
        }

        return $evaluation->first()?->getRawOriginal('updated_at');
    }

    public static function renderTrainingPlan($trainingPlan, $student, $studentActive = true): array
    {
        //        dd(json_encode($trainingPlan));
        $output = '';

        if (!empty($trainingPlan)) {
            $output = self::creatAccordion($trainingPlan, 'course', $student, $studentActive);
        }

        return ['html' => $output, 'raw' => $trainingPlan];
    }

    public static function creatAccordion($items, $type, $student, $studentActive, $inner = false, $count = '')
    {
        $output = '';
        $log = [];
        $activityService = app()[StudentActivityService::class];
        if (!empty($items) && count($items) > 0) {
            if ($inner) {
                $type = ($type === 'course') ? 'lesson' : (($type === 'lesson') ? 'topic' : (($type === 'topic') ? 'quiz' : 'attempt'));
            }

            $plural = Str::plural($type);
            $output = '<div class="accordion accordion-margin" id="accordion-' . $plural . $count . '">';
            foreach ($items as $id => $item) {
                //                if(empty($item[ 'data' ])){
                //                    continue;
                //                }
                $log[$type][$id] = [];
                if ($type === 'attempt') {
                    $activity = $activityService->getActivityWhere(
                        [
                        'activity_event' => 'ASSESSMENT MARKED',
                        'actionable_type' => QuizAttempt::class,
                        'actionable_id' => $item['data']['id'] ?? 0,
                        'user_id' => $student->id,
                    ]
                    );
                    $activity = $activity->sortByDesc('id')->first();
                    $activity_time = $item['data']['accessed_at'] ?? null;
                    //                    if($student->id === 5368  && $item['data']['id'] === 24917) {
                    //                        dd( $activity, $activity_time, $item['data'] );
                    //                    }
                    if (!empty($activity) && empty($activity_time)) {
                        $activity_time = $activity->activity_on;
                    }
                    //                    if($student->id === 5368  && $item['data']['id'] === 24917) {
                    //                        dd( $activity->activity_on, $activity_time, self::parseDate( $activity_time )->timezone( Helper::getTimeZone() )->format( 'j F, Y g:i A' ), $item['data'] );
                    //                    }
                    if (!empty($item['data'])) {
                        $output .= '<div class="alert alert-' . config('lms.status.' . $item['data']['status'] . '.class') . '" role="alert">
                                <div class="alert-body d-flex flex-row" data-attempt="' . $item['data']['id'] . '">
                                    <span class="me-2">Attempt#' . $item['data']['attempt'] . '</span>
                                    <span>&nbsp;</span>
                                    <span data-status="' . $item['data']['status'] . '" class="d-flex flex-grow-1 fw-bolder">' . (in_array($item['data']['status'], ['RETURNED', 'FAIL', 'OVERDUE']) ? 'NOT SATISFACTORY' : $item['data']['status']) . ':</span>';
                        $output .= '<strong class="me-4">' . self::parseDate($activity_time)->timezone(Helper::getTimeZone())->format('j F, Y g:i A') . '</strong>';
                        if (auth()->user()->can('mark assessments') && $item['data']['system_result'] !== 'INPROGRESS') {
                            $output .= '<a class="btn btn-primary btn-sm d-flex align-items-end" href="' . $item['link'] . '" target="_blank">Click here</a>';
                        }
                        $output .= '</div>
                            </div>';
                    }
                } else {
                    $output .= "<div class='accordion-item' data-id='" . ($item['data']['id'] ?? $id) . "'>";
                    $additional = '';
                    $checklistStatus = '';
                    $isCompetent = false;
                    if ($item['type'] === 'lesson') {
                        if (!empty($item['data']['has_topic']) && !empty($item['checklist'])) {
                            $checklistStatus = 'Checklist: ';
                            //                            if ( $item[ 'checklist' ][ 'status' ] === 'COMPLETED' ) {
                            //                                $checklistStatus .= "Evidence Required";
                            //                            } else {
                            $checklistStatus .= $item['checklist']['status'] ?? '';
                            //                            }
                            //                            $checklistStatus .= $item[ 'checklist' ][ 'failed' ] ? ' - FAILED' : '';
                            //                            if ( !empty( $item[ 'evidence' ] ) && $item[ 'evidence' ][ 'status' ] === 'COMPLETED' ) {
                            //                                $checklistStatus = "Checklist: Completed";
                            //                            }
                        }
                        $additional .= self::lessonTitleAdditional($item, $student, $activityService);
                        if (!empty($item['competency']) && $item['competency']['is_competent']) {
                            $isCompetent = true;
                        }
                    }

                    $output .= '<h2 class="accordion-header " id="heading' . ucfirst($item['type']) . $id . '">
                                <button
                                    class="accordion-button collapsed"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#accordion' . ucfirst($item['type']) . $id . '"
                                    aria-expanded="false"
                                    aria-controls="accordion' . ucfirst($item['type']) . $id . '">
                                        <span data-satus="' . ($isCompetent ? 'COMPETENT' : ($item['status'] ?? 'ATTEMPTING')) . '"
                                                class="circle-icon bg-' . ($isCompetent ? 'purple' : (config('lms.status.' . (!empty($item['stats']['is_marked_complete']) ? 'MARKED' : ($item['status'] ?? 'ATTEMPTING')) . '.class'))) . ' m-50"></span>
                                    <span class="' . ($item['type'] === 'lesson' ? 'd-flex justify-content-between flex-grow-1' : '') . '">
                                        <span class="flex-grow-1"> ' . ucfirst($item['type']) . ': ' . ($item['title'] ?? '') . ' </span>
                                        <small class="text-purple fw-bold flex-grow-1">' . ($isCompetent ? 'Competency Achieved' : '') . '</small>
                                        <small class="text-info fw-bold flex-grow-1">' . $checklistStatus . '</small>
                                        <small class="text-muted">' . $additional . '</small>
                                        <small class="text-primary ms-1 me-1" data-type="' . $item['type'] . '" data-checklist="' . (!empty($item['data']) ? $item['data']['has_checklist'] ?? 'NO' : '') . '">' .
                        ((auth()->user()->can('upload checklist') && $item['type'] === 'quiz' && !empty($item['data']) && intval($item['data']['has_checklist'] ?? 0) === 1) ? '* Checklist Required' : '')
                        . '</small>
                                    </span>
                                </button>
                            </h2>';

                    $output .= '<div
                                id="accordion' . ucfirst($item['type']) . $id . '"
                                class="accordion-collapse collapse"
                                aria-labelledby="heading' . ucfirst($item['type']) . $id . '"
                                data-bs-parent="#accordion-' . $plural . $count . '">';

                    $output .= '<div class="accordion-body">';
                    $output .= '<div class="d-flex flex-row">';
                    if ($item['type'] === 'quiz') {
                        $output .= self::quizAddons($item, $student, $id);
                    }
                    if ($item['type'] === 'lesson') {
                        $output .= self::lessonAddons($item, $student, $id, $studentActive);
                    }
                    if ($item['type'] === 'topic') {
                        $output .= self::topicAddons($item, $studentActive, $id);
                    }
                    $output .= '</div>';
                    if (isset($item['children']) && !empty($item['children'])) {
                        $output .= self::creatAccordion($item['children'], $item['type'], $student, $studentActive, true, $id);
                    }
                    $output .= '</div>';
                    $output .= '</div>';
                    $output .= '</div>';
                }
            }
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * @param string $output
     */
    public static function lessonAddons(mixed $item, $student, int|string $id, $studentActive): string
    {
        $studentActive = true;
        $output = '';

        $lessonData = Lesson::find($item['data']['id']);
        if (\Str::contains($lessonData->title, 'Study Tips')) {
            return $output;
        }
        $lessonCompetentReady = StudentCourseService::competencyCheck($student->id, $lessonData);
        $lessonEvidenceReady = StudentCourseService::evidenceReady($student->id, $lessonData);

        if (auth()->user()->can('mark work placement') && $item['type'] === 'lesson' && intval($item['data']['has_work_placement'] ?? 0) === 1) {
            $attachment = StudentLMSAttachables::forEvent('WORK_PLACEMENT')
                ->forAttachable(Lesson::class, $item['data']['id'])
                ->where('student_id', $student->id)?->first();
            //                        \Log::info($attachment);
            if (!empty($attachment)) {
                //                            \Log::info('Work Placement Competent');
                $output .= '<span class="fw-bold text-primary ms-2 me-2">Work Placement Completed</span>';
            } else {
                //                            \Log::info('Work Placement Pending');
                $output .= '<button class="btn btn-primary btn-sm d-flex align-items-start me-2" onclick="LMS.MarkWorkPlacement(' . $id . ',' . $item['user_id'] . ')">Mark Work Placement Completed</button>';
            }
        }

        if (auth()->user()->can('mark complete') && $item['type'] === 'lesson' && $item['status'] !== 'COMPLETED' && $studentActive) {
            $output .= '<button class="btn btn-success btn-sm d-flex align-items-end" onclick="LMS.MarkLessonComplete(' . $id . ',' . $item['user_id'] . ')">Mark Lesson Complete</button>';
        }
        //        if($item[ 'type' ] === 'lesson') {
        // //            $output .=  ' quiz_count '.$item[ 'quiz_count' ];
        // //            $output .=  ' topic_count '.$item[ 'topic_count' ];
        //            $output .=  ' competency '.var_dump($item[ 'competency' ]);
        //        }
        if (auth()->user()->can('mark complete') && $item['type'] === 'lesson' && $studentActive) {
            //                $output .= '<span> EVIDENCE'.$lessonEvidenceReady.' '. json_encode($item[ 'evidence' ]).'</span>';
            if (
                $lessonEvidenceReady ||
                $item['topic_count'] === 0 || $item['quiz_count'] === 0) {
                $output .= '<span class="border-light mx-1"></span>';
                $output .= '<div class="col-8 mb-2">';
                //                if ( isset( $item[ 'evidence' ][ 'status' ] ) && $item[ 'evidence' ][ 'status' ] === 'COMPLETED' ) {
                //                    $output .= '<span class="fw-normal ms-2 me-2"><span class="fw-bold text-purple ">Evidence Uploaded:</span> '
                //                        . self::parseDate( $item[ 'evidence' ][ 'created_at' ] )->timezone( Helper::getTimeZone() )->format( 'j F, Y' ) . '</span>';
                //                    $output .= '<a href="' . $item[ 'evidence' ][ 'file' ] . '" title="Evidence Checklist">View</a>';
                //                } else {
                //                    $output .= '<div class="d-flex flex-row"><span>Evidence Checklist: </span>';
                //                    $output .= '<input class="form-control me-2" type="file" name="evidence_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '" id="evidence_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '"
                //                                    data-format="pdf|doc|docx|zip|jpg|jpeg|xls|xlsx|ppt|pptx|png" accept=".pdf,.doc,.docx,.zip,.jpg,.jpeg,.xls,.xlsx,.ppt,.pptx,.png" />';
                // //                        $output .= '<div class="d-flex mt-1"><label class="form-label fw-bold font-small-4 py-1 pe-1 ">Status:</label><select data-placeholder="Status" class="form-select me-2" id="evidence_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '_status" name="status"><option></option><option value="SATISFACTORY">SATISFACTORY</option><option value="NOT SATISFACTORY">NOT SATISFACTORY</option></select>';
                //                    $output .= '<button class="btn btn-primary btn-sm" onclick="LMS.UploadEvidence(' . $item[ 'data' ][ 'id' ] . ', \'evidence_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '\',' . $item[ 'user_id' ] . ')">Upload Evidence Checklist</button>';
                // //                        $output .= '</div>';
                //                    $output .= '</div>';
                //                }
                $output .= '</div>';
            }
        }
        if ($lessonEvidenceReady && auth()->user()->can('mark competency') && $item['type'] === 'lesson' && $studentActive) {
            if (($lessonCompetentReady || $item['topic_count'] === 0 || $item['quiz_count'] === 0)
                && $item['status'] === 'COMPLETED'
                //                && ( isset( $item[ 'evidence' ][ 'status' ] ) && $item[ 'evidence' ][ 'status' ] === 'COMPLETED' )
            ) {
                if (is_array($item['competency']) && $item['competency']['is_competent']) {
                    $output .= '<span class="border-light mx-1"></span>';
                    $output .= '<span class="fw-normal ms-2 me-2"><span class="fw-bold text-purple ">Marked Competent:</span> '
                        . self::parseDate($item['competency']['competent_on'])->timezone(Helper::getTimeZone())->format('j F, Y')
                        . ' ( ' . $item['competency']['notes']['added_by']['user_name'] . ' )</span>';
                    //                    $output .= '<span class="fw-normal mx-1">' . $item[ 'competency' ][ 'notes' ][ 'remarks' ] . '</span>';
                } else {
                    $lessonStartDate = StudentCourseService::lessonStartDate($student->id, $item['data']['id']);
                    // lessonEndDate already returns timezone-aware formatted date from StudentCourseService
                    $lessonEndDate = StudentCourseService::lessonEndDate($student->id, $item['data']['id'], true);
                    // Calculate minDate using the timezone-aware end date
                    $minDate = $lessonEndDate ? Carbon::parse($lessonEndDate)->timezone(Helper::getTimeZone())->lessThan('2025-01-01') ? '2025-01-01' : $lessonEndDate : '2025-01-01';

                    // Format dates in user's local timezone using Y-m-d format for JavaScript compatibility
                    // This ensures consistent date handling between PHP and JavaScript
                    $formattedStartDateYmd = self::parseDate($lessonStartDate)->timezone(Helper::getTimeZone())->format('Y-m-d');
                    // lessonEndDate is already timezone-aware, just convert to Y-m-d format
                    $formattedEndDateYmd = $lessonEndDate ? Carbon::parse($lessonEndDate)->timezone(Helper::getTimeZone())->format('Y-m-d') : null;
                    $formattedMinDateYmd = Carbon::parse($minDate)->timezone(Helper::getTimeZone())->format('Y-m-d');

                    $data = [
                        'lessonID' => $id,
                        'studentID' => $item['user_id'],
                        'title' => $lessonData->title ?? '',
                        'start_date' => $formattedStartDateYmd,
                        'end_date' => $formattedEndDateYmd,
                        'min_date' => $formattedMinDateYmd,
                    ];
                    $dataStr = json_encode($data);
                    $output .= "<button class='btn btn-purple btn-sm d-flex align-items-end' onclick='LMS.ShowLessonCompetent(JSON.stringify({$dataStr}))'>Mark Lesson Compentent</button>";
                }
            }
        }

        return $output;
    }

    /**
     * @param string $output
     */
    public static function topicAddons(mixed $item, $studentActive, int|string $id): string
    {
        $studentActive = true;
        $output = '';
        if (auth()->user()->can('mark complete') && $item['type'] === 'topic' && $item['status'] !== 'COMPLETED' && $studentActive) {
            $output .= '<div class="d-flex flex-row">
                                        <button class="btn btn-success btn-sm d-flex align-items-end" onclick="LMS.MarkTopicComplete(' . $id . ',' . $item['user_id'] . ')">Mark Topic Complete</button>
                                    </div>';
        }

        return $output;
    }

    /**
     * @param string $output
     */
    public static function quizAddons(mixed $item, $student, int|string $id): string
    {
        //        $hasChecklist = [];
        //        if( $item[ 'type' ] === 'quiz' ) {
        //            dump($item[ 'type' ]);
        //            $hasChecklist[$id] = (intval($item[ 'data' ][ 'has_checklist' ] ?? 0));
        //            dump(auth()->user()->can( 'upload checklist' ));
        //            dd( $item );
        //        }
        $output = '';
        //        $output .= '<div style="display: none;"> ' . $item[ "type" ] . ': ' . json_encode($item[ "data" ]) . ' => ' . ( intval( $item[ "data" ][ "has_checklist" ] ?? 0 ) === 1 ) . ' </div>';
        if (auth()->user()->can('upload checklist') && !empty($item['data']) && intval($item['data']['has_checklist'] ?? 0) === 1) {
            //            dd($item);
            $checklist = StudentLMSAttachables::forEvent('CHECKLIST')
                ->forAttachable(Quiz::class, $item['data']['id'])
                ->where('student_id', $student->id)->get();
            $checkListCount = count($checklist);

            //                        \Log::info('checklist event', $checklist);
            //                        dd($checklist);

            $notSatisfactory = 0;
            $output .= '<div class="col-12 mb-2 d-flex"><span class="fw-bold me-1" data-count="' . $checkListCount . '">Checklist(s):</span>';
            if (!empty($checklist)) {
                //                            \Log::info('checklist Competent');
                $output .= '<div class="col-5">';
                $count = 1;
                foreach ($checklist as $checkListItem) {
                    $status = $checkListItem->properties['status'] ?? 'N/A';
                    if ($status === 'NOT SATISFACTORY') {
                        $notSatisfactory++;
                    }
                    $color = ($status === 'SATISFACTORY' ? 'success' : (($status === 'NOT SATISFACTORY') ? 'danger' : 'dark'));
                    $output .= '<p class="fw-bold text-primary ms-2 me-2"><a href="' . Storage::url($checkListItem->properties['file']['destination']) . '" download="' . ($checkListItem->properties['file']['name'] ?? 'Obs_Checklist') . '">Obs Checklist #' . ($count)
                        . '</a> <span class="fw-normal text-' . $color . ' ms-2">' . $status . '</span><span class="fw-normal text-dark ms-2">' . self::parseDate($checkListItem->created_at)->timezone(Helper::getTimeZone())->format('j F, Y') . '</span></p>';
                    $count++;
                }
                $output .= '</div>';
            }
            if ($notSatisfactory === 3) {
                $output .= '<p class="fw-bold text-danger">FAILED</p>';
            }
            if (empty($checklist) || $checkListCount < 3) {
                //                            \Log::info('checklist Pending');
                $output .= '<div class="col-5">';
                //                $output .= '<input class="form-control me-2" type="text" maxlength="56" placeholder="Checklist Name<sup>*</sup> (required)" name="checklist_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '_name" id="checklist_' . $item[ 'data' ][ 'course_id' ] . '_' . $id . '_name" />';
                $output .= '<input class="form-control me-2" type="file" name="checklist_' . $item['data']['course_id'] . '_' . $id . '" id="checklist_' . $item['data']['course_id'] . '_' . $id . '"
                                        data-format="pdf|doc|docx|zip|jpg|jpeg|xls|xlsx|ppt|pptx|png" accept=".pdf,.doc,.docx,.zip,.jpg,.jpeg,.xls,.xlsx,.ppt,.pptx,.png" />';
                $output .= '<div class="d-flex mt-1"><label class="form-label fw-bold font-small-4 py-1 pe-1 ">Status:</label><select data-placeholder="Status" class="form-select me-2" id="checklist_' . $item['data']['course_id'] . '_' . $id . '_status" name="status"><option></option><option value="SATISFACTORY">SATISFACTORY</option><option value="NOT SATISFACTORY">NOT SATISFACTORY</option></select>';
                $output .= '<button class="btn btn-primary btn-sm" onclick="LMS.UploadChecklist(' . $item['data']['id'] . ', \'checklist_' . $item['data']['course_id'] . '_' . $id . '\', ' . $item['user_id'] . ')">Upload Obs Checklist</button>';
                $output .= '</div></div>';
            }
            $output .= '</div>';
        }

        return $output;
    }

    public static function lessonTitleAdditional(mixed $item, $student, mixed $activityService): string
    {
        $additional = '';
        $hidden = '<span style="display:none">';
        $lesson = Lesson::find($item['data']['id']);
        $enrolment = StudentCourseEnrolment::where('user_id', $student->id)->where('course_id', $item['data']['course_id'])->first();
        $course_start_date = $lesson->release_key === 'XDAYS' ? self::parseDate($enrolment?->getRawOriginal('course_start_at')) : null;
        //                        $additional .= "#" . $lesson->release_key.' '.$course_start_date;
        //                        dd($lesson, $lesson->isAllowed($course_start_date), $lesson->isSubmitted(), $lesson->isComplete());
        if ($lesson->release_key !== 'IMMEDIATE' && !$lesson->isAllowed($course_start_date)) {
            $additional .= "<i class='me-50' data-lucide='calendar'></i> Available On: " . $lesson->releasePlan($course_start_date);
        }

        $activity1 = null;
        $activityTime = null;
        if (!empty($item['start_date'])) {
            $activityTime = $item['start_date'];
            $hidden .= '$$$ start_date ' . $activityTime;
        } elseif (!empty($item['first_attempt'])) {
            $first_attempt = $item['first_attempt']['quiz'];
            $quizAttempt = $item['first_attempt']['attempt'] ?? QuizAttempt::where('quiz_id', $first_attempt['data']['id'])->where('user_id', $student->id)->first()?->toArray();
            $activityTime = $quizAttempt['accessed_at'] ?? null;
            $hidden .= '$$$ first_attempt accessed_at ' . $activityTime;

            if (empty($activityTime)) {
                $activityS = $activityService->getActivityWhere(
                    [
                    'activity_event' => 'ASSESSMENT MARKED',
                    'actionable_type' => QuizAttempt::class,
                    'actionable_id' => $item['data']['id'],
                    'user_id' => $student->id,
                ]
                );
                $activityS = $activityS->sortByDesc('id')->first();
                if (!empty($activityS)) {
                    $activityTime = $activityS->activity_on;
                    $hidden .= '$$$ no activityTime assessment marked ' . $activityTime;
                } else {
                    $hidden .= '$$$ no activityTime submitted_at attempted_at ' . $activityTime;
                    $activityTime = $first_attempt['submitted_at'] ?? $first_attempt['attempted_at'] ?? null;
                }
            }
        }

        if (empty($activityTime)) {
            $activity1 = $activityService->getActivityWhere([
                'activity_event' => 'LESSON START',
                'actionable_type' => Lesson::class,
                'actionable_id' => $item['data']['id'],
                'user_id' => $student->id,
            ])->first();

            if (!empty($activity1)) {
                $activityTime = $activity1->activity_on;
                $hidden .= '$$$ no activityTime1 $activity1 ' . $activityTime;
            } else {
                $activityTime = $item['submitted_at'] ?? $item['attempted_at'] ?? null;
                $hidden .= '$$$ no activityTime1 submitted_at attempted_at ' . $activityTime;
            }
        }
        if (!empty($activityTime)) {
            $additional .= ' Start Date: ' . self::parseDate($activityTime)->timezone(Helper::getTimeZone())->format('j F, Y');
        }
        $activityTimeEnd = '';
        // Get End Date

        if ($lesson->isAllowed($course_start_date) || $lesson->isComplete()) {
            $lessonEndDate = StudentCourseService::lessonEndDate($student->id, $item['data']['id']);
            //            if($item[ 'data' ][ 'id' ] === 7 && $student->id === 46){
            //                Helper::debug( ['lesson' => $item[ 'data' ][ 'id' ], 'lessonEndDate' => $lessonEndDate ] );
            //            }
            if (!empty($lessonEndDate)) {
                $additional .= ' End Date: ' . $lessonEndDate;
            } else {
                //                $lastAttemptEndDate = StudentCourseService::lastAttemptEndDate( $student->id, $item[ 'data' ][ 'id' ] );
                //                if ( !empty( $lastAttemptEndDate ) ) {
                //                    $activityTimeEnd = $lastAttemptEndDate;
                //                }
                //                if ( !empty( $item[ 'stats' ][ 'is_marked_complete' ] ) ) {
                //                    $lessonActivityDate = StudentCourseService::lessonActivityDate( $student->id, $item[ 'data' ][ 'id' ] );
                //                    $activityTimeEnd = $lessonActivityDate;
                //                }
                //
                //    //            if(auth()->user()->id === 1 && $item['data']['id'] === 100316){
                //    //                dd($lastAttemptEndDate, $lessonActivityDate, $activityTimeEnd, $item);
                //    //            }
                //
                //                if ( !empty( $item[ 'competency' ] ) && $item[ 'competency' ][ 'is_competent' ] ) {
                //                    $activityTimeEnd = $item[ 'competency' ][ 'competent_on' ];
                //                } elseif ( StudentCourseService::checklistComplete( $student->id, $item[ 'data' ][ 'id' ] ) ) {
                //                    $checklistTime = StudentCourseService::checklistEndDate( $student->id, $item[ 'data' ][ 'id' ] );
                //                    if ( !empty( $checklistTime ) ) {
                //                        $activityTimeEnd = self::parseDate( $checklistTime )->greaterThan( $activityTimeEnd ) ? $checklistTime : $activityTimeEnd;
                //                    }
                //                }
                //                if ( !empty( $activityTimeEnd ) ) {
                //                    $additional .= " End Date: " . self::parseDate( $activityTimeEnd )->timezone( Helper::getTimeZone() )->format( 'j F, Y' );
                //                }
            }
        }

        if (!empty($item['stats']['is_marked_complete'])) {
            $activityMarked = $activityService->getActivityWhere([
                'activity_event' => 'LESSON MARKED',
                'actionable_type' => Lesson::class,
                'actionable_id' => $item['data']['id'],
                'user_id' => $student->id,
            ])?->first();

            if ((empty($activityMarked)) && !empty($item['marked_at'])) {
                $activityMarked = self::parseDate($item['marked_at'])->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
            } elseif (!empty($activityMarked)) {
                $additional = 'Marked Completed on: ' . self::parseDate($activityMarked->activity_on)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
            }
        }
        $hidden .= '</span>';

        //        $additional .= $hidden;
        return $additional;
    }

    private static function isChecklistLinear(Lesson $lesson, User $student)
    {
        $result = '';
        $checklistStatus = false;
        foreach ($lesson->topics as $topic) {
            if ($topic->hasQuizzes()) {
                foreach ($topic->quizzes as $quiz) {
                    if ($quiz->hasChecklist()) {
                        $checklists = $quiz->attachedChecklists();
                    }
                }
            }
        }

        return !$checklistStatus ? 'MISSING CHECKLIST' : '';
    }

    public static function updateQuizPassedCourseProgress($user_id, array $identifiers, $passed_at_date)
    {
        $courseProgress = CourseProgress::where('user_id', $user_id)
            ->where('course_id', $identifiers['course_id'])
            ->first();

        if (!$courseProgress) {
            return;
        }

        $details = json_decode($courseProgress->details, true);

        // Extract identifiers
        $lessonId = $identifiers['lesson_id'];
        $topicId = $identifiers['topic_id'];
        $quizId = $identifiers['quiz_id'];
        //        Helper::debug([$details['lessons']['list'][$lessonId]['topics']['list'][$topicId]['quizzes']['list'][$quizId]['passed_at'] , $passed_at_date, self::parseDate($details['lessons']['list'][$lessonId]['topics']['list'][$topicId]['quizzes']['list'][$quizId]['passed_at'])->notEqualTo($passed_at_date)]);
        // Safely access the quiz using keys
        if (
            isset($details['lessons']['list'][$lessonId]['topics']['list'][$topicId]['quizzes']['list'][$quizId]) &&
            self::parseDate($details['lessons']['list'][$lessonId]['topics']['list'][$topicId]['quizzes']['list'][$quizId]['passed_at'])?->notEqualTo($passed_at_date)
        ) {
            // Update the quiz directly
            $quiz = &$details['lessons']['list'][$lessonId]['topics']['list'][$topicId]['quizzes']['list'][$quizId];
            $quiz['passed_at'] = $passed_at_date;
            $quiz['passed'] = true;
            $quiz['submitted'] = true;
            $quiz['attempted'] = true;
            $quiz['submitted_at'] = $identifiers['attempt']['submitted_at'] ?? null;
            $quiz['attempted_at'] = $passed_at_date ?? null;
            //            Helper::debug($details,'dd');
            // Save back to DB
            $courseProgress->details = $details;

            //            Helper::debug($courseProgress->details,'dd','patrickb2');
            $courseProgress->save();
        }
    }

    public function addToProgress($course_id, $payload)
    {
        $courseProgresses = CourseProgress::where('course_id', $course_id)->get();

        if (!isset($payload['key']) || !isset($payload['id']) ||
            !isset($payload['parent_id']) || !isset($payload['data']) || empty($courseProgresses)) {
            return false;
        }

        $key = $payload['key'];
        $id = $payload['id'];
        $parentId = $payload['parent_id'];
        $data = $payload['data'];
        //        dump($payload, $course_id);

        foreach ($courseProgresses as $cp) {
            $courseProgress = CourseProgress::where('id', $cp->id)->first();
            $user_id = $courseProgress->user_id;
            $progress = $courseProgress->details ? $courseProgress->details->toArray() : [];

            if (strtolower($key) === 'lesson') {
                // You should not change lesson to different course if student is already enrolled

                //                if (isset($progress['lessons']['list'][$id])) {
                //                    return $this->changeCourse($user_id, $parentId, ['id' => $id, 'data' => $progress['lessons']['list'][$id]]);
                //                } elseif ($progress['course'] === $parentId) {
                $lastId = (count($progress['lessons']['list']) > 0)
                    ? array_key_last($progress['lessons']['list'])
                    : 0;
                $progress['lessons']['count']++;
                $progress['lessons']['list'][$id] = [
                    'completed' => false,
                    'at' => null,
                    'previous' => $lastId,
                    'topics' => [
                        'passed' => 0,
                        'count' => 0,
                        'list' => [],
                    ],
                    'data' => $data,
                ];
                //                }
            } elseif (strtolower($key) === 'topic') {
                foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {
                    if (intval($lesson_id) === intval($parentId)) {
                        $lastId = (count($progress['lessons']['list'][$lesson_id]['topics']['list']) > 0)
                            ? array_key_last($progress['lessons']['list'][$lesson_id]['topics']['list'])
                            : 0;
                        $progress['lessons']['list'][$lesson_id]['topics']['count']++;
                        $progress['lessons']['list'][$lesson_id]['topics']['list'][$id] = [
                            'completed' => false,
                            'at' => null,
                            'previous' => $lastId,
                            'quizzes' => [
                                'passed' => 0,
                                'count' => 0,
                                'submitted' => 0,
                                'list' => [],
                            ],
                            'data' => $data,
                        ];

                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['completed'] = FALSE;
                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['attempted '] = FALSE;
                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['marked_at'] = NULL;
                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['attempted_at'] = NULL;
                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['completed_at'] = NULL;
                        //                        $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['lesson_end_at'] = NULL;
                    }
                    //                    if (isset($lesson['topics']['list'][$id]) && $lesson_id !== $parentId) {
                    //                        $progress['lessons']['list'][$lesson_id]['topics']['count']--;
                    //                        $progress['lessons']['list'][$parentId]['topics']['count']++;
                    //                        $progress['lessons']['list'][$parentId]['topics']['list'][$id] = $lesson['topics']['list'][$id];
                    //                        unset($progress['lessons']['list'][$lesson_id]['topics']['list'][$id]);
                    //                    }
                }
            } elseif (strtolower($key) === 'quiz' && !empty($progress['lessons'])) {
                foreach ($progress['lessons']['list'] as $lesson_id => $lesson) {
                    foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                        //                        dump(intval($topic_id), intval($parentId));
                        if (intval($topic_id) === intval($parentId)) {
                            $lastId = (count($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list']) > 0)
                                ? array_key_last($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'])
                                : 0;
                            //                            dd($lastId);
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count']++;
                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$id] = [
                                'passed' => false,
                                'failed' => false,
                                'submitted' => false,
                                'at' => null,
                                'marked_at' => null,
                                'passed_at' => null,
                                'failed_at' => null,
                                'submitted_at' => null,
                                'previous' => $lastId,
                                'attempted' => false,
                                'attempted_at' => null,
                                'data' => $data,
                            ];

                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ]['completed'] = FALSE;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ]['attempted '] = FALSE;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ]['marked_at'] = NULL;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ][ 'topics' ][ 'list' ][ $topic_id ]['attempted _at'] = NULL;
                            //
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['completed'] = FALSE;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['attempted '] = FALSE;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['marked_at'] = NULL;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['attempted_at'] = NULL;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['completed_at'] = NULL;
                            //                            $progress[ 'lessons' ][ 'list' ][ $lesson_id ]['lesson_end_at'] = NULL;
                        }
                        //                        if (isset($topic['quizzes']['list'][$id]) && $topic_id !== $parentId) {
                        //                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['count']--;
                        //                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$parentId]['quizzes']++;
                        //                            $progress['lessons']['list'][$lesson_id]['topics']['list'][$parentId]['quizzes']['list'][$id] = $topic['quizzes']['list'][$id];
                        //                            unset($progress['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$id]);
                        //                        }
                    }
                }
            }

            $courseProgress->percentage = self::getTotalCounts($user_id, $progress);
            $courseProgress->details = $progress;

            $courseProgress->save();

            self::updateAdminReportProgress($user_id, $course_id);
        }
    }

    public function changeCourse($user_id, $course_id, $lesson)
    {
        $courseProgress = self::getProgress($user_id, $course_id);
        if (empty($courseProgress)) {
            $newProgress = self::populateProgress($course_id);
            $newProgress['lessons']['count']++;
            $newProgress['lessons']['list'][$lesson['id']] = $lesson['data'];

            CourseProgress::create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'percentage' => self::getTotalCounts($user_id, $newProgress),
                'details' => $newProgress,
            ]);
        } else {
            $courseProgress['lessons']['count']++;
            $courseProgress['lessons']['list'][$lesson['id']] = $lesson['data'];
        }

        return self::updateAdminReportProgress($user_id, $course_id);
    }

    public function markProgress(int $user_id, int $course_id, QuizAttempt $attempt)
    {
        if (empty($user_id) || empty($course_id)) {
            return false;
        }
        //        dd($user_id, $course_id, count( $attempt->questions ), count( $attempt->submitted_answers ));
        if (count($attempt->questions) !== count($attempt->submitted_answers)) {
            return false;
        }
        $progress = [];
        $percentage = ['passed' => 0, 'total' => 0];
        $existingProgress = CourseProgress::where('user_id', $user_id)->where('course_id', $course_id)->first();
        //        dump($existingProgress);
        if (!empty($existingProgress)) {
            return $this->updateProgress($existingProgress, $attempt);
        }

        $progress = self::populateProgress($course_id);

        return $this->insertProgress($progress, $attempt);
    }

    protected function updateProgress(CourseProgress $progress, QuizAttempt $attempt)
    {
        //        $topicProgress = $progress->details['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes'];
        //        dd($attempt);
        $detailsArray = $progress->details ? $progress->details->toArray() : [];
        $progressDetails = $this->markAttempt($detailsArray, $attempt);

        $progress->percentage = self::getTotalCounts($attempt->user_id, $progressDetails);
        $progress->details = $progressDetails;

        $progress->save();
        $this->updateAdminReport($attempt);

        self::updateProgressSession($progress);

        return $progress;
    }

    protected function markAttempt($progress, QuizAttempt $attempt)
    {
        // 'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
        $marked = $attempt->system_result === 'MARKED';
        $passed = $attempt->status === 'SATISFACTORY';
        $failed = in_array($attempt->status, ['FAIL', 'RETURNED']);
        //        if (!$progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['passed']) {
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['passed'] = $passed;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['marked_at'] = $marked ? Carbon::now()->toDateTimeString() : null;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['passed_at'] = $passed ? Carbon::now()->toDateTimeString() : null;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['failed'] = $failed;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['failed_at'] = $failed ? Carbon::now()->toDateTimeString() : null;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['submitted'] = true;
        $progress['lessons']['list'][$attempt->lesson_id]['topics']['list'][$attempt->topic_id]['quizzes']['list'][$attempt->quiz_id]['at'] = time();

        self::updateOrCreateStudentActivity(
            $attempt,
            'ASSESSMENT MARKED',
            $attempt->user_id,
            [
                'activity_on' => $attempt->updated_at,
                'status' => $attempt->status,
                'student_id' => $attempt->user_id,
                'student' => $attempt->user_id,
                'accessor_id' => auth()->user()->id,
                'accessor_role' => auth()->user()->roleName(),
                'accessed_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        //        dd('ready to reEvaluate');
        return self::reEvaluateProgress($attempt->user_id, $progress);

        //        return self::refreshProgress($progress);
    }

    protected function updateAdminReport(QuizAttempt $attempt)
    {
        return self::updateAdminReportProgress($attempt->user_id, $attempt->course_id);
    }

    protected function insertProgress(array $progress, QuizAttempt $attempt)
    {
        $courseProgress = new CourseProgress();
        $courseProgress->user_id = $attempt->user_id;
        $courseProgress->course_id = $attempt->course_id;

        $progressDetails = $this->markAttempt($progress, $attempt);

        $courseProgress->percentage = self::getTotalCounts($attempt->user_id, $progressDetails);
        $courseProgress->details = $progressDetails;

        $courseProgress->save();

        $this->updateAdminReport($attempt);

        self::updateProgressSession($courseProgress);

        return $courseProgress;
    }

    /**
     * @return void
     */
    /**
     * Update progress when LLND quiz is completed satisfactorily.
     *
     * @param int $student_id
     * @param int $course_id
     * @param int $lesson_id
     * @param int $topic_id
     * @param int $quiz_id
     * @return void
     */
    public static function updateLLNDProgress($student_id, $course_id, $lesson_id, $topic_id, $quiz_id)
    {
        // Get the current progress
        $progress = self::getProgress($student_id, $course_id);

        if (!$progress) {
            return;
        }

        // Update the progress to reflect LLND completion
        $progressDetails = $progress->details ? $progress->details->toArray() : [];

        // Mark the topic as completed (always)
        if (isset($progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id])) {
            $progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['completed'] = true;
            $progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['marked_at'] = now()->toDateTimeString();
        }

        // Only mark lesson as completed if it has only 1 topic
        if (isset($progressDetails['lessons']['list'][$lesson_id])) {
            $topicCount = $progressDetails['lessons']['list'][$lesson_id]['topics']['count'] ?? 0;
            if ($topicCount === 1) {
                $progressDetails['lessons']['list'][$lesson_id]['completed'] = true;
                $progressDetails['lessons']['list'][$lesson_id]['marked_at'] = now()->toDateTimeString();
            }
        }

        // Mark the quiz as passed
        if (isset($progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id])) {
            $progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed'] = true;
            $progressDetails['lessons']['list'][$lesson_id]['topics']['list'][$topic_id]['quizzes']['list'][$quiz_id]['passed_at'] = now()->toDateTimeString();
        }

        // Update the progress counts
        $progressDetails = self::reEvaluateProgress($student_id, $progressDetails);

        // Save the updated progress
        $progress->details = $progressDetails;
        $progress->percentage = self::getTotalCounts($student_id, $progressDetails);
        $progress->save();

        // Update progress session to ensure changes are reflected
        self::updateProgressSession($progress);

        // Update admin report
        self::updateAdminReportProgress($student_id, $course_id);
    }

    public static function quizSatisfactoryEntry($student_id, $data)
    {
        $currentDateTime = Carbon::now();
        $attempt = QuizAttempt::firstOrCreate(
            ['user_id' => $student_id, 'quiz_id' => $data['quizId']],
            [
                'user_id' => $student_id,
                'course_id' => $data['course_id'],
                'lesson_id' => $data['lesson_id'],
                'topic_id' => $data['topic_id'],
                'quiz_id' => $data['quizId'],
                'questions' => [],
                'submitted_answers' => [],
                'attempt' => 1,
                'system_result' => 'MARKED',
                'status' => 'SATISFACTORY',
                'user_ip' => request()->ip(),
                'accessor_id' => null,
                'accessed_at' => null,
                'submitted_at' => null,
                'created_at' => $currentDateTime,
                'updated_at' => $currentDateTime,
            ]
        );

        if (!empty($attempt)) {
            self::updateOrCreateStudentActivity(
                $attempt,
                'ASSESSMENT MARKED',
                $attempt->user_id,
                [
                    'activity_on' => $attempt->getRawOriginal('updated_at'),
                    'student' => $attempt->user_id,
                    'status' => $attempt->status,
                    'accessor_id' => !empty($accessor) ? $accessor->id : null,
                    'accessor_role' => !empty($accessor) ? $accessor->roleName() : null,
                    'accessed_at' => !empty($attempt->created_at) ? $attempt->created_at : $currentDateTime,
                ]
            );
            event(new \App\Events\QuizAttemptStatusChanged($attempt));
        }
    }

    public static function updateStudentCourseStats(StudentCourseEnrolment $enrolment, $isMainCourse)
    {
        $courseStats = CourseProgressService::getCourseStats($enrolment->user_id, $enrolment->course_id);
        $courseStatus = CourseProgressService::getCourseStatus($enrolment);
        //        Helper::debug([$enrolment, $courseStats]);
        self::updateCourseProgress($enrolment, $courseStatus);

        $enrolment->load('progress');

        return self::processEnrolment($enrolment, $isMainCourse, $courseStats, $courseStatus);
    }

    public static function updateCourseProgress($enrolment, $courseStatus): void
    {
        $enrolment->load('adminReport');
        $adminReport = $enrolment->adminReport;
        //        Helper::debug(['adminReport'=> $adminReport]);
        if (empty($adminReport)) {
            return;
        }

        // Check if the record has been updated today
        //        if ( $adminReport->updated_at && \Carbon\self::parseDate( $adminReport->getRawOriginal( 'updated_at' ) )->isToday() ) {
        //            // Skip this record
        //            return;
        //        }
        //        Helper::debug($adminReport);
        // Parse necessary dates
        $startDate = Carbon::parse($enrolment->getRawOriginal('course_start_at'));
        $endDate = Carbon::parse($enrolment->getRawOriginal('course_ends_at'));
        $now = Carbon::now();

        // Calculate the expected progress
        $expected = self::calculateExpectedProgress($startDate, $endDate, $now);

        // Update student course progress
        $student_course_progress = $adminReport->student_course_progress;
        $student_course_progress['expected_course_progress'] = $expected;
        $adminReport->student_course_progress = $student_course_progress;

        // Update course status
        $adminReport->course_status = $courseStatus;
        //        Helper::debug([
        //            $courseStatus
        //        ]);
        // Update course progress
        if ($courseStatus === 'COMPLETED' && empty($adminReport->course_completed_at)) {
            $latestDate = self::getLatestCompletionDate($adminReport->student_course_progress['details'], $enrolment->user_id);
            //            Helper::debug($latestDate);
            $adminReport->course_completed_at = $latestDate;

            $enrolment->course_completed_at = $latestDate;
            $enrolment->save();
        } elseif (empty($enrolment->course_expiry)) {
            $calculatedCourseExpiry = self::setupExpiryDate($enrolment);
            //            \Log::debug('Course Expiry', ['course_expiry' => $calculatedCourseExpiry,
            //                'course_id' => $enrolment->course_id,
            //                'student_id' => $enrolment->user_id,
            //                'start_date' => $startDate,
            //                'end_date' => $endDate,
            //                'course_length' => $enrolment->course->course_length,
            //                'course_expiry_days' => $enrolment->course->course_expiry_days,
            //            ]);
        }
        //        Helper::debug(['ready to save' => $adminReport],'dd');
        $adminReport->updated_at = $now;
        // Save updated admin report
        $adminReport->save();
    }

    public static function setupExpiryDate(StudentCourseEnrolment $enrolment): string
    {
        // Setting Course Expiry Date
        $calculatedCourseExpiry = self::calculateCourseExpiry($enrolment);

        $updateExpiryDate = self::updateExpiryDate($enrolment, $calculatedCourseExpiry);
        //        \Log::debug( 'setupExpiryDate', [
        //            '$calculatedCourseExpiry' => $calculatedCourseExpiry,
        //            '$updateExpiryDate' => $updateExpiryDate
        //        ] );
        if ($updateExpiryDate) {
            return $calculatedCourseExpiry;
        }

        return '';
    }

    /**
     * Set the expiry date for the enrolment and admin report.
     *
     * @param StudentCourseEnrolment $enrolment The enrolment instance.
     * @param string $calculatedCourseExpiry The calculated course expiry date.
     * @return bool TRUE if successful, FALSE otherwise.
     */
    public static function updateExpiryDate(StudentCourseEnrolment $enrolment, $calculatedCourseExpiry): bool
    {
        $adminReport = AdminReport::where('student_id', $enrolment->user_id)
            ->where('course_id', $enrolment->course_id)
            ->first();
        //        Helper::debug([$enrolment->course_id, $enrolment->user_id, $adminReport], 'dd');

        //        \Log::debug('updateExpiryDate',[
        //            'course_id' => $enrolment->course_id,
        //            'student_id' => $enrolment->user_id,
        //            'course_expiry' => $calculatedCourseExpiry,
        //            'adminReport' => $adminReport?->toArray(),
        //        ]);

        if (empty($adminReport)) {
            return false;
        }

        $enrolment->course_expiry = $calculatedCourseExpiry;
        $enrolment->save();

        $adminReport->course_expiry = $calculatedCourseExpiry;
        $adminReport->save();

        return true;
    }

    /**
     * Calculate the course expiry date.
     *
     * @param StudentCourseEnrolment $enrolment The enrolment instance.
     * @return string The calculated expiry date as a string.
     */
    public static function calculateCourseExpiry(StudentCourseEnrolment $enrolment): string
    {
        // Parse necessary dates
        $startDate = Carbon::parse($enrolment->getRawOriginal('course_start_at'));
        $endDate = Carbon::parse($enrolment->getRawOriginal('course_ends_at'));
        //        Helper::debug([$startDate, $startDate->clone()->addDays( 2 )->toDateTimeString(), $endDate->diffInDays( $startDate ), $enrolment],'dd');
        if (empty($enrolment->course)) {
            return false;
        }

        // Get the course length from the enrolment's course
        $courseLength = $enrolment->course->course_length_days;

        // Calculate the difference in days between the start and end dates, or use the provided days
        $diff = $endDate?->diffInDays($startDate);
        $course_expiry_days = $enrolment->course->course_expiry_days;
        if (empty($course_expiry_days)) {
            $course_expiry_days = $courseLength * 2;
        }

        if ($courseLength === $diff) {
            $expDiff = $course_expiry_days;
        } else {
            $expDiff = $course_expiry_days + abs($courseLength - $diff);
        }
        //        \Log::debug( 'inside calculateCourseExpiry', [
        //            'user_id' => $enrolment->user_id,
        //            'course_id' => $enrolment->course_id,
        //            'course_length' => $courseLength,
        //            'diff' => $diff,
        //            'course_expiry_days' => $course_expiry_days,
        //            'expDiff' => $expDiff,
        //            'return' => $startDate->clone()->addDays( $expDiff )->toDateTimeString(),
        //        ] );

        // Return the calculated expiry date as a string
        return $startDate->clone()->addDays($expDiff)->toDateTimeString();
    }

    public static function getLatestCompletionDate($details, $user_id)
    {
        $latestDate = null;

        if (isset($details['lessons']['list'])) {
            //            Helper::debug( $details[ 'lessons' ][ 'list' ] );
            foreach ($details['lessons']['list'] as $lesson_id => $lesson) {
                $lessonDate = null;
                if (isset($lesson['completed_at']) && !empty($lesson['completed_at'])) {
                    $lessonDate = Carbon::createFromTimestamp($lesson['completed_at']);
                } elseif (isset($lesson['marked_at']) && !empty($lesson['marked_at'])) {
                    $lessonDate = Carbon::createFromTimestamp($lesson['marked_at']);
                }
                if ($lessonDate !== null && ($latestDate === null || $lessonDate->greaterThan($latestDate))) {
                    $latestDate = $lessonDate;
                }
                //                Helper::debug( $lessonDate );
                if (isset($lesson['topics']['list'])) {
                    //                    Helper::debug( $lesson[ 'topics' ][ 'list' ] );
                    foreach ($lesson['topics']['list'] as $topic_id => $topic) {
                        $topicDate = null;
                        if (isset($topic['completed_at']) && !empty($topic['completed_at'])) {
                            $topicDate = Carbon::createFromTimestamp($topic['completed_at']);
                        } elseif (isset($topic['marked_at']) && !empty($topic['marked_at'])) {
                            $topicDate = Carbon::createFromTimestamp($topic['marked_at']);
                        }
                        if ($topicDate !== null && ($latestDate === null || $topicDate->greaterThan($latestDate))) {
                            $latestDate = $topicDate;
                        }
                        //                        Helper::debug( $topicDate );

                        if (isset($topic['quizzes']['list'])) {
                            //                            Helper::debug( $topic[ 'quizzes' ][ 'list' ] );
                            foreach ($topic['quizzes']['list'] as $quiz_id => $quiz) {
                                $quizDate = null;
                                if (!empty($quiz['passed_at'])) {
                                    $passedAt = self::parseDate($quiz['passed_at']);
                                    if (!empty($quiz['attempted_at']) && !empty($passedAt) && $passedAt->greaterThanOrEqualTo(self::parseDate($quiz['attempted_at']))) {
                                        $quizDate = $passedAt;
                                    } else {
                                        $quizAttempt = QuizAttempt::with(['evaluation'])->where('quiz_id', $quiz_id)->where('user_id', $user_id)->first();
                                        //                                        Helper::debug( [ $quizAttempt, $quiz_id, $user_id ]);
                                        if (!empty($quizAttempt)) {
                                            if (empty($quizAttempt->accessor_id) || $quizAttempt->accessor_id === 0) {
                                                if (!empty($quizAttempt->evaluation)) {
                                                    $quizDate = self::parseDate($quizAttempt->evaluation->getRawOriginal('created_at'));
                                                    self::updateQuizPassedCourseProgress($user_id, ['course_id' => $details['course'], 'lesson_id' => $lesson_id, 'topic_id' => $topic_id, 'quiz_id' => $quiz_id, 'attempt' => $quizAttempt], $quizDate->toDateTimeString());

                                                    $quizAttempt->accessor_id = 0;
                                                    $quizAttempt->accessed_at = $quizDate;

                                                    $quizAttempt->save();
                                                }
                                            } else {
                                                $quizDate = self::parseDate($quizAttempt->getRawOriginal('accessed_at'));
                                            }
                                        }
                                    }
                                } elseif (isset($quiz['marked_at']) && !empty($quiz['marked_at'])) {
                                    $quizDate = self::parseDate($quiz['marked_at']);
                                }

                                if ($quizDate !== null && ($latestDate === null || $quizDate->greaterThan($latestDate))) {
                                    $latestDate = $quizDate;
                                }
                                //                                Helper::debug( $quizDate );
                            }
                        }
                    }
                }
            }
        }
        //        Helper::debug( $details, 'dd' );
        if (empty($latestDate) || $latestDate->lessThan('1-1-2000')) {
            return;
        }

        return $latestDate->timezone(Helper::getTimeZone()) ?? Carbon::now();
    }

    public static function calculateExpectedProgress(Carbon $startDate, Carbon $endDate, Carbon $now): float
    {
        if ($startDate->greaterThan($now)) {
            return 0; // Course hasn't started yet
        }

        if ($endDate->lessThanOrEqualTo($now)) {
            return 100; // Course has ended
        }

        $totalDays = $startDate?->diffInDays($endDate);
        $elapsedDays = $startDate?->diffInDays($now);

        if ($totalDays > 0 && $elapsedDays > 0) {
            $progress = ($elapsedDays / $totalDays) * 100;

            return min($progress, 100); // Cap progress at 100%
        }

        return 0; // Default case
    }

    public static function processEnrolment($enrolment, $isMainCourse, $courseStats, $courseStatus)
    {
        $enrolmentStats = [];
        $s1 = !empty($enrolment->course_id) ? intval($enrolment->course_id) : null;
        $s2 = !empty($enrolment->course) ? intval($enrolment->course->next_course) : null;

        // Pre-calculate and attach additional details
        $enrolmentStats['is_course_completed'] = false;
        $enrolmentStats['canIssueCert'] = false;
        $enrolmentStats['preCourse'] = null;
        $enrolmentStats['preCourseAssistance'] = 0;

        if ($isMainCourse) {
            $preCourse = DB::table('lessons')
                ->leftJoin('quizzes', 'lessons.id', '=', 'quizzes.lesson_id')
                ->leftJoin('quiz_attempts', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
                ->select(
                    'lessons.id as lesson_id',
                    'quizzes.id as quiz_id',
                    'quiz_attempts.id as attempt_id',
                    'quiz_attempts.assisted as assisted'
                )
                ->where('lessons.course_id', intval($enrolment->course_id)) // Lesson belongs to the course
                ->where('lessons.order', 0) // First lesson
                ->whereRaw('LOWER(lessons.title) LIKE ?', ['%study tips%']) // Title contains "Study Tips"
                ->where(function ($query) use ($enrolment) {
                    $query->whereNull('quiz_attempts.user_id') // Include lessons even if no attempts
                        ->orWhere('quiz_attempts.user_id', intval($enrolment->user_id)); // Only attempts by the user
                })
                ->orderBy('quiz_attempts.id', 'DESC') // Latest attempt
                ->first(); // Fetch a single result

            $enrolmentStats['preCourse'] = collect($preCourse)->toArray();

            // Fetch course stats and determine completion
            if (!empty($courseStats['course_status']) && ($courseStats['course_status'] === 'COMPLETE' || $courseStats['current_course_progress'] >= 100)) {
                $enrolmentStats['is_course_completed'] = (bool)$courseStats['is_course_completed'];
            }

            if ($isMainCourse && $s2 !== 0 && boolval($enrolment->allowed_to_next_course)) {
                // Check course completion for the next course
                $enrolmentStats['nextCourseStats'] = CourseProgressService::getCourseStats($enrolment->user_id, $s2);
                $enrolmentStats['is_course_completed'] = (bool)($courseStats['is_course_completed'] ?? false) && ($enrolmentStats['nextCourseStats']['is_course_completed'] ?? false) &&
                    ($courseStats['course_status'] === 'COMPLETE' || $courseStats['current_course_progress'] >= 100) &&
                    ($enrolmentStats['nextCourseStats']['course_status'] === 'COMPLETE' || $enrolmentStats['nextCourseStats']['current_course_progress'] >= 100);
            }

            // Set canIssueCert if both courses are completed and it's the main course
            $enrolmentStats['canIssueCert'] = $enrolmentStats['is_course_completed'];
        }
        if (empty($courseStatus)) {
            $courseStatus = CourseProgressService::getCourseStatus($enrolment);
        }

        $studentCourseStats = StudentCourseStats::updateOrCreate(
            [
                'user_id' => $enrolment->user_id,
                'course_id' => $s1,
            ], // Conditions to find the record
            [
                'next_course_id' => $s2 ?? 0,
                'pre_course_lesson_id' => !empty($enrolmentStats['preCourse']) ? $enrolmentStats['preCourse']['lesson_id'] : 0,
                'pre_course_attempt_id' => !empty($enrolmentStats['preCourse']['attempt_id']) ? $enrolmentStats['preCourse']['attempt_id'] : 0,
                'pre_course_assisted' => (bool)($enrolmentStats['preCourse'] && $enrolmentStats['preCourse']['assisted']),
                'is_full_course_completed' => (bool)$enrolmentStats['is_course_completed'],
                'can_issue_cert' => (bool)$enrolmentStats['canIssueCert'],
                'is_main_course' => (bool)$isMainCourse,
                'course_status' => $courseStatus,
                'course_stats' => $courseStats, // Store course stats
            ] // Fields to update or insert
        );

        $enrolment->is_main_course = (bool)$isMainCourse;
        $enrolment->student_course_stats_id = $studentCourseStats->id;

        //        \Log::info('Updating last_updated for enrolment ID: ' . $enrolment->id, ['method' => __METHOD__]);

        $enrolment->last_updated = Carbon::now();
        $enrolment->save();

        return $enrolment;
    }

    public static function updateCourseProgressV2(StudentCourseEnrolment $enrolment, string $courseStatus): void
    {
        if (!$adminReport = self::fetchAdminReportV2($enrolment)) {
            return;
        }

        [$start, $end, $now] = self::parseEnrolmentDatesV2($enrolment);
        $expected = self::calculateExpectedProgress($start, $end, $now);

        $adminReport->student_course_progress = array_merge(
            $adminReport->student_course_progress,
            ['expected_course_progress' => $expected]
        );
        $adminReport->course_status = $courseStatus;

        if ($courseStatus === 'COMPLETED' && !$adminReport->course_completed_at) {
            $completedAt = self::getLatestCompletionDate(
                $adminReport->student_course_progress['details'],
                $enrolment->user_id
            );
            $adminReport->course_completed_at = $completedAt;
            $enrolment->update(['course_completed_at' => $completedAt]);
            $adminReport->student_course_progress['course_expiry'] = self::setupExpiryDate($enrolment);
        }

        $adminReport->updated_at = $now;
        $adminReport->save();
    }

    /**
     * Format a date string with proper timezone handling.
     *
     * @param string|int|null $date The date to format
     * @param string $format The desired output format
     */
    public static function formatDate($date, $format = 'j F, Y'): string
    {
        $parsedDate = self::parseAndFormatDate($date, $format);

        return $parsedDate ? $parsedDate->format($format) : '';
    }

    /**
     * Parse a date string into a Carbon instance with proper error handling.
     *
     * @param string|int|null $date The date to parse
     */
    public static function parseDate($date): ?\Carbon\Carbon
    {
        if (empty($date) || $date === 'null') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            try {
                return DateHelper::parse($date);
            } catch (\Exception $e) {
                \Log::warning('Failed to parse date', [
                    'date' => $date,
                    'error' => $e->getMessage(),
                    'at line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return null;
            }
        }
    }

    /**
     * Parse and format a date in one step, similar to DateHelper::parse but with enhanced error handling.
     *
     * @param string|int|null $date The date to parse
     * @return \Carbon\Carbon|null Returns Carbon instance if successful, null if parsing fails
     */
    public static function parseAndFormatDate($date, $format = null): ?\Carbon\Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            $parsedDate = self::parseDate($date);
            if (!$parsedDate || !($parsedDate instanceof \Carbon\Carbon)) {
                return null;
            }

            $date = $parsedDate->timezone(Helper::getTimeZone());

            return $date;
            // return !empty($format) ? $date->format($format) : $date->toDateTimeString();
        } catch (\Exception $e) {
            \Log::warning('Failed to parse and format date', [
                'date' => $date,
                'error' => $e->getMessage(),
                'at line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
