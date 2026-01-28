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
        Schema::create('cron_job_cursors', function (Blueprint $table) {
            $table->id();
            $table->string('cronjob')->unique();
            $table->unsignedBigInteger('last_processed_id')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->float('total_duration_seconds')->default(0);
            $table->timestamps();
        });

        Schema::create('cron_job_failures', function (Blueprint $table) {
            $table->id();
            $table->string('cronjob');
            $table->unsignedBigInteger('job_identifier');
            $table->text('error_message');
            $table->unsignedInteger('retry_attempts')->default(0);
            $table->timestamp('failed_at');
            $table->timestamps();

            $table->index('cronjob');
            $table->index('job_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_job_cursors');
        Schema::dropIfExists('cron_job_failures');
    }
};
