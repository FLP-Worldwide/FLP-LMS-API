<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('temp_password')->nullable()->after('password');
            $table->timestamp('temp_password_created_at')->nullable()->after('temp_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'temp_password',
                'temp_password_created_at',
            ]);
        });
    }
};
