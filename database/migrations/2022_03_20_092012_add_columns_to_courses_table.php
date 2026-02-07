<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('visibility', ['PUBLIC', 'PRIVATE', 'PASSWORD PROTECT'])->default('PUBLIC')->after('auto_register_next_course');
            $table->enum('status', ['PUBLISHED', 'PENDING REVIEW', 'DRAFT'])->default('DRAFT')->after('visibility');
            $table->integer('revisions')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('visibility');
            $table->dropColumn('status');
            $table->dropColumn('revisions');
        });
    }
}
