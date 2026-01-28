<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVersionColumnToStudentCourseEnrolmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('status');
            $table->json('version_log')->nullable()->after('status');
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
            $table->dropColumn('version');
            $table->dropColumn('version_log');
        });
    }
}
