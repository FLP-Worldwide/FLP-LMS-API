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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('student_id');
            $table->string('class');
            $table->string('section')->nullable();

            $table->date('attendance_date'); // actual class date
            $table->timestamp('marked_at')->nullable(); // when marked
            $table->unsignedBigInteger('marked_by')->nullable(); // teacher/admin

            $table->enum('status', ['P', 'A', 'L', 'H'])->default('P');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['attendance_date', 'class']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
