<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnrolmentTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'enrolments',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('enrolment_key');
                $table->longText('enrolment_value');
                $table->timestamps();

                if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                }
            }
        );

        Schema::create(
            'student_documents',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('file_name');
                $table->bigInteger('file_size');
                $table->string('file_path');
                $table->uuid('file_uuid');
                $table->timestamps();

                if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                }
            }
        );

        Schema::create(
            'student_course_enrolments',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('course_id');
                $table->boolean('allowed_to_next_course')->default(false);
                $table->timestamp('course_start_at')->nullable();
                $table->timestamp('course_ends_at')->nullable();
                $table->enum('status', ['DELIST', 'ENROLLED', 'COMPLETED'])->default('ENROLLED');
                $table->timestamps();
                if (env('APP_DOMAIN', 'localhost') !== 'localhost') {
                    $table->foreign('course_id')
                        ->references('id')
                        ->on('courses');
                    $table->foreign('user_id')
                        ->references('id')
                        ->on('users');
                }
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_course_enrolments');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('enrolments');
    }
}
