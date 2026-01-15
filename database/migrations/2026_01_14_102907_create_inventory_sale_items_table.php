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
        Schema::create('inventory_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();

            $table->enum('sale_type', ['free', 'paid']);

            $table->decimal('sale_price', 10, 2)->default(0);
            $table->integer('units');
            $table->decimal('tax_percentage', 5, 2)->default(0);

            $table->decimal('sub_total', 12, 2);


            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_sale_items');
    }
};
