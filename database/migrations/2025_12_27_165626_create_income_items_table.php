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
        Schema::create('income_items', function (Blueprint $table) {
            $table->id();


            $table->foreignId('income_id')->constrained()->cascadeOnDelete();

            // Category from finance_categories (Income type)
            $table->string('category');

            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 12, 2);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_items');
    }
};
