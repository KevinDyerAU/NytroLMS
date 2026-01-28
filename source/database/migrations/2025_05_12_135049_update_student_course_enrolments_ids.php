<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Update course_progress_id
        DB::statement('
            UPDATE student_course_enrolments sce
            JOIN course_progress cp
                ON cp.user_id = sce.user_id
                AND cp.course_id = sce.course_id
            SET sce.course_progress_id = cp.id
            WHERE sce.course_progress_id IS NULL
        ');

        // Update admin_reports_id
        DB::statement('
            UPDATE student_course_enrolments sce
            JOIN admin_reports ar
                ON ar.student_id = sce.user_id
                AND ar.course_id = sce.course_id
            SET sce.admin_reports_id = ar.id
            WHERE sce.admin_reports_id IS NULL
        ');

        // Update student_course_stats_id
        DB::statement('
            UPDATE student_course_enrolments sce
            JOIN student_course_stats scs
                ON scs.user_id = sce.user_id
                AND scs.course_id = sce.course_id
            SET sce.student_course_stats_id = scs.id
            WHERE sce.student_course_stats_id IS NULL
        ');

        // Update is_main_course
        DB::statement('
            UPDATE courses
            SET is_main_course = 1
            WHERE LOWER(title) NOT LIKE \'%semester 2%\'
        ');

        // Update student_course_enrolments.is_main_course
        DB::statement('
            UPDATE student_course_enrolments sce
            JOIN courses c ON c.id = sce.course_id
            SET sce.is_main_course = 1
            WHERE LOWER(c.title) NOT LIKE \'%semester 2%\'
        ');
    }

    public function down(): void
    {
        DB::table('student_course_enrolments')
            ->update([
                'course_progress_id' => null,
                'admin_reports_id' => null,
                'student_course_stats_id' => null,
            ]);
    }
};
