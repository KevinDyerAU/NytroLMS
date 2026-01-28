<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('language');
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('timezone');
            $table->dateTime('last_logged_in')->nullable();
            $table->dateTime('onboard_at')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'ALWAYS_ACTIVE', 'ONBOARDED', 'ENROLLED', 'CREATED'])->default('CREATED');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_details');
    }
}
