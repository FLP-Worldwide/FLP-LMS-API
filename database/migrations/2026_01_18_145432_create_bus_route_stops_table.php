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
       Schema::create('bus_route_stops', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('bus_route_id');

            $table->string('stop_name');
            $table->integer('stop_order'); // 1,2,3...

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('institute_id')
                ->references('id')
                ->on('institutes')
                ->cascadeOnDelete();

            $table->foreign('bus_route_id')
                ->references('id')
                ->on('bus_routes')
                ->cascadeOnDelete();

            $table->unique(
                ['bus_route_id', 'stop_order'],
                'unique_stop_order_per_route'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_route_stops');
    }
};
