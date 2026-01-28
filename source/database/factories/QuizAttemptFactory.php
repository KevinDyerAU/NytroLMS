<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'course_id' => \App\Models\Course::factory(),
            'lesson_id' => Lesson::factory(),
            'topic_id' => \App\Models\Topic::factory(),
            'quiz_id' => Quiz::factory(),
            'questions' => json_encode([]),
            'submitted_answers' => json_encode([]),
            'attempt' => $this->faker->numberBetween(1, 5),
            'system_result' => $this->faker->randomElement(['INPROGRESS', 'COMPLETED', 'EVALUATED', 'MARKED']),
            'status' => $this->faker->randomElement(['ATTEMPTING', 'SUBMITTED', 'REVIEWING', 'RETURNED', 'SATISFACTORY', 'FAIL', 'OVERDUE']),
            'user_ip' => $this->faker->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-10 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-10 days', 'now'),
            'submitted_at' => $this->faker->optional()->dateTimeBetween('-10 days', 'now'),
        ];
    }

    /**
     * Indicate that the quiz attempt is satisfactory.
     */
    public function satisfactory(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_result' => 'MARKED',
            'status' => 'SATISFACTORY',
        ]);
    }

    /**
     * Indicate that the quiz attempt is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_result' => 'COMPLETED',
            'status' => 'SUBMITTED',
        ]);
    }

    /**
     * Indicate that the quiz attempt is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_result' => 'INPROGRESS',
            'status' => 'ATTEMPTING',
        ]);
    }

    /**
     * Indicate that the quiz attempt is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_result' => 'MARKED',
            'status' => 'FAIL',
        ]);
    }
}
