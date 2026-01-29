<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {

            /**
             * 1️⃣ DROP FOREIGN KEY FIRST
             */
            $table->dropForeign(['teacher_id']);

            /**
             * 2️⃣ MAKE teacher_id NULLABLE
             */
            $table->unsignedBigInteger('teacher_id')->nullable()->change();

            /**
             * 3️⃣ ADD user_id IF NOT EXISTS
             */
            if (!Schema::hasColumn('teacher_attendances', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('teacher_id');
            }

            /**
             * 4️⃣ DROP OLD UNIQUE INDEX
             */
            $table->dropUnique('teacher_attendances_teacher_id_attendance_date_unique');

            /**
             * 5️⃣ ADD NEW SAFE UNIQUE INDEX
             */
            $table->unique(
                ['attendance_date', 'teacher_id', 'user_id'],
                'attendance_unique_teacher_or_user'
            );

            /**
             * 6️⃣ RE-ADD FOREIGN KEY (NULLABLE SAFE)
             */
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {

            $table->dropForeign(['teacher_id']);
            $table->dropUnique('attendance_unique_teacher_or_user');

            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();

            $table->unique(
                ['teacher_id', 'attendance_date'],
                'teacher_attendances_teacher_id_attendance_date_unique'
            );

            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->cascadeOnDelete();
        });
    }
};
