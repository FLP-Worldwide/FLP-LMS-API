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
        Schema::create('batch_subjects', function (Blueprint $table) {
            $table->id();

           $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('subject_id'); // subjects.id

            $table->unsignedBigInteger('teacher_id');       // main teacher
            $table->unsignedBigInteger('extra_teacher_id')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['batch_id', 'subject_id'],
                'batch_subject_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_subjects');
    }
};
