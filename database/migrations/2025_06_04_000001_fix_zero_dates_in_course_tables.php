<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixZeroDatesInCourseTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix student_course_enrolments table
        DB::statement("UPDATE student_course_enrolments SET course_completed_at = NULL WHERE course_completed_at = '0000-00-00 00:00:00' OR course_completed_at IS NOT NULL AND course_completed_at < '1971-01-01 00:00:00'");
        DB::statement("UPDATE student_course_enrolments SET course_expiry = NULL WHERE course_expiry = '0000-00-00' OR course_expiry IS NOT NULL AND course_expiry < '1971-01-01'");
        DB::statement("UPDATE student_course_enrolments SET last_updated = NULL WHERE last_updated = '0000-00-00' OR last_updated IS NOT NULL AND last_updated < '1971-01-01'");

        // Fix admin_reports table
        DB::statement("UPDATE admin_reports SET course_completed_at = NULL WHERE course_completed_at = '0000-00-00 00:00:00' OR course_completed_at IS NOT NULL AND course_completed_at < '1971-01-01 00:00:00'");
        DB::statement("UPDATE admin_reports SET course_expiry = NULL WHERE course_expiry = '0000-00-00 00:00:00' OR course_expiry IS NOT NULL AND course_expiry < '1971-01-01 00:00:00'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No need for reverse migration since we're fixing data
    }
}
