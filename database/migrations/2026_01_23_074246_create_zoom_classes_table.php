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
        Schema::create('zoom_classes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->string('topic');
            $table->date('date');
            $table->time('from_time');
            $table->time('to_time');

            $table->json('settings')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('zoom_class_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users');
        });

        Schema::create('zoom_class_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained();
        });

        Schema::create('zoom_class_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained();
        });

        Schema::create('zoom_class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zoom_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_class_teacher');
        Schema::dropIfExists('zoom_class_course');
        Schema::dropIfExists('zoom_class_batch');
        Schema::dropIfExists('zoom_class_student');
        Schema::dropIfExists('zoom_classes');
    }
};
