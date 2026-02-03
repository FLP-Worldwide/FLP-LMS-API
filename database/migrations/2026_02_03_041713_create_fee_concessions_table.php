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
        Schema::create('fee_concessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['AMOUNT', 'PERCENT']); // flat or %
            $table->decimal('amount', 10, 2);

            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fee_concession_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_concession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('fee_concession_fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_concession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fees_type_id')->constrained()->cascadeOnDelete();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_concession_fee_types');
        Schema::dropIfExists('fee_concession_batches');
        Schema::dropIfExists('fee_concessions');
    }
};
