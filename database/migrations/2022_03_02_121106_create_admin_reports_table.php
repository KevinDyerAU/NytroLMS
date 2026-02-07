<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('trainer_id')->nullable();
            $table->unsignedBigInteger('leader_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->longText('student_details');
            $table->string('student_status');
            $table->dateTime('student_last_active')->nullable();
            $table->date('student_course_start_date')->nullable();
            $table->date('student_course_end_date')->nullable();
            $table->string('course_status')->nullable();
            $table->longText('course_details')->nullable();
            $table->longText('student_course_progress')->nullable();
            $table->longText('trainer_details');
            $table->longText('leader_details');
            $table->dateTime('leader_last_active')->nullable();
            $table->longText('company_details')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('trainer_id')
                ->references('id')
                ->on('users');
            $table->foreign('leader_id')
                ->references('id')
                ->on('users');
            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
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
        Schema::dropIfExists('admin_reports');
    }
}
