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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            // Core
            $table->string('topic');                 // assignment title
            $table->text('description')->nullable();

            // Status
            $table->enum('status', ['draft', 'published'])->default('draft');

            // Schedule
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('due_at')->nullable();

            // Flags
            $table->boolean('allow_late_submission')->default(false);
            $table->boolean('evaluation_required')->default(false);

            // Mapping
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('teacher_id');

            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->unsignedBigInteger('sub_topic_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
