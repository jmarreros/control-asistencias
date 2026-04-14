<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('student_id')
                ->constrained('student_plans')
                ->nullOnDelete();
        });

        // Poblar plan_id en registros existentes: busca el plan activo del alumno
        // en esa fecha (sin soft-deleted), tomando el más reciente si hubiera solapamiento
        DB::statement("
            UPDATE attendances
            SET plan_id = (
                SELECT student_plans.id
                FROM student_plans
                WHERE student_plans.student_id = attendances.student_id
                  AND student_plans.start_date <= attendances.date
                  AND student_plans.end_date   >= attendances.date
                  AND student_plans.deleted_at IS NULL
                ORDER BY student_plans.start_date DESC
                LIMIT 1
            )
            WHERE plan_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
