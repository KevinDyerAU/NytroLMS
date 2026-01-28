<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeferredToStudentCourseEnrolmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->json('deferred_details')->nullable()->after('status');
            $table->boolean('deferred')->nullable()->after('status');
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
            $table->dropColumn('deferred');
            $table->dropColumn('deferred_details');
        });
    }
}
