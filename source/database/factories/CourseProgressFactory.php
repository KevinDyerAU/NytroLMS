<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseProgress>
 */
class CourseProgressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CourseProgress::class;

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
            'percentage' => [
                'passed' => $this->faker->numberBetween(0, 100),
                'failed' => $this->faker->numberBetween(0, 10),
                'processed' => $this->faker->numberBetween(0, 100),
                'submitted' => $this->faker->numberBetween(0, 50),
                'total' => $this->faker->numberBetween(50, 150),
                'quizzes_passed' => $this->faker->numberBetween(0, 50),
                'quizzes_failed' => $this->faker->numberBetween(0, 10),
                'course_completed' => false,
                'empty' => $this->faker->numberBetween(0, 20),
            ],
            'details' => [
                'course' => [
                    'id' => $this->faker->numberBetween(1, 100),
                    'title' => $this->faker->sentence(),
                    'is_main_course' => $this->faker->boolean(),
                ],
                'lessons' => [
                    'count' => $this->faker->numberBetween(1, 10),
                    'passed' => $this->faker->numberBetween(0, 5),
                    'submitted' => $this->faker->numberBetween(0, 5),
                    'list' => [],
                ],
                'completed' => false,
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the course is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => [
                'passed' => 100,
                'failed' => 0,
                'processed' => 100,
                'submitted' => 50,
                'total' => 100,
                'quizzes_passed' => 50,
                'quizzes_failed' => 0,
                'course_completed' => true,
                'empty' => 0,
            ],
            'details' => [
                'course' => [
                    'id' => $this->faker->numberBetween(1, 100),
                    'title' => $this->faker->sentence(),
                    'is_main_course' => true,
                ],
                'lessons' => [
                    'count' => 5,
                    'passed' => 5,
                    'submitted' => 5,
                    'list' => [],
                ],
                'completed' => true,
            ],
        ]);
    }

    /**
     * Indicate that the progress has LLND completion.
     */
    public function withLLND(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => [
                'passed' => $this->faker->numberBetween(55, 60),
                'failed' => 0,
                'processed' => $this->faker->numberBetween(87, 90),
                'submitted' => $this->faker->numberBetween(30, 35),
                'total' => $this->faker->numberBetween(83, 85),
                'quizzes_passed' => $this->faker->numberBetween(32, 35),
                'quizzes_failed' => 0,
                'course_completed' => false,
                'empty' => $this->faker->numberBetween(20, 25),
            ],
            'details' => [
                'course' => [
                    'id' => $this->faker->numberBetween(1, 100),
                    'title' => $this->faker->sentence(),
                    'is_main_course' => true,
                ],
                'lessons' => [
                    'count' => 5,
                    'passed' => 1,
                    'submitted' => 1,
                    'list' => [
                        1 => [
                            'data' => ['order' => 0],
                            'marked_at' => now()->toDateTimeString(),
                            'completed' => true,
                            'topics' => [
                                'list' => [
                                    1 => [
                                        'data' => ['order' => 0],
                                        'quizzes' => [
                                            'list' => [
                                                1 => ['passed' => true],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'completed' => false,
            ],
        ]);
    }
}
