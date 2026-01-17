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
        Schema::create('student_fee_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_fee_id')
                ->constrained('student_fees')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('student_fee_payments')
                ->nullOnDelete();

            // DEBIT = Fees assigned
            // CREDIT = Payment / adjustment
            $table->enum('type', ['DEBIT', 'CREDIT']);

            $table->decimal('amount', 10, 2);

            $table->string('description')->nullable();


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_ledgers');
    }
};
