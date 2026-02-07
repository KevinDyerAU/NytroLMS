<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseCompletedAtToAdminReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_reports', function (Blueprint $table) {
            $table->dateTime('course_expiry')->nullable()->after('course_status');
            $table->dateTime('course_completed_at')->nullable()->after('course_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_reports', function (Blueprint $table) {
            $table->dropColumn('course_expiry');
            $table->dropColumn('course_completed_at');
        });
    }
}
