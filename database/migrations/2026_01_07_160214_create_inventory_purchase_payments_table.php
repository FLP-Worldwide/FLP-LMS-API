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
        Schema::create('inventory_purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_purchase_id')
                  ->constrained('inventory_purchases')
                  ->cascadeOnDelete();

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);

            $table->enum('payment_mode', [
                'cash', 'upi', 'bank', 'cheque', 'card'
            ]);

            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['institute_id', 'inventory_purchase_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_purchase_payments');
    }
};
