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

            $table->unsignedBigInteger('class_id')->after('student_id');

            // Prevent duplicate attendance for same student same date
            $table->unique(['student_id', 'attendance_date'], 'unique_student_attendance');

        });
    }

    public function down()
    {
        Schema::table('student_attendances', function (Blueprint $table) {

            $table->dropUnique('unique_student_attendance');
            $table->dropColumn('class_id');

        });
    }

};
