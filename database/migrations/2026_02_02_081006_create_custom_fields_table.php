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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['textbox', 'textarea', 'dropdown', 'checkbox', 'radio', 'date'])->default('textbox');
            $table->text('description')->nullable();

            $table->enum('show_on_student', ['Y', 'N'])->default('Y');
            $table->enum('is_required', ['Y', 'N'])->default('N');
            $table->enum('is_searchable', ['Y', 'N'])->default('N');
            $table->enum('is_external', ['Y', 'N'])->default('N');

            $table->integer('sequence')->nullable();
            $table->integer('max_length')->nullable();

            $table->string('default_value')->nullable();
            $table->json('prefilled_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institute_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
