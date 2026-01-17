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
        Schema::create('student_fee_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->enum('payment_mode', [
                'CASH',
                'UPI',
                'BANK_TRANSFER',
                'CHEQUE'
            ]);

            $table->decimal('amount', 10, 2);
            $table->date('payment_date');

            // payment meta
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->string('country')->nullable();
            $table->text('remarks')->nullable();

            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING');

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_payments');
    }
};
