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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->string('company_name');
            $table->string('email')->nullable();
            $table->string('mobile');
            $table->string('contact_person');
            $table->text('address');

            $table->boolean('is_active')->default(true);

            $table->index('institute_id');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('supplier_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('asset_category_id');

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['supplier_id', 'asset_category_id']);
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
