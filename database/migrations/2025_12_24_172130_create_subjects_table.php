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
        Schema::create('subjects', function (Blueprint $table) {
           $table->id();

            // 🔗 Institute
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // 🔗 Class relation
            $table->foreignId('class_id')
                ->constrained('class_rooms')
                ->restrictOnDelete();

            // 📘 Subject info
            $table->string('name');                 // Mathematics
            $table->string('short_code')->nullable(); // MATH
            $table->enum('type', ['Scholastic', 'Co-Scholastic']);
            $table->boolean('is_active')->default(true);

            // ✅ Prevent duplicate subject per class
            $table->unique(
                ['institute_id', 'class_id', 'name'],
                'subjects_unique_per_class'
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
        Schema::dropIfExists('subjects');
    }
};
