<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_routines', function (Blueprint $table) {

            $table->unsignedBigInteger('course_id')->after('institute_id');
            $table->unsignedBigInteger('batch_id')->after('course_id');

            $table->date('base_date')->nullable()->after('section');

            $table->string('topic')->nullable()->after('subject_id');
            $table->text('description')->nullable()->after('topic');

            $table->string('class_type')->default('Regular')->after('room_id');

            $table->enum('repeat_type', [
                'Does Not Repeat',
                'Weekly',
                'Select Dates'
            ])->default('Does Not Repeat')->after('class_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
