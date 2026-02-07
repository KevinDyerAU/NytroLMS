<?php

namespace Database\Seeders;

use Database\Factories\CourseFactory;
use Database\Factories\LessonFactory;
use Database\Factories\QuestionFactory;
use Database\Factories\QuizFactory;
use Database\Factories\TopicFactory;
use Illuminate\Database\Seeder;

class LMSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        (new CourseFactory())->count(3)->create()->each(function ($course) {
            (new LessonFactory())->count(3)->create(['course_id' => $course->id])->each(function ($lesson) {
                (new TopicFactory())->count(\Arr::random([0, 1, 2]))->create(['lesson_id' => $lesson->id])->each(function ($topic) {
                    (new QuizFactory())->count(\Arr::random([0, 1, 2]))->create(['topic_id' => $topic->id])->each(function ($quiz) {
                        (new QuestionFactory())->count(2)->create(['quiz_id' => $quiz->id]);
                    });
                });
            });
        });

        // Pre-course assessment
        // $theID = config('constants.precourse_quiz_id', 99999);
        // (new CourseFactory())->create(['id' => $theID]);

        // (new LessonFactory())->create(['id' => $theID, 'course_id' => $theID]);

        // (new TopicFactory())->create(['id' => $theID, 'lesson_id' => $theID, 'course_id' => $theID]);

        // (new QuizFactory())->create(['id' => $theID, 'topic_id' => $theID, 'lesson_id' => $theID, 'course_id' => $theID]);

        // (new QuestionFactory())->create(['id' => $theID, 'quiz_id' => $theID]);
    }
}
