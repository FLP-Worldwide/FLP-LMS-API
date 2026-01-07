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
        Schema::create('supplier_asset_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('asset_item_id');

            $table->unique(['supplier_id', 'asset_item_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_asset_items');
    }
};
