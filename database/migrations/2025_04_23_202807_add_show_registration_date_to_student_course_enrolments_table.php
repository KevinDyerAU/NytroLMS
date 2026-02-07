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
            $table->boolean('show_registration_date')->default(0)->after('registered_on_create');
            $table->boolean('show_on_widget')->default(1)->after('registered_on_create');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_course_enrolments', function (Blueprint $table) {
            $table->dropColumn('show_registration_date');
            $table->dropColumn('show_on_widget');
        });
    }
};
