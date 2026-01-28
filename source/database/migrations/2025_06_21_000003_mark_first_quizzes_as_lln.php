<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MarkFirstQuizzesAsLln extends Migration
{
    public function up()
    {
        // Update first quiz (by MIN(id)) of first topic of first lesson of each main course (excluding those with 'semester 2' in the title)
        DB::statement("
           UPDATE quizzes
           JOIN (
               SELECT MIN(q.id) as quiz_id
               FROM courses c
               JOIN lessons l ON l.course_id = c.id AND l.`order` = 0
               JOIN topics t ON t.lesson_id = l.id AND t.`order` = 0
               JOIN quizzes q ON q.topic_id = t.id
               WHERE c.id != 11111 AND c.id != 11112
                 AND LOWER(c.title) NOT LIKE '%semester 2%'
               GROUP BY c.id
           ) as first_quizzes ON quizzes.id = first_quizzes.quiz_id
           SET quizzes.is_lln = true
       ");
    }

    public function down()
    {
        DB::table('quizzes')->where('is_lln', true)->update(['is_lln' => false]);
    }
}
