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
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
// 🔗 Institute (auto-filled by BelongsToInstitute)
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // 📘 Class info
            $table->string('name');               // Class 1, Nursery, KG
            $table->string('class_code')->unique(); // CLS-ABC123
            $table->date('created_on');           // business date

            $table->boolean('is_active')->default(true);

            // Prevent duplicate class name per institute
            $table->unique(
                ['institute_id', 'name'],
                'class_rooms_unique_per_institute'
            );
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
