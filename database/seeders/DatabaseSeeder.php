<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    // dayOfWeek (Carbon) → clave de día en schedule
    private const DOW = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];

    public function run(): void
    {
        // ── SETTINGS ─────────────────────────────────────────────────────
        Setting::set('price_8h',    120);
        Setting::set('price_12h',   150);
        Setting::set('price_16h',   170);
        Setting::set('price_24h',   200);
        Setting::set('price_full1', 190);
        Setting::set('price_full2', 210);
        Setting::set('notify_days_before',       3);
        Setting::set('notify_classes_remaining', 1);
        Setting::set('notify_message',         'Hola {nombre}, tu plan está por vencer. Te quedan {clases} clase(s) y vence el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!');
        Setting::set('notify_expired_message',  'Hola {nombre}, tu plan venció el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!');

        // ── LIMPIAR DATOS DE ALUMNOS (se conservan los cursos) ───────────
        DB::table('attendances')->delete();
        DB::table('student_plans')->delete();
        DB::table('clase_student')->delete();
        DB::table('students')->delete();

        // ── OBTENER CURSOS EXISTENTES ─────────────────────────────────────
        $bPrinB = Clase::where('name', 'Bachata - Prin-Basic')->firstOrFail();
        $bInter = Clase::where('name', 'Bachata - Inter')->firstOrFail();
        $lady   = Clase::where('name', 'Lady Style')->firstOrFail();
        $sOn1   = Clase::where('name', 'Salsa - Prin-Basic On1')->firstOrFail();
        $sOn2   = Clase::where('name', 'Salsa - Prin-Basic On2')->firstOrFail();
        $sInter = Clase::where('name', 'Salsa - Inter On2')->firstOrFail();

        // ── ALUMNOS ──────────────────────────────────────────────────────
        $datos = [
            ['name' => 'Ana García López',        'phone' => '987001001', 'dni' => '72001001'],
            ['name' => 'Carlos Mendoza Ríos',     'phone' => '987001002', 'dni' => '72001002'],
            ['name' => 'María Torres Vega',       'phone' => '987001003', 'dni' => '72001003'],
            ['name' => 'Luis Paredes Castillo',   'phone' => '987001004', 'dni' => '72001004'],
            ['name' => 'Sofia Ramírez Cruz',      'phone' => '987001005', 'dni' => '72001005'],
            ['name' => 'Diego Flores Huanca',     'phone' => '987001006', 'dni' => '72001006'],
            ['name' => 'Valentina Pérez Lima',    'phone' => '987001007', 'dni' => '72001007'],
            ['name' => 'Rodrigo Soto Bernal',     'phone' => '987001008', 'dni' => '72001008'],
            ['name' => 'Camila Quispe Ramos',     'phone' => '987001009', 'dni' => '72001009'],
            ['name' => 'Javier Núñez Arce',       'phone' => '987001010', 'dni' => '72001010'],
            ['name' => 'Patricia Vargas Morales', 'phone' => '987001011', 'dni' => '72001011'],
            ['name' => 'Fernando Castro Ruiz',    'phone' => '987001012', 'dni' => '72001012'],
        ];
        $s = collect($datos)->map(fn($d) => Student::create(array_merge($d, ['active' => true])));

        // ── MATRÍCULAS ───────────────────────────────────────────────────
        //   Ana      → bInter (lun/mié/vie) + lady (lun/jue)
        //   Carlos   → bInter (lun/mié/vie) + sInter (mar/jue)
        //   María    → sOn2 (mar/mié/vie) + lady (lun/jue)
        //   Luis     → sOn1 (mar/jue/vie)
        //   Sofia    → lady (lun/jue) + sOn1 (mar/jue/vie)
        //   Diego    → bPrinB (lun/mié) + sOn1 (mar/jue/vie)
        //   Valentina→ lady (lun/jue) + sInter (mar/jue)
        //   Rodrigo  → bInter (lun/mié/vie)
        //   Camila   → sOn2 (mar/mié/vie) + lady (lun/jue)
        //   Javier   → sOn2 (mar/mié/vie)
        //   Patricia → bPrinB (lun/mié) + lady (lun/jue)
        //   Fernando → bPrinB (lun/mié) + sOn1 (mar/jue/vie)

        $e1 = '2026-01-05';
        $e2 = '2026-01-12';

        $bInter->students()->attach($s[0]->id,  ['enrolled_at' => $e1]);
        $lady->students()->attach($s[0]->id,    ['enrolled_at' => $e1]);

        $bInter->students()->attach($s[1]->id,  ['enrolled_at' => $e1]);
        $sInter->students()->attach($s[1]->id,  ['enrolled_at' => $e1]);

        $sOn2->students()->attach($s[2]->id,    ['enrolled_at' => $e1]);
        $lady->students()->attach($s[2]->id,    ['enrolled_at' => $e1]);

        $sOn1->students()->attach($s[3]->id,    ['enrolled_at' => $e1]);

        $lady->students()->attach($s[4]->id,    ['enrolled_at' => $e1]);
        $sOn1->students()->attach($s[4]->id,    ['enrolled_at' => $e1]);

        $bPrinB->students()->attach($s[5]->id,  ['enrolled_at' => $e1]);
        $sOn1->students()->attach($s[5]->id,    ['enrolled_at' => $e1]);

        $lady->students()->attach($s[6]->id,    ['enrolled_at' => $e1]);
        $sInter->students()->attach($s[6]->id,  ['enrolled_at' => $e1]);

        $bInter->students()->attach($s[7]->id,  ['enrolled_at' => $e1]);

        $sOn2->students()->attach($s[8]->id,    ['enrolled_at' => $e2]);
        $lady->students()->attach($s[8]->id,    ['enrolled_at' => $e2]);

        $sOn2->students()->attach($s[9]->id,    ['enrolled_at' => $e2]);

        $bPrinB->students()->attach($s[10]->id, ['enrolled_at' => $e2]);
        $lady->students()->attach($s[10]->id,   ['enrolled_at' => $e2]);

        $bPrinB->students()->attach($s[11]->id, ['enrolled_at' => $e2]);
        $sOn1->students()->attach($s[11]->id,   ['enrolled_at' => $e2]);

        // Cursos por alumno (para generarAsistencias)
        $c = [
            0  => [$bInter, $lady],    // Ana
            1  => [$bInter, $sInter],  // Carlos
            2  => [$sOn2,   $lady],    // María
            3  => [$sOn1],             // Luis
            4  => [$lady,   $sOn1],    // Sofia
            5  => [$bPrinB, $sOn1],    // Diego
            6  => [$lady,   $sInter],  // Valentina
            7  => [$bInter],           // Rodrigo
            8  => [$sOn2,   $lady],    // Camila
            9  => [$sOn2],             // Javier
            10 => [$bPrinB, $lady],    // Patricia
            11 => [$bPrinB, $sOn1],    // Fernando
        ];

        // ── Período 1: enero ─────────────────────────────────────────────
        $this->plan($s[0],  '2026-01-05', '2026-01-25', '12', 150, $c[0]);
        $this->plan($s[1],  '2026-01-05', '2026-01-18', '8',  120, $c[1]);
        $this->plan($s[2],  '2026-01-05', '2026-01-25', '12', 150, $c[2]);
        $this->plan($s[3],  '2026-01-05', '2026-01-18', '8',  120, $c[3]);
        $this->plan($s[4],  '2026-01-05', '2026-01-25', '12', 150, $c[4]);
        $this->plan($s[5],  '2026-01-05', '2026-01-18', '8',  120, $c[5]);
        $this->plan($s[6],  '2026-01-05', '2026-01-25', '12', 150, $c[6]);
        $this->plan($s[7],  '2026-01-05', '2026-01-18', '8',  120, $c[7]);

        // ── Período 2: finales enero → febrero ───────────────────────────
        $this->plan($s[0],  '2026-01-26', '2026-02-22', '16', 170, $c[0]);
        $this->plan($s[1],  '2026-01-19', '2026-02-15', '16', 170, $c[1]);
        $this->plan($s[2],  '2026-01-26', '2026-02-22', '16', 170, $c[2]);
        $this->plan($s[3],  '2026-01-19', '2026-02-15', '16', 170, $c[3]);
        $this->plan($s[4],  '2026-01-26', '2026-02-22', '16', 170, $c[4]);
        $this->plan($s[5],  '2026-01-19', '2026-02-15', '12', 150, $c[5]);
        $this->plan($s[6],  '2026-01-26', '2026-02-22', '16', 170, $c[6]);
        $this->plan($s[7],  '2026-01-19', '2026-02-15', '12', 150, $c[7]);
        $this->plan($s[8],  '2026-01-12', '2026-02-01', '16', 170, $c[8]);
        $this->plan($s[9],  '2026-01-12', '2026-02-08', '12', 150, $c[9]);
        $this->plan($s[10], '2026-01-12', '2026-02-08', '12', 150, $c[10]);
        $this->plan($s[11], '2026-01-12', '2026-02-08', '12', 150, $c[11]);

        // ── Período 3: febrero → marzo ────────────────────────────────────
        $this->plan($s[0],  '2026-02-23', '2026-03-22', '16', 170, $c[0]);
        $this->plan($s[1],  '2026-02-16', '2026-03-15', '16', 170, $c[1]);
        $this->plan($s[2],  '2026-02-23', '2026-03-22', '12', 150, $c[2]);
        $this->plan($s[3],  '2026-02-16', '2026-03-08', '12', 150, $c[3]);
        $this->plan($s[4],  '2026-02-23', '2026-03-15', '12', 150, $c[4]);
        $this->plan($s[5],  '2026-02-16', '2026-03-08', '12', 150, $c[5]);
        $this->plan($s[6],  '2026-02-23', '2026-03-22', '16', 170, $c[6]);
        $this->plan($s[7],  '2026-02-16', '2026-03-15', '16', 170, $c[7]);
        $this->plan($s[8],  '2026-02-02', '2026-03-01', '16', 170, $c[8]);
        $this->plan($s[9],  '2026-02-09', '2026-03-08', '16', 170, $c[9]);
        $this->plan($s[10], '2026-02-09', '2026-03-08', '16', 170, $c[10]);
        $this->plan($s[11], '2026-02-09', '2026-03-08', '16', 170, $c[11]);

        // ── Período 4: marzo → inicio abril ──────────────────────────────
        $this->plan($s[0],  '2026-03-23', '2026-04-05', '8',  120, $c[0]);
        $this->plan($s[1],  '2026-03-16', '2026-04-05', '12', 150, $c[1]);
        $this->plan($s[2],  '2026-03-23', '2026-04-05', '8',  120, $c[2]);
        $this->plan($s[3],  '2026-03-09', '2026-04-05', '16', 170, $c[3]);
        $this->plan($s[4],  '2026-03-16', '2026-04-05', '12', 150, $c[4]);
        $this->plan($s[5],  '2026-03-09', '2026-04-05', '16', 170, $c[5]);
        $this->plan($s[6],  '2026-03-23', '2026-04-05', '8',  120, $c[6]);
        $this->plan($s[7],  '2026-03-16', '2026-04-05', '12', 150, $c[7]);
        $this->plan($s[8],  '2026-03-02', '2026-04-05', '24', 200, $c[8]);
        $this->plan($s[9],  '2026-03-09', '2026-04-05', '16', 170, $c[9]);
        $this->plan($s[10], '2026-03-09', '2026-04-05', '16', 170, $c[10]);
        $this->plan($s[11], '2026-03-09', '2026-04-05', '16', 170, $c[11]);

        // ── Período actual: abril ─────────────────────────────────────────
        //   ACTIVOS   : Ana, Carlos, María, Diego, Camila, Patricia, Fernando
        //   POR VENCER: Luis  → vence 13-abr (3 días desde hoy 10-abr)
        //               Sofia → vence 12-abr (2 días desde hoy 10-abr)
        //   VENCIDOS  : Valentina, Rodrigo, Javier (sin plan en abril)

        $this->plan($s[0],  '2026-04-07', '2026-05-04', '16', 170, $c[0]);  // Ana: activo
        $this->plan($s[1],  '2026-04-07', '2026-05-04', '24', 200, $c[1]);  // Carlos: activo
        $this->plan($s[2],  '2026-04-07', '2026-05-04', '16', 170, $c[2]);  // María: activo
        $this->plan($s[3],  '2026-04-07', '2026-04-13', '8',  120, $c[3]);  // Luis: por vencer (fecha)
        $this->plan($s[4],  '2026-04-07', '2026-04-12', '8',  120, $c[4]);  // Sofia: por vencer (fecha)
        $this->plan($s[5],  '2026-04-07', '2026-05-04', '8',  120, $c[5]);  // Diego: activo
        $this->plan($s[8],  '2026-04-07', '2026-05-04', '12', 150, $c[8]);  // Camila: activo
        $this->plan($s[10], '2026-04-07', '2026-05-04', '12', 150, $c[10]); // Patricia: activo
        $this->plan($s[11], '2026-04-07', '2026-05-04', '16', 170, $c[11]); // Fernando: activo
        // Valentina ($s[6]), Rodrigo ($s[7]), Javier ($s[9]) → sin plan en abril → vencidos
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function plan(Student $student, string $start, string $end, string $quota, float $price, array $clases): void
    {
        $plan = StudentPlan::create([
            'student_id'  => $student->id,
            'start_date'  => $start,
            'end_date'    => $end,
            'class_quota' => $quota,
            'price'       => $price,
        ]);

        $this->generarAsistencias($student, $plan, $clases);
    }

    /**
     * Genera asistencias para un plan, marcando exactamente `class_quota` como presentes
     * (o ~82% para planes full), distribuidas aleatoriamente entre las sesiones disponibles.
     */
    private function generarAsistencias(Student $student, StudentPlan $plan, array $clases): void
    {
        $isFull = in_array($plan->class_quota, ['full1', 'full2']);
        $quota  = $isFull ? PHP_INT_MAX : (int) $plan->class_quota;

        $start = Carbon::parse($plan->start_date);
        $end   = Carbon::parse($plan->end_date)->min(Carbon::today());

        if ($start->gt($end)) return;

        // Recopilar todas las sesiones disponibles dentro del período
        $sessions = [];
        $day      = $start->copy();
        while ($day->lte($end)) {
            $key = self::DOW[$day->dayOfWeek];
            foreach ($clases as $clase) {
                if (isset($clase->schedule[$key])) {
                    $sessions[] = ['clase_id' => $clase->id, 'date' => $day->toDateString()];
                }
            }
            $day->addDay();
        }

        if (empty($sessions)) return;

        shuffle($sessions);
        $presentCount = $isFull
            ? (int) round(count($sessions) * 0.82)
            : min($quota, count($sessions));

        $presentSet = collect(array_slice($sessions, 0, $presentCount))
            ->keyBy(fn($s) => $s['clase_id'] . '_' . $s['date']);

        foreach ($sessions as $s) {
            Attendance::create([
                'clase_id'   => $s['clase_id'],
                'student_id' => $student->id,
                'date'       => $s['date'],
                'present'    => $presentSet->has($s['clase_id'] . '_' . $s['date']),
            ]);
        }
    }
}
