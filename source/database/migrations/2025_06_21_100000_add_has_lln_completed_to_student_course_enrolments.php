<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasLlnCompletedToStudentCourseEnrolments extends Migration
{
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->boolean('has_lln_completed')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->dropColumn('has_lln_completed');
        });
    }
}
