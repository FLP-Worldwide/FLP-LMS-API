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
        Schema::create('study_resources', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->unsignedBigInteger('parent_id')->nullable(); // folder id

            $table->enum('type', ['folder', 'file', 'link']);

            $table->string('name');

            // for file
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();

            // for link
            $table->string('url')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_resources');
    }
};
