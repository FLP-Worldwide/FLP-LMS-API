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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('supplier_id');

            $table->string('invoice_no')->nullable();
            $table->date('purchase_date');
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->date('service_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('unit')->nullable();
            $table->string('purchased_by')->nullable();
            $table->string('file_path')->nullable();


            $table->text('remarks')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
