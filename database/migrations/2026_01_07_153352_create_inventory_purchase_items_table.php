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
        Schema::create('inventory_purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();

            $table->decimal('unit_price', 10, 2);
            $table->integer('units');
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
        Schema::dropIfExists('inventory_purchase_items');
    }
};
