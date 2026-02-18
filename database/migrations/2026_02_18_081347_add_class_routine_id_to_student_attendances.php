<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('student_attendances', function (Blueprint $table) {

            $table->unsignedBigInteger('class_routine_id')
                ->after('student_id');

            $table->foreign('class_routine_id')
                ->references('id')
                ->on('class_routines')
                ->onDelete('cascade');

            // optional but recommended
            $table->index(['class_routine_id', 'attendance_date']);
        });
    }

    public function down()
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropForeign(['class_routine_id']);
            $table->dropColumn('class_routine_id');
        });
    }

};
