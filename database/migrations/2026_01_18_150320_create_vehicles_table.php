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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->string('vehicle_number');      // RJ14 AB 1234
            $table->string('type');                // School Bus / Van
            $table->integer('capacity');

            $table->unsignedBigInteger('driver_id')->nullable(); // users.id
            $table->unsignedBigInteger('bus_route_id')->nullable(); // bus_routes.id

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->foreign('driver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('bus_route_id')->references('id')->on('bus_routes')->nullOnDelete();

            $table->unique(
                ['institute_id', 'vehicle_number'],
                'vehicle_unique_per_institute'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
