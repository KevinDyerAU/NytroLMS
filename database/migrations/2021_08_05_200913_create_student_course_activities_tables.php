<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentCourseActivitiesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('topic_id');
            $table->unsignedBigInteger('quiz_id');
            $table->longText('questions');
            $table->longText('submitted_answers');
            $table->integer('attempt');
            $table->enum('system_result', ['INPROGRESS', 'COMPLETED', 'EVALUATED', 'MARKED']);
            $table->enum('status', ['ATTEMPTING', 'SUBMITTED', 'REVIEWING', 'RETURNED', 'SATISFACTORY', 'FAIL', 'OVERDUE'])->default('SUBMITTED');
            //            $table->text('assessor_result');//use feedback table instead
            //            $table->text('assessor_feedback');
            //            $table->unsignedBigInteger('assessor_id');
            //            $table->timestamp('assessed_on');
            $table->ipAddress('user_ip');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('course_id')
                    ->references('id')
                    ->on('courses');

                $table->foreign('lesson_id')
                    ->references('id')
                    ->on('lessons');

                $table->foreign('topic_id')
                    ->references('id')
                    ->on('topics');

                $table->foreign('quiz_id')
                    ->references('id')
                    ->on('quizzes');
            }
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->morphs('attachable');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            //            $table->foreign('owner_id')
            //                ->references('id')
            //                ->on('users')
            //                ->onDelete('cascade');

            $table->index(['attachable_id', 'attachable_type'], 'feedbacks_attachable_id_attachable_type_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('course_progress');
        Schema::dropIfExists('feedbacks');
    }
}
