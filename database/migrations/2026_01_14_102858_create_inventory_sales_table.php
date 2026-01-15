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
        Schema::create('inventory_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            $table->enum('role', ['student', 'staff']);
            $table->unsignedBigInteger('user_id');

            $table->string('reference_no')->nullable();
            $table->date('date');

            $table->enum('payment_status', ['paid', 'unpaid']);
            $table->text('description')->nullable();

            $table->string('bill_copy')->nullable();

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['institute_id', 'role', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_sales');
    }
};
