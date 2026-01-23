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
        Schema::create('study_materials', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->unsignedBigInteger('class_id');   // class_rooms.id
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('topic_id')->nullable();

            $table->string('type');                   // YouTube, Notes, etc
            $table->string('title')->nullable();

            $table->string('video_url')->nullable();  // for YouTube / Vimeo
            $table->string('file_path')->nullable();  // for uploads
            $table->string('file_name')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_materials');
    }
};
