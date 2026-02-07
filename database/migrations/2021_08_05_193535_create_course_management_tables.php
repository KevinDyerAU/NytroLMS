<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseManagementTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('title');
            $table->enum('course_type', ['FREE', 'PAID'])->default('PAID');
            $table->integer('course_length_days')->default(0);
            $table->integer('next_course_after_days')->default(0);
            $table->unsignedBigInteger('next_course')->nullable();
            $table->boolean('auto_register_next_course')->default(false);
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->boolean('has_topic')->default(true);
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('course_id')
                    ->references('id')
                    ->on('courses')
                    ->onDelete('cascade');
            }
        });
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->double('estimated_time')->nullable();
            $table->boolean('has_quiz')->default(true);
            $table->unsignedBigInteger('lesson_id');
            $table->timestamps();
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('lesson_id')
                    ->references('id')
                    ->on('lessons')
                    ->onDelete('cascade');
            }
        });
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable();
            $table->string('slug');
            $table->string('title');
            $table->integer('passing_percentage');
            $table->double('estimated_time')->nullable();
            $table->tinyInteger('allowed_attempts')->nullable();
            $table->unsignedBigInteger('topic_id');
            $table->timestamps();
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('topic_id')
                    ->references('id')
                    ->on('topics')
                    ->onDelete('cascade');
            }
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable();
            $table->boolean('required')->default(true);
            $table->string('slug');
            $table->string('title');
            $table->longText('content');
            $table->string('answer_type');
            $table->longText('options')->nullable();
            $table->double('estimated_time')->nullable();
            $table->text('correct_answer')->nullable();
            $table->unsignedBigInteger('quiz_id');
            $table->timestamps();
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('quiz_id')
                    ->references('id')
                    ->on('quizzes')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
}
