<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseColToStudentCourseEnrolments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->boolean('is_semester_2')->default(0)->after('is_main_course');
            $table->unsignedInteger('registered_by')->nullable()->after('cert_details');
            $table->date('registration_date')->nullable()->after('cert_details');
            $table->boolean('is_chargeable')->default(false)->after('cert_details');
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
            $table->dropColumn('is_semester_2');
            $table->dropColumn('is_chargeable');
            $table->dropColumn('registration_date');
            $table->dropColumn('registered_by');
        });
    }
}
