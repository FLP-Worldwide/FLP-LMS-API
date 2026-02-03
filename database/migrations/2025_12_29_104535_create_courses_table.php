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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('institute_id');

            // 🔗 Class / Standard
            $table->unsignedBigInteger('standard_id'); // class_rooms.id

            $table->string('name');
            $table->string('short_description')->nullable();
            $table->boolean('show_on_registration')->default(true);
            $table->boolean('is_active')->default(true);

            // 🔒 Prevent duplicate course per class per institute
            $table->unique(
                ['institute_id', 'standard_id', 'name'],
                'courses_unique_per_class'
            );

                $table->softDeletes();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
