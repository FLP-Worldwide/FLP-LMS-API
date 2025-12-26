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
        Schema::create('referred_bies', function (Blueprint $table) {
            $table->id();
 // 🔗 Institute scope
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // 👤 Team member info
            $table->string('name');
            $table->string('phone', 15)->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // ✅ Prevent duplicate active entries
            $table->unique(
                ['institute_id', 'name', 'phone', 'deleted_at'],
                'referred_by_unique_active'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referred_bies');
    }
};
