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
        Schema::create('class_routine_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_routine_id')
                ->constrained('class_routines')
                ->cascadeOnDelete();

            // Monday, Tuesday, etc.
            $table->string('day');



            $table->unique(['class_routine_id', 'day']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_routine_days');
    }
};
