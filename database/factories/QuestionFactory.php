<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
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
            'required' => 1,
            'title' => $title,
            'slug' => \Str::slug($title),
            'content' => $this->faker->paragraph,
            'answer_type' => 'ESSAY',
            'quiz_id' => 1,
        ];
    }
}
