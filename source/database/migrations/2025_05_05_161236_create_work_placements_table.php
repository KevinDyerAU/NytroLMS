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
        Schema::create('work_placements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('leader_id')->nullable();
            $table->date('course_start_date')->nullable();
            $table->date('course_end_date')->nullable();
            $table->boolean('consultation_completed')->nullable();
            $table->date('consultation_completed_on')->nullable();
            $table->date('wp_commencement_date')->nullable();
            $table->date('wp_end_date')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_email')->nullable();
            $table->string('employer_phone')->nullable();
            $table->text('employer_address')->nullable();
            $table->text('employer_notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->json('field_changes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_placements');
    }
};
