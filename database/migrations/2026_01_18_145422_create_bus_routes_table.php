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
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            $table->string('route_name');
            $table->string('vehicle_number')->nullable();

            $table->string('start_point'); // City / Area
            $table->string('end_point');   // School

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('institute_id')
                ->references('id')
                ->on('institutes')
                ->cascadeOnDelete();

            $table->unique(
                ['institute_id', 'route_name'],
                'bus_route_unique_per_institute'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_routes');
    }
};
