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
        Schema::create('teacher_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();

            // Personal
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Qualification
            $table->string('qualification')->nullable();
            $table->integer('experience_years')->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');

            // Banking (future payroll)
            $table->string('bank_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('ifsc')->nullable();

            $table->string('photo')->nullable();
            $table->longText('docs')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_details');
    }
};
