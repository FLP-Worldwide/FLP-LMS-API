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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();

           $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('class_id');   // class_rooms.id
            $table->unsignedBigInteger('subject_id'); // subjects.id

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('duration')->default(0);
            $table->integer('priority')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('class_rooms')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();

            $table->unique(
                ['institute_id', 'class_id', 'subject_id', 'name'],
                'topic_unique_per_class_subject'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
