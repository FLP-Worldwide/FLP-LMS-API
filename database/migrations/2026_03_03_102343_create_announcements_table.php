<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('category')->nullable();
            $table->longText('description')->nullable();

            $table->enum('status', ['DRAFT','PUBLISHED'])->default('DRAFT');

            $table->boolean('schedule_for_later')->default(false);
            $table->timestamp('scheduled_at')->nullable();

            $table->string('attachment')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
