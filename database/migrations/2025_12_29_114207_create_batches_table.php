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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id'); // auto by trait
            $table->unsignedBigInteger('course_id');    // courses.id

            $table->string('name');                    // Batch Name
            $table->string('batch_uid');                    // Batch Name
            $table->string('academic_year');           // 2025-26
            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['institute_id', 'course_id', 'name'],
                'batch_unique_per_course'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
