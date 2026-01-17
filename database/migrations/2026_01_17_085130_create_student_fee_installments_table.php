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
        Schema::create('student_fee_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_fee_id')->constrained()->cascadeOnDelete();

            $table->foreignId('fee_type_id')->constrained('fees_types');
            $table->enum('assign_type', ['TRIGGER','BAD','DAYS_AFTER_BAD','MONTH_AFTER_BAD']);
            $table->integer('offset')->default(0);
            $table->decimal('amount', 10, 2);

            $table->boolean('is_extra')->default(false); // ⭐ key
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_installments');
    }
};
