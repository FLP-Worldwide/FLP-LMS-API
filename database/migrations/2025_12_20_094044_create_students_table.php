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
        Schema::create('students', function (Blueprint $table) {
           $table->id();
            $table->uuid('stuid')->unique();
            // Multi-tenant
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            // Core identity
            $table->string('admission_no')->nullable();
            $table->string('roll_no')->nullable();

            $table->string('first_name');
            $table->string('last_name')->nullable();

            // Common for school + coaching
            $table->string('class')->nullable();     // Class / Course
            $table->string('section')->nullable();   // Section / Batch

            // Status
            $table->enum('status', ['active', 'inactive', 'passed', 'left'])
                  ->default('active');

            $table->date('admission_date')->nullable();

            $table->index(['institute_id', 'class', 'section']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
