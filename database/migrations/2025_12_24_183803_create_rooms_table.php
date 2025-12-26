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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // 🏫 Room info
            $table->string('name');                 // Room 101, Physics Lab
            $table->string('code')->nullable();     // R101, PHY-LAB
            $table->integer('capacity')->nullable();
            $table->string('number')->nullable();
            $table->string('floor')->nullable();
            $table->boolean('is_active')->default(true);


            // ✅ Unique room per institute
            $table->unique(
                ['institute_id', 'name'],
                'rooms_unique_per_institute'
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
        Schema::dropIfExists('rooms');
    }
};
