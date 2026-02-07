<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('calling_codes')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('code')->nullable();
            $table->text('languages')->nullable();
            $table->string('currency')->nullable();
            $table->string('flag')->nullable();
        });
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('timezones');
    }
}
