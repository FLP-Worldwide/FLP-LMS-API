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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            // 🔗 Institute
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->restrictOnDelete();

            // ================= BASIC DETAILS =================
           $table->string('enquiry_code')->unique();
            $table->string('student_name');
            $table->string('phone', 20);

            $table->foreignId('lead_source_type_id')->nullable();
            $table->foreignId('referred_by_id')->nullable();

            $table->date('enquiry_date')->nullable();
            $table->string('status')->default('new'); // new, open, closed
            $table->string('lead_temperature')->nullable(); // hot, warm, cold

            $table->foreignId('lead_closing_reason_id')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
