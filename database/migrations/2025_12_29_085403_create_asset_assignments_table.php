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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('asset_category_id');
            $table->unsignedBigInteger('asset_id');

            $table->string('role'); // student / teacher / staff
            $table->unsignedBigInteger('checkout_by')->nullable(); // teacher/user id

            $table->integer('quantity');

            $table->date('assign_date');
            $table->date('due_date')->nullable();
            $table->date('return_date')->nullable();

            $table->text('note')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
