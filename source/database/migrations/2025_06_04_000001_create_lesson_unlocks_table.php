<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonUnlocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lesson_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('unlocked_by')->nullable();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->foreign('lesson_id', 'lesson_unlocks_lesson_id_foreign')
                ->references('id')
                ->on('lessons')
                ->onDelete('cascade');

            $table->foreign('course_id', 'lesson_unlocks_course_id_foreign')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('user_id', 'lesson_unlocks_user_id_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('unlocked_by', 'lesson_unlocks_unlocked_by_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Make sure a lesson can only be unlocked once per user per course
            $table->unique(['lesson_id', 'course_id', 'user_id'], 'lesson_unlocks_lesson_course_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lesson_unlocks', function (Blueprint $table) {
            $table->dropForeign('lesson_unlocks_lesson_id_foreign');
            $table->dropForeign('lesson_unlocks_course_id_foreign');
            $table->dropForeign('lesson_unlocks_user_id_foreign');
            $table->dropForeign('lesson_unlocks_unlocked_by_foreign');
        });

        Schema::dropIfExists('lesson_unlocks');
    }
}
