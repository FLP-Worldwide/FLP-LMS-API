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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tuid')->unique();
            // Multi-tenant
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            // Core
            $table->string('employee_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();

            // School / Coaching
            $table->string('designation')->nullable(); // Teacher, Faculty, Mentor
            $table->string('department')->nullable();  // Maths, Science, etc.

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('joining_date')->nullable();

            $table->index(['institute_id', 'department']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
