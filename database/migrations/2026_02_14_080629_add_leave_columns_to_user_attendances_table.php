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
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->date('applied_on')->nullable();
            $table->string('leave_status')->default('approved')->after('status');
            $table->text('remark')->nullable()->after('leave_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropColumn(['leave_status', 'remark','applied_on']);
        });
    }
};
