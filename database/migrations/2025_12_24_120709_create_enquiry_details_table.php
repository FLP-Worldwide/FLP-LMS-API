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
        Schema::create('enquiry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')
                ->constrained('enquiries')
                ->cascadeOnDelete();

            // ================= BASIC =================
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();

            // ================= LOCATION =================
            $table->string('country')->default('India');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('pincode')->nullable();

            $table->text('current_address')->nullable();
            $table->text('residential_address')->nullable();
            $table->boolean('same_address')->default(false);

            // ================= OTHER =================
            $table->string('alternate_contact')->nullable();
            $table->string('alternate_email')->nullable();
            $table->string('nationality')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('category')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('aadhar_no')->nullable();

            // ================= PARENT =================
            $table->string('parent_name')->nullable();
            $table->string('parent_contact')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('parent_profession')->nullable();
            $table->string('parent_aadhar_no')->nullable();

            // ================= GUARDIAN =================
            $table->string('guardian_name')->nullable();
            $table->string('guardian_contact')->nullable();
            $table->string('guardian_email')->nullable();

            // ================= COMMENTS =================
            $table->text('comment')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_details');
    }
};
