<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssociatedColumnsToLmsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('lesson_id');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('topic_id');
            $table->unsignedBigInteger('lesson_id')->nullable()->after('topic_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('course_id');
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('course_id');
            $table->dropColumn('lesson_id');
        });
    }
}
