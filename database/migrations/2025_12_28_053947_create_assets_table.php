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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            $table->foreignId('asset_location_id')->constrained();
            $table->foreignId('asset_category_id')->constrained();

            $table->string('name');
            $table->string('code')->nullable();
            $table->integer('quantity')->default(1);

            $table->enum('condition', ['new', 'good', 'damaged', 'repair'])->default('good');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['institute_id', 'code','asset_category_id','deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
