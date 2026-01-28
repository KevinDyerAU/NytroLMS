<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentCourseStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_course_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('next_course_id')->nullable();
            $table->unsignedBigInteger('pre_course_lesson_id')->nullable();
            $table->unsignedBigInteger('pre_course_attempt_id')->nullable();
            $table->boolean('pre_course_assisted')->default(false);
            $table->boolean('is_full_course_completed')->default(false);
            $table->boolean('can_issue_cert')->default(false);
            $table->timestamps();
        });

        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->date('last_updated')->nullable()->after('course_id');
            $table->unsignedBigInteger('student_course_stats_id')->nullable()->after('course_id');
            $table->boolean('is_main_course')->default(false)->after('course_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_course_stats');
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->dropColumn('is_main_course');
            $table->dropColumn('last_updated');
            $table->dropColumn('student_course_stats_id');
        });
    }
}
