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
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();

             // 🔐 Institute
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            // 🔗 Polymorphic relation
            $table->morphs('accountable');
            // accountable_id
            // accountable_type

            // 💳 Account meta
            $table->enum('account_type', ['cash', 'bank', 'upi', 'cheque']);

            $table->string('account_name')->nullable();

            // 🏦 Bank
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc')->nullable();

            // 📱 UPI
            $table->string('upi_id')->nullable();

            $table->boolean('is_active')->default(true);

            $table->index(['institute_id', 'account_type']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};
