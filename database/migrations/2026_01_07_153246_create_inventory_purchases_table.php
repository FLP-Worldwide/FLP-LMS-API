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
        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('inventory_suppliers')->cascadeOnDelete();

            $table->date('date');
            $table->string('reference_no');
            $table->string('bill_copy');
            $table->text('description')->nullable();

            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['institute_id', 'supplier_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_purchases');
    }
};
