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
        Schema::create('class_routine_exceptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('class_routine_id');

            $table->date('exception_date');

            $table->enum('type', ['cancelled', 'rescheduled']);

            // for reschedule
            $table->time('new_start_time')->nullable();
            $table->time('new_end_time')->nullable();
            $table->date('new_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['class_routine_id', 'exception_date'], 'unique_exception');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_routine_exceptions');
    }
};
