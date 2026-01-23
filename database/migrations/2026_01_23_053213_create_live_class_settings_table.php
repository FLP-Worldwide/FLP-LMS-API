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
        Schema::create('live_class_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id')->nullable();
            // null = global, or set institute-wise later

            $table->boolean('recording_enabled')->default(false);

            $table->json('recorded_view_visibility')->nullable();
            $table->json('recorded_download_visibility')->nullable();

            $table->json('attendance')->nullable();

            $table->json('vdocipher')->nullable();

            $table->boolean('zoom_account_selection')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_class_settings');
    }
};
