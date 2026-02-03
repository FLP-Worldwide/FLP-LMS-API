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
        Schema::create('student_fee_refunds', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('institute_id');
        $table->unsignedBigInteger('student_id');

        $table->unsignedBigInteger('student_fee_installment_id')->nullable();
        $table->unsignedBigInteger('payment_id')->nullable();

        $table->decimal('refund_amount', 10, 2);
        $table->date('refund_date');

        $table->string('payment_mode'); // CASH / UPI / BANK_TRANSFER / CHEQUE
        $table->string('reference_no')->nullable();

        $table->string('reason'); // DUPLICATE_PAYMENT / ADJUSTMENT / etc
        $table->text('remarks')->nullable();

        // receipt + notifications
        $table->boolean('download_receipt')->default(false);

        $table->boolean('notify_email_parents')->default(false);
        $table->boolean('notify_email_students')->default(false);
        $table->boolean('notify_sms_parents')->default(false);
        $table->boolean('notify_sms_students')->default(false);

        $table->timestamps();
        $table->softDeletes();

        $table->index(['student_id']);
        $table->index(['payment_id']);
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_refunds');
    }
};
