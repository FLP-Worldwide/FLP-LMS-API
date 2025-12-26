<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_source_types', function (Blueprint $table) {
            $table->id();

            // 🔗 Institute relation
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete(); // ✅ IMPORTANT

            // 📌 Lead source info
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // 🚀 Unique only for active records
            $table->unique(['institute_id', 'name', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_source_types');
    }
};
