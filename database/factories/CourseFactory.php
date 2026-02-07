<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->sentence();

        return [
            'title' => $title,
            'slug' => \Str::slug($title),
            'course_type' => 'PAID',
            'course_length_days' => 90,
            'next_course_after_days' => 0,
            'next_course' => 0,
            'auto_register_next_course' => 0,
            'visibility' => 'PUBLIC',
            'status' => 'PUBLISHED',
            'revisions' => 0,
            'is_main_course' => true,
            'category' => 'accredited',
        ];
    }

    /**
     * Indicate that the course is non-accredited (excluded from PTR).
     */
    public function nonAccredited(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'non_accredited',
        ]);
    }

    /**
     * Indicate that the course is accredited (requires PTR).
     */
    public function accredited(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'accredited',
        ]);
    }
}
