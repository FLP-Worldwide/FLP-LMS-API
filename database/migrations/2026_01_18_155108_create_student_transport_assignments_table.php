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
        Schema::create('student_transport_assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('bus_route_id');

            $table->string('pickup_point'); // TEXT snapshot

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('bus_route_id')->references('id')->on('bus_routes')->cascadeOnDelete();

            // 🚫 Prevent duplicate assignment
            $table->unique(
                ['student_id', 'institute_id'],
                'student_unique_transport'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transport_assignments');
    }
};
