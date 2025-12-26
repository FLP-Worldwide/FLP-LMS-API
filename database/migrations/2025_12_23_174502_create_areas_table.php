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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();

            // 🔗 Institute (multi-tenant)
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // 🌍 Location Path (Single Row)
            $table->string('country')->default('India');
            $table->string('state');
            $table->string('city');
            $table->string('area');

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // ✅ Prevent duplicate area per institute
            $table->unique(
                ['institute_id', 'state', 'city', 'area', 'deleted_at'],
                'area_unique_active'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
