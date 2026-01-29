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
        Schema::create('salary_templates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->string('name');
            $table->enum('type', ['monthly', 'hourly']);

            $table->json('salary');        // basic, hourly_rate, rate_per_hour
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->json('summary')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_templates');
    }
};
