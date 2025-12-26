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
        Schema::create('class_routines', function (Blueprint $table) {
            $table->id();
            // Institute
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // Relations
            $table->foreignId('class_id')->constrained('class_rooms')->restrictOnDelete();
            $table->string('section')->nullable();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            // $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();

            // Time
            $table->time('start_time');
            $table->time('end_time');

            // Room
            $table->string('room_id');

            $table->boolean('is_active')->default(true);



            // Prevent duplicate slot
            $table->unique([
                'institute_id',
                'class_id',

                'start_time',
                'end_time'
            ], 'unique_class_time_slot');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_routines');
    }
};
