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
        Schema::create('enquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')
                ->constrained('enquiries')
                ->cascadeOnDelete();

            $table->string('follow_up_type')->nullable(); // Call, Visit
            $table->date('followup_date')->nullable();
            $table->string('followup_time')->nullable(); // HH:MM
            $table->text('comment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_follow_ups');
    }
};
