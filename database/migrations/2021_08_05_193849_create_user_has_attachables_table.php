<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHasAttachablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_has_attachables', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->morphs('attachable');
            $table->index(['attachable_id', 'attachable_type'], 'user_has_attachables_attachable_id_attachable_type_index');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->primary(
                ['user_id', 'attachable_id', 'attachable_type'],
                'user_has_attachables_user_attachable_type_primary'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_has_attachables');
    }
}
