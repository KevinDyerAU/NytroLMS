<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class FixLMSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            foreach ($course->lessons as $lesson) {
                foreach ($lesson->topics as $topic) {
                    $topic->lesson_id = $lesson->id;
                    $topic->course_id = $course->id;
                    $topic->save();
                    foreach ($topic->quizzes as $quiz) {
                        $quiz->topic_id = $topic->id;
                        $quiz->lesson_id = $lesson->id;
                        $quiz->course_id = $course->id;
                        $quiz->save();
                    }
                }
            }
        }
    }
}
