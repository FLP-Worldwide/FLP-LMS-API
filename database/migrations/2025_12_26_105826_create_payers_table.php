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
        Schema::create('payers', function (Blueprint $table) {
            $table->id();
 $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('display_name');
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->enum('vendor_type',['Customer','Parent','Other'])->default('Customer');
            $table->string('email')->nullable();
            $table->string('contact_no');
            $table->text('address')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payers');
    }
};
