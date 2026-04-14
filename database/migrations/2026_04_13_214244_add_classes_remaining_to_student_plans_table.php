<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_plans', function (Blueprint $table) {
            $table->unsignedInteger('classes_remaining')->nullable()->after('class_quota');
        });

        // Poblar con los valores actuales: quota - asistencias presentes del plan
        // Plans full1/full2 quedan en NULL
        DB::statement("
            UPDATE student_plans
            SET classes_remaining = CASE
                WHEN class_quota IN ('full1', 'full2') THEN NULL
                ELSE MAX(0, CAST(class_quota AS INTEGER) - (
                    SELECT COUNT(*) FROM attendances
                    WHERE attendances.plan_id = student_plans.id
                      AND attendances.present = 1
                ))
            END
        ");
    }

    public function down(): void
    {
        Schema::table('student_plans', function (Blueprint $table) {
            $table->dropColumn('classes_remaining');
        });
    }
};
