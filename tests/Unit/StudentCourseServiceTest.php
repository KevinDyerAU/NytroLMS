<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\StudentCourseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StudentCourseServiceTest extends TestCase
{
    use RefreshDatabase;

    // Inline factory for QuizAttempt (for test only)
    protected function makeQuizAttempt($overrides = [])
    {
        return QuizAttempt::create(array_merge([
            'user_id' => $overrides['user_id'] ?? 1,
            'lesson_id' => $overrides['lesson_id'] ?? 1,
            'quiz_id' => $overrides['quiz_id'] ?? 1,
            'system_result' => $overrides['system_result'] ?? 'INPROGRESS',
            'status' => $overrides['status'] ?? 'ATTEMPTING',
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['updated_at'] ?? now(),
            'submitted_at' => $overrides['submitted_at'] ?? null,
        ], $overrides));
    }

    /** @test */
    public function it_returns_lesson_start_date_from_first_quiz_submission()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        // First quiz attempt (not submitted)
        $this->makeQuizAttempt([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'created_at' => Carbon::now()->subDays(2),
            'system_result' => 'INPROGRESS',
            'submitted_at' => null,
        ]);

        // Second quiz attempt (submitted)
        $submittedAt = Carbon::now()->subDay();
        $this->makeQuizAttempt([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'created_at' => Carbon::now()->subDay(),
            'system_result' => 'PASSED',
            'submitted_at' => $submittedAt,
        ]);

        $startDate = StudentCourseService::lessonStartDate($user->id, $lesson->id);
        $this->assertEquals($submittedAt->toDateTimeString(), $startDate);
    }

    /** @test */
    public function it_returns_lesson_start_date_from_first_attempt_updated_at_if_marked()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $createdAt = Carbon::now()->subDays(3);
        $updatedAt = $createdAt->copy()->addHour();

        // First quiz attempt (not submitted, MARKED)
        $this->makeQuizAttempt([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'system_result' => 'MARKED',
            'submitted_at' => null,
        ]);

        $startDate = StudentCourseService::lessonStartDate($user->id, $lesson->id);
        $this->assertEquals($updatedAt->toDateTimeString(), $startDate);
    }

    /** @test */
    public function it_returns_lesson_start_date_from_first_attempt_created_at_if_not_marked()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $createdAt = Carbon::now()->subDays(4);

        // First quiz attempt (not submitted, not MARKED)
        $this->makeQuizAttempt([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'created_at' => $createdAt,
            'system_result' => 'INPROGRESS',
            'submitted_at' => null,
        ]);

        $startDate = StudentCourseService::lessonStartDate($user->id, $lesson->id);
        $this->assertEquals($createdAt->toDateTimeString(), $startDate);
    }

    /** @test */
    public function it_returns_null_if_no_quiz_attempts_or_activity()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $startDate = StudentCourseService::lessonStartDate($user->id, $lesson->id);
        $this->assertNull($startDate);
    }

    protected function tearDown(): void
    {
        // No need to call Mockery::close() since we are not mocking
        parent::tearDown();
    }

    // You can add similar tests for lessonEndDate, mocking completion logic as needed.
}
