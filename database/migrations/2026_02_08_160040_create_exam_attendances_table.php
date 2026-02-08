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
        Schema::create('exam_attendances', function (Blueprint $table) {
            $table->id();

             $table->unsignedBigInteger('institute_id');

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            /**
             * 0 = Not Marked
             * 1 = Present
             * 2 = Absent
             * 3 = Leave
             */

            $table->tinyInteger('attendance')->default(0);

            $table->unique(['exam_id', 'student_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
    }
};
