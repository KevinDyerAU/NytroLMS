<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateColumnsToStudentCourseEnrolmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->json('cert_details')->nullable()->after('deferred_details');
            $table->unsignedBigInteger('cert_issued_by')->nullable()->after('deferred_details');
            $table->dateTime('cert_issued_on')->nullable()->after('deferred_details');
            $table->boolean('cert_issued')->default(false)->after('deferred_details');
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
            $table->dropColumn('cert_issued');
            $table->dropColumn('cert_issued_on');
            $table->dropColumn('cert_issued_by');
            $table->dropColumn('cert_details');
        });
    }
}
