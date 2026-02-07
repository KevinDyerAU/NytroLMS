<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentCourseEnrolment>
 */
class StudentCourseEnrolmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentCourseEnrolment::class;

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
            'status' => 'ACTIVE',
            'registration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'registered_by' => User::factory(),
            'registered_on_create' => $this->faker->boolean(),
            'is_chargeable' => $this->faker->boolean(),
            'deferred_details' => null,
            'cert_details' => null,
            'course_completed_at' => null,
            'course_expiry' => $this->faker->dateTimeBetween('now', '+2 years'),
            'last_updated' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'student_course_stats_id' => null,
            'course_progress_id' => null,
            'admin_reports_id' => null,
            'has_lln_completed' => $this->faker->boolean(),
            'version' => $this->faker->numberBetween(1, 5),
        ];
    }

    /**
     * Indicate that the enrolment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Indicate that the enrolment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'COMPLETED',
            'course_completed_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the enrolment is delisted.
     */
    public function delisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'DELIST',
        ]);
    }

    /**
     * Indicate that LLND is completed.
     */
    public function llnCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_lln_completed' => true,
        ]);
    }
}
