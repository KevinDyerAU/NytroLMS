<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentCourseStatsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'next_course_id' => null,
            'pre_course_lesson_id' => null,
            'pre_course_attempt_id' => null,
            'pre_course_assisted' => false,
            'is_full_course_completed' => false,
            'can_issue_cert' => false,
        ];
    }
}
