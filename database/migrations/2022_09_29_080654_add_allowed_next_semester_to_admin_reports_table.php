<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowedNextSemesterToAdminReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_reports', function (Blueprint $table) {
            $table->string('allowed_to_next_course')->default(1)->after('student_course_end_date');
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
            $table->dropColumn('allowed_to_next_course');
        });
    }
}
