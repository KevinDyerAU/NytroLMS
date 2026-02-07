<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseCompletedAtToStudentCourseEnrolments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->date('course_expiry')->nullable()->after('status');
            $table->dateTime('course_completed_at')->nullable()->after('status');
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
            $table->dropColumn('course_expiry');
            $table->dropColumn('course_completed_at');
        });
    }
}
