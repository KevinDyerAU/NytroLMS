<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->text('results');
            $table->morphs('evaluable');
            $table->enum('status', ['SATISFACTORY', 'UNSATISFACTORY'])->nullable();
            $table->text('email_sent_on')->nullable();
            $table->unsignedBigInteger('evaluator_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamps();

            //            $table->foreign('evaluator_id')
            //                ->references('id')
            //                ->on('users')
            //                ->onDelete('cascade');

            $table->foreign('student_id')
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
        Schema::dropIfExists('evaluations');
    }
}
