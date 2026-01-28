<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainingPlanIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });

        Schema::table('student_activities', function (Blueprint $table) {
            $table->index(['user_id', 'actionable_type', 'actionable_id']);
            $table->index(['user_id', 'activity_event']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'quiz_id', 'created_at']);
        });

        Schema::table('student_lms_attachables', function (Blueprint $table) {
            $table->index(['student_id', 'event', 'attachable_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['course_id', 'order']);
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->index(['lesson_id', 'order']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->index(['topic_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropIndexIfExists('student_course_enrolments', 'student_course_enrolments_user_id_status_index');

        // Handle student_activities table with its foreign key constraint
        Schema::table('student_activities', function (Blueprint $table) {
            $indexes = Schema::getIndexes('student_activities');

            if (isset($indexes['student_activities_user_id_actionable_type_actionable_id_index'])) {
                $table->dropIndex('student_activities_user_id_actionable_type_actionable_id_index');
            }

            if (isset($indexes['student_activities_user_id_activity_event_index'])) {
                // Drop foreign key if it exists
                $foreignKeys = Schema::getForeignKeys('student_activities');
                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey['columns'] === ['user_id']) {
                        $table->dropForeign(['user_id']);

                        break;
                    }
                }
                $table->dropIndex('student_activities_user_id_activity_event_index');
            }
        });

        $this->dropIndexIfExists('quiz_attempts', 'quiz_attempts_user_id_quiz_id_created_at_index');
        $this->dropIndexIfExists('student_lms_attachables', 'student_lms_attachables_student_id_event_attachable_id_index');
        $this->dropIndexIfExists('lessons', 'lessons_course_id_order_index');
        $this->dropIndexIfExists('topics', 'topics_lesson_id_order_index');
        $this->dropIndexIfExists('quizzes', 'quizzes_topic_id_order_index');
    }

    /**
     * Drop an index if it exists using Laravel's native schema methods.
     *
     * @param string $tableName
     * @param string $indexName
     * @return void
     */
    private function dropIndexIfExists($tableName, $indexName)
    {
        $indexes = Schema::getIndexes($tableName);

        if (isset($indexes[$indexName])) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
}
