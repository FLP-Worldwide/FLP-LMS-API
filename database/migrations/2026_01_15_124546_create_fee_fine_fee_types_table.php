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
        Schema::create('fee_fine_fee_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fee_fine_id')
                ->constrained('fee_fines')
                ->cascadeOnDelete();

            $table->foreignId('fees_type_id')
                ->constrained('fees_types')
                ->cascadeOnDelete();

            $table->unique(['fee_fine_id', 'fees_type_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_fine_fee_types');
    }
};
