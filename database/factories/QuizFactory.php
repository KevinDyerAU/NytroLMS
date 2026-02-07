<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
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
            'passing_percentage' => 0,
            'estimated_time' => 0,
            'allowed_attempts' => 3,
            'topic_id' => 1,
            'course_id' => 1,
            'lesson_id' => 1,
            'lb_content' => $this->faker->paragraphs(2, true),
            'is_lln' => false,
        ];
    }
}
