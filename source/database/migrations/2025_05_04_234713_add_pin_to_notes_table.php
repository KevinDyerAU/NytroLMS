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
        Schema::table('notes', function (Blueprint $table) {
            $table->json('pin_log')->nullable()->after('note_body');
            $table->boolean('is_pinned')->default(false)->after('note_body');

            $table->index(['subject_type', 'subject_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('pin_log');
            $table->dropColumn('is_pinned');
        });
    }
};
