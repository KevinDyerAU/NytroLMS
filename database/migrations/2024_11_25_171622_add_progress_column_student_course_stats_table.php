<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProgressColumnStudentCourseStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_course_stats', function (Blueprint $table) {
            $table->string('course_status')->after('course_id')->nullable();
            $table->json('course_stats')->after('course_id')->nullable();
            $table->boolean('is_main_course')->after('course_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_course_stats', function (Blueprint $table) {
            $table->dropColumn('course_status');
            $table->dropColumn('course_stats');
            $table->dropColumn('is_main_course');
        });
    }
}
