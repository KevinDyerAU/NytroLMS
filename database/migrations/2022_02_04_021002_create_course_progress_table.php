<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->string('percentage');
            $table->longText('details');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                $table->foreign('course_id')
                    ->references('id')
                    ->on('courses');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_progress');
    }
}
