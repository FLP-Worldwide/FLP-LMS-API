<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_salary_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();

            $table->string('role_type'); // faculty, admin etc

            $table->string('salary_month'); // 2026-03

            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);

            $table->decimal('payment_amount', 12, 2);
            $table->string('payment_method'); // cash, bank, upi

            $table->date('payment_date');

            $table->text('comment')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_payments');
    }
};
