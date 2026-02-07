<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLockCourseToStudentCourseEnrolments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->boolean('is_locked')->default(0)->after('is_main_course');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
}
