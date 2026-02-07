<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Topic;
use App\Services\LlnCompletionService;
use Illuminate\Database\Seeder;

class LlnContentSeeder extends Seeder
{
    public function run()
    {
        // Get LLN and PTR IDs from config
        $types = [
            'lln' => [
                'course_id' => config('lln.course_id'),
                'lesson_id' => config('lln.lesson_id'),
                'topic_id' => config('lln.topic_id'),
                'quiz_id' => config('lln.quiz_id'),
                'course_title' => 'Special LLN assessment course',
                'lesson_title' => 'Language, Literacy and Numeracy Lesson',
                'topic_title' => 'Language, Literacy and Numeracy Topic',
                'quiz_title' => 'Language, Literacy, Numeracy and Digital (LLND) Activity',
                'course_slug' => 'lln-assessment-course',
                'lesson_slug' => 'lln-assessment-lesson',
                'topic_slug' => 'lln-assessment-topic',
                'quiz_slug' => 'lln-assessment',
            ],
            'ptr' => [
                'course_id' => config('ptr.course_id'),
                'lesson_id' => config('ptr.lesson_id'),
                'topic_id' => config('ptr.topic_id'),
                'quiz_id' => config('ptr.quiz_id'),
                'course_title' => 'Special PTR assessment course',
                'lesson_title' => 'Pre-Training Review Lesson',
                'topic_title' => 'Pre-Training Review Topic',
                'quiz_title' => 'Pre-Training Review Activity',
                'course_slug' => 'ptr-assessment-course',
                'lesson_slug' => 'ptr-assessment-lesson',
                'topic_slug' => 'ptr-assessment-topic',
                'quiz_slug' => 'ptr-assessment',
            ],
        ];

        foreach ($types as $type => $data) {
            // Create Course
            $course = Course::create([
                'id' => $data['course_id'],
                'title' => $data['course_title'],
                'slug' => $data['course_slug'],
                'course_length_days' => 90,
                'visibility' => 'PUBLIC',
                'status' => 'PUBLISHED',
            ]);

            // Create Lesson
            $lesson = Lesson::create([
                'id' => $data['lesson_id'],
                'course_id' => $course->id,
                'title' => $data['lesson_title'],
                'slug' => $data['lesson_slug'],
                'order' => 1,
            ]);

            // Create Topic
            $topic = Topic::create([
                'id' => $data['topic_id'],
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'title' => $data['topic_title'],
                'slug' => $data['topic_slug'],
                'order' => 1,
            ]);

            // Create Quiz
            Quiz::create([
                'id' => $data['quiz_id'],
                'topic_id' => $topic->id,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'title' => $data['quiz_title'],
                'slug' => $data['quiz_slug'],
                'passing_percentage' => 0,
                'allowed_attempts' => 999,
                'is_lln' => $type === 'lln',
                'order' => 1,
            ]);
        }

        // Use the service to bulk update all enrolments
        app(LlnCompletionService::class)->bulkUpdateAll();
    }
}
