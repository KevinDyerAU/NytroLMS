<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
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
            'has_topic' => 1,
            'course_id' => 1,
        ];
    }
}
