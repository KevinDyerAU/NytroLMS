<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignupLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signup_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('leader_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('creator_id');
            $table->uuid('key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies');
            $table->foreign('course_id')
                ->references('id')
                ->on('courses');
            $table->foreign('leader_id')
                ->references('id')
                ->on('users');
            $table->foreign('creator_id')
                ->references('id')
                ->on('users');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->boolean('signup_through_link')->nullable()->after('user_id');
            $table->boolean('signup_links_id')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('signup_links');

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn('signup_through_link');
            $table->dropColumn('signup_links_id');
        });
    }
}
