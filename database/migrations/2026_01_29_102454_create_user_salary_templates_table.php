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
        Schema::create('user_salary_templates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('salary_template_id')
                ->constrained('salary_templates')
                ->cascadeOnDelete();

            $table->enum('salary_type', ['monthly', 'hourly']);

            $table->boolean('is_active')->default(true);


            // 🔒 One active template per user
            $table->unique(['user_id', 'salary_template_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_salary_templates');
    }
};
