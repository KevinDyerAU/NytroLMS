<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameStudentColumnToNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->renameColumn('student_id', 'subject_id');
            $table->string('subject_type')->after('note_body')->default("App\\\Models\\\User");
            $table->json('data')->after('subject_id')->nullable();
        });
    }

    /* HINT: To drop foreign key i used following
     * ALTER TABLE notes DROP FOREIGN KEY notes_student_id_foreign;
     * ALTER TABLE notes DROP INDEX notes_student_id_foreign;
     */

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('subject_type');
            $table->dropColumn('data');
            $table->renameColumn('subject_id', 'student_id');
        });
    }
}
