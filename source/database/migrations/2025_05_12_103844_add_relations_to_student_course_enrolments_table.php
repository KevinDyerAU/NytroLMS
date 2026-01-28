<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->unsignedBigInteger('course_progress_id')->nullable()->after('student_course_stats_id');
            $table->unsignedBigInteger('admin_reports_id')->nullable()->after('student_course_stats_id');
            $table->foreign('course_progress_id')
                ->references('id')
                ->on('course_progress')
                ->onDelete('set null');
            $table->foreign('admin_reports_id')
                ->references('id')
                ->on('admin_reports')
                ->onDelete('set null');

            $table->index('course_progress_id', 'idx_student_course_enrolments_course_progress_id');
            $table->index('admin_reports_id', 'idx_student_course_enrolments_admin_reports_id');
            $table->index('student_course_stats_id', 'idx_student_course_enrolments_student_course_stats_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->dropForeign(['course_progress_id']);
            $table->dropForeign(['admin_reports_id']);
            $table->dropColumn('course_progress_id');
            $table->dropColumn('admin_reports_id');
        });
    }
};
