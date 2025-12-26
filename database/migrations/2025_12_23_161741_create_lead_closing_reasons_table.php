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
        Schema::create('lead_closing_reasons', function (Blueprint $table) {
             $table->id();

            // 🔗 Institute relation
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete(); // SoftDelete-safe

            // 📌 Reason info
            $table->string('name');               // Fees too high
            $table->string('slug')->nullable();   // fees-too-high
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // ✅ Unique per institute (soft delete aware)
            $table->unique(
                ['institute_id', 'name', 'deleted_at'],
                'lead_closing_reason_unique_active'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_closing_reasons');
    }
};
