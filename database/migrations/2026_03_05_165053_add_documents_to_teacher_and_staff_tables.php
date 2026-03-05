<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_details', function (Blueprint $table) {
            $table->string('document1')->nullable()->after('address');
            $table->string('document2')->nullable()->after('document1');
        });

        Schema::table('staff_details', function (Blueprint $table) {
            $table->string('document1')->nullable()->after('id_number');
            $table->string('document2')->nullable()->after('document1');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_details', function (Blueprint $table) {
            $table->dropColumn(['document1', 'document2']);
        });

        Schema::table('staff_details', function (Blueprint $table) {
            $table->dropColumn(['document1', 'document2']);
        });
    }
};
