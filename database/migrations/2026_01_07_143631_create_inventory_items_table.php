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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_category_id')
                ->constrained('inventory_categories')
                ->cascadeOnDelete();

            $table->string('item_name');
            $table->decimal('buying_price', 10, 2);
            $table->decimal('sale_price', 10, 2);

            $table->decimal('tax_percentage', 5, 2);
            $table->integer('low_stock_indicator');

            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institute_id', 'inventory_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
