<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Settings
        Setting::set('price_8h',   120);
        Setting::set('price_12h',  150);
        Setting::set('price_16h',  170);
        Setting::set('price_full', 190);

        // Alumnos
        $students = collect([
            ['name' => 'Ana García López',       'phone' => '999-111-001', 'active' => true],
            ['name' => 'Carlos Mendoza Ríos',    'phone' => '999-111-002', 'active' => true],
            ['name' => 'María Torres Vega',      'phone' => '999-111-003', 'active' => true],
            ['name' => 'Luis Paredes Castillo',  'phone' => '999-111-004', 'active' => true],
            ['name' => 'Sofia Ramírez Cruz',     'phone' => '999-111-005', 'active' => true],
            ['name' => 'Diego Flores Huanca',    'phone' => '999-111-006', 'active' => true],
            ['name' => 'Valentina Pérez Lima',   'phone' => '999-111-007', 'active' => true],
            ['name' => 'Rodrigo Soto Bernal',    'phone' => '999-111-008', 'active' => true],
            ['name' => 'Camila Quispe Ramos',    'phone' => '999-111-009', 'active' => true],
            ['name' => 'Javier Núñez Arce',      'phone' => '999-111-010', 'active' => true],
        ])->map(fn($d) => Student::create($d));

        // Clases
        $salsa = Clase::create([
            'name'        => 'Salsa Principiantes',
            'description' => 'Clase ideal para quienes inician en la salsa',
            'schedule'    => ['lun' => ['start' => '18:00', 'end' => '19:30'], 'mie' => ['start' => '18:00', 'end' => '19:30']],
            'active'      => true,
        ]);

        $bachata = Clase::create([
            'name'        => 'Bachata Intermedio',
            'description' => 'Para alumnos con base en bachata',
            'schedule'    => ['mar' => ['start' => '19:30', 'end' => '21:00'], 'jue' => ['start' => '19:30', 'end' => '21:00']],
            'active'      => true,
        ]);

        $ballet = Clase::create([
            'name'        => 'Ballet Adultos',
            'description' => 'Ballet clásico para adultos',
            'schedule'    => ['vie' => ['start' => '17:00', 'end' => '18:30']],
            'active'      => true,
        ]);

        $urbano = Clase::create([
            'name'        => 'Urbano y Hip Hop',
            'description' => 'Danza urbana y hip hop',
            'schedule'    => ['sab' => ['start' => '10:00', 'end' => '11:30']],
            'active'      => true,
        ]);

        // Matrículas
        // Salsa: Ana, Carlos, María, Luis, Sofia, Diego
        $salsa->students()->sync($students->take(6)->pluck('id'));
        // Bachata: María, Luis, Sofia, Diego, Valentina, Rodrigo
        $bachata->students()->sync($students->skip(2)->take(6)->pluck('id'));
        // Ballet: Ana, Carlos, María, Camila
        $ballet->students()->sync($students->filter(fn($s) => in_array($s->name, [
            'Ana García López', 'Carlos Mendoza Ríos', 'María Torres Vega', 'Camila Quispe Ramos'
        ]))->pluck('id'));
        // Urbano: Sofia, Valentina, Rodrigo, Camila, Javier
        $urbano->students()->sync($students->filter(fn($s) => in_array($s->name, [
            'Sofia Ramírez Cruz', 'Valentina Pérez Lima', 'Rodrigo Soto Bernal', 'Camila Quispe Ramos', 'Javier Núñez Arce'
        ]))->pluck('id'));

        // ── PLANES HISTÓRICOS ──────────────────────────────────────────

        // Hace 3 meses (enero)
        $this->crearPlan($students[0], '2026-01-06', '2026-02-05', '12', 150);   // Ana
        $this->crearPlan($students[1], '2026-01-06', '2026-02-05', '8',  120);   // Carlos
        $this->crearPlan($students[2], '2026-01-06', '2026-02-05', 'full', 190); // María
        $this->crearPlan($students[3], '2026-01-13', '2026-02-12', '8',  100);   // Luis (descuento)
        $this->crearPlan($students[4], '2026-01-13', '2026-02-12', '12', 150);   // Sofia

        // Hace 2 meses (febrero)
        $this->crearPlan($students[0], '2026-02-06', '2026-03-05', '12', 150);   // Ana renovación
        $this->crearPlan($students[1], '2026-02-06', '2026-03-05', '12', 150);   // Carlos sube de plan
        $this->crearPlan($students[2], '2026-02-06', '2026-03-05', 'full', 190); // María
        $this->crearPlan($students[5], '2026-02-10', '2026-03-09', '8',  120);   // Diego nuevo
        $this->crearPlan($students[6], '2026-02-10', '2026-03-09', '16', 160);   // Valentina (promo)
        $this->crearPlan($students[7], '2026-02-17', '2026-03-16', '8',  120);   // Rodrigo

        // Mes pasado (marzo)
        $this->crearPlan($students[0], '2026-03-06', '2026-04-05', '16', 170);   // Ana
        $this->crearPlan($students[1], '2026-03-06', '2026-04-05', '12', 150);   // Carlos
        $this->crearPlan($students[2], '2026-03-06', '2026-04-05', 'full', 190); // María
        $this->crearPlan($students[3], '2026-03-10', '2026-04-09', '12', 140);   // Luis (promo)
        $this->crearPlan($students[4], '2026-03-10', '2026-04-09', '12', 150);   // Sofia
        $this->crearPlan($students[5], '2026-03-10', '2026-04-09', '8',  120);   // Diego
        $this->crearPlan($students[8], '2026-03-17', '2026-04-16', '8',  120);   // Camila nueva
        $this->crearPlan($students[9], '2026-03-17', '2026-04-16', '16', 170);   // Javier

        // Mes actual (abril — planes vigentes)
        $this->crearPlan($students[0], '2026-04-07', '2026-05-06', '16', 170);   // Ana
        $this->crearPlan($students[1], '2026-04-07', '2026-05-06', '12', 150);   // Carlos
        $this->crearPlan($students[2], '2026-04-07', '2026-05-06', 'full', 190); // María
        $this->crearPlan($students[4], '2026-04-07', '2026-05-06', '12', 150);   // Sofia
        $this->crearPlan($students[5], '2026-04-07', '2026-05-06', '8',  120);   // Diego
        $this->crearPlan($students[6], '2026-04-07', '2026-05-06', 'full', 180); // Valentina (promo)
        $this->crearPlan($students[8], '2026-04-07', '2026-05-06', '8',  120);   // Camila

        // ── ASISTENCIAS HISTÓRICAS ─────────────────────────────────────

        // Salsa: lun y mié desde enero
        $this->generarAsistencias($salsa, $students->take(6), '2026-01-06', [1, 3], 0.85);

        // Bachata: mar y jue desde enero
        $this->generarAsistencias($bachata, $students->slice(2, 6), '2026-01-07', [2, 4], 0.75);

        // Ballet: vie desde enero
        $this->generarAsistencias($ballet, $students->filter(fn($s) => in_array($s->name, [
            'Ana García López', 'Carlos Mendoza Ríos', 'María Torres Vega', 'Camila Quispe Ramos'
        ])), '2026-01-10', [5], 0.80);

        // Urbano: sáb desde febrero
        $this->generarAsistencias($urbano, $students->filter(fn($s) => in_array($s->name, [
            'Sofia Ramírez Cruz', 'Valentina Pérez Lima', 'Rodrigo Soto Bernal', 'Camila Quispe Ramos', 'Javier Núñez Arce'
        ])), '2026-02-07', [6], 0.70);
    }

    private function crearPlan(Student $student, string $start, string $end, string $quota, float $price): void
    {
        StudentPlan::create([
            'student_id'  => $student->id,
            'start_date'  => $start,
            'end_date'    => $end,
            'class_quota' => $quota,
            'price'       => $price,
        ]);
    }

    /**
     * Genera asistencias para una clase desde $desde hasta hoy,
     * solo en los días de semana indicados (0=dom, 1=lun, ..., 6=sáb).
     */
    private function generarAsistencias(Clase $clase, $students, string $desde, array $diasSemana, float $tasaPresencia): void
    {
        $inicio = Carbon::parse($desde);
        $fin    = Carbon::today();
        $current = $inicio->copy();

        while ($current->lte($fin)) {
            if (in_array($current->dayOfWeek, $diasSemana)) {
                foreach ($students as $student) {
                    // Solo generar si el alumno ya está matriculado
                    if ($clase->students->contains($student->id)) {
                        Attendance::create([
                            'clase_id'   => $clase->id,
                            'student_id' => $student->id,
                            'date'       => $current->toDateString(),
                            'present'    => (mt_rand(1, 100) <= ($tasaPresencia * 100)),
                        ]);
                    }
                }
            }
            $current->addDay();
        }
    }
}
