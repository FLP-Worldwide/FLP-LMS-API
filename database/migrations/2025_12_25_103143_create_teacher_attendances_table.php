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
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();

            $table->date('attendance_date');

            $table->enum('status', ['P', 'A', 'LP', 'HP', 'L']);

            // 🔒 One attendance per teacher per day
            $table->unique(['teacher_id', 'attendance_date']);
            $table->index(['institute_id', 'attendance_date']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
