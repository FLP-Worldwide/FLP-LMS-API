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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();

                // 🔐 Institute
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

                // 🔗 Payer (Student / Person / Entity)
                $table->foreignId('payer_id')->constrained('payers');

                // 💳 Finance Account (cash / bank / upi)
                $table->foreignId('finance_account_id')->constrained('finance_accounts');

                // 📅 Payment
                $table->date('payment_date');
                $table->enum('payment_mode', ['cash', 'bank', 'upi', 'cheque']);

                // Optional refs
                $table->string('transaction_id')->nullable();
                $table->string('cheque_no')->nullable();

                $table->decimal('total_amount', 12, 2)->default(0);

                $table->text('remark')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
