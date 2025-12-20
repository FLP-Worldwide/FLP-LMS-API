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
        Schema::create('erp_requests', function (Blueprint $table) {
           $table->id();

            // Request type
            $table->enum('type', ['school', 'coaching']);

            // Contact info
            $table->string('institute_name');
            $table->string('contact_person')->nullable();
            $table->string('email');
            $table->string('phone');

            // Location
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');

            // Business flow
            $table->enum('status', ['lead', 'pending', 'paid', 'converted'])->default('lead');
            $table->string('payment_reference')->nullable();

            // If converted
            $table->foreignId('institute_id')->nullable()->constrained()->nullOnDelete();

            // Notes for sales/admin
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_requests');
    }
};
