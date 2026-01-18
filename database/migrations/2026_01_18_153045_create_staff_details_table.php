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
        Schema::create('staff_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');       // users.id
            $table->unsignedBigInteger('institute_id');  // institutes.id

            // 🔹 Common staff fields
            $table->string('phone')->nullable();

            $table->string('designation')->nullable();   // Driver, Teacher, Accountant
            $table->date('joining_date')->nullable();

            // 🔹 Driver-specific (optional)
            $table->string('id_number')->nullable();

            // 🔹 Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // 🔑 Constraints
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('institute_id')
                ->references('id')
                ->on('institutes')
                ->cascadeOnDelete();

            $table->unique(
                ['user_id', 'institute_id'],
                'staff_unique_per_institute'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_details');
    }
};
