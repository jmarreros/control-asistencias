<?php

/**
 * Script de importación de alumnos desde importacion.csv
 * Uso: php importar.php
 *
 * - Crea alumnos (sin DNI) con sus planes y los asigna a todos los cursos.
 * - Hombres no se asignan a Lady Style.
 * - classes_remaining = min(cuota, sesiones futuras desde hoy hasta end_date).
 * - Planes full1/full2 → classes_remaining = null.
 * - Planes vencidos (end_date < hoy) → classes_remaining = 0.
 */

require __DIR__ . '/vendor/autoload.php';
$app  = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Clase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

$meses = [
    'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4,
    'may' => 5, 'jun' => 6, 'jul' => 7, 'ago' => 8,
    'sep' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
];

// día de semana Carbon (0=Dom, 1=Lun … 6=Sab)
$diasSemana = [
    'lun' => 1, 'mar' => 2, 'mie' => 3,
    'jue' => 4, 'vie' => 5, 'sab' => 6, 'dom' => 0,
];

function parseFecha(string $str, array $meses): Carbon
{
    $str  = rtrim(trim($str), '.');        // quita punto final
    [$dia, $mes] = explode('-', $str, 2);
    $mes  = strtolower(trim($mes));
    if (!isset($meses[$mes])) {
        throw new \InvalidArgumentException("Mes desconocido: '$mes' en '$str'");
    }
    return Carbon::create(2026, $meses[$mes], (int) $dia, 0, 0, 0);
}

function mapCuota(string $plan): string
{
    return match (strtolower(trim($plan))) {
        '4h', '8h'          => '8',
        '12h'               => '12',
        '16h'               => '16',
        '24h'               => '24',
        'full 1', 'full1'   => 'full1',
        'full 2', 'full2'   => 'full2',
        default             => '8',
    };
}

function mapPromocion(?string $promo): ?string
{
    if (!$promo || !trim($promo)) return null;
    $p = strtolower(trim($promo));
    // Cualquier texto con "full" es solo una nota del plan, sin código de promoción
    if (str_contains($p, 'full')) return null;
    // 2x1 (sin "full") → promo_2x1
    if (str_contains($p, '2x1')) return 'promo_2x1';
    // 2x2 u otros sin mapeo conocido
    return null;
}

/**
 * Cuenta las sesiones futuras (hoy inclusive → end_date) de todos los cursos inscritos,
 * sumando cada sesión de clase por separado (una clase = un día de su horario = 1 sesión).
 */
function sesionesRestantes(Carbon $hoy, Carbon $fin, $clases, array $diasSemana): int
{
    if ($fin->lt($hoy)) return 0;

    // Construir lista de [clase_id => [dow, dow, ...]]
    $sesiones = [];  // array de enteros dayOfWeek
    foreach ($clases as $clase) {
        foreach ($clase->schedule as $diaKey => $_) {
            if (isset($diasSemana[$diaKey])) {
                $sesiones[] = $diasSemana[$diaKey];
            }
        }
    }
    if (empty($sesiones)) return 0;

    $count   = 0;
    $current = $hoy->copy();
    while ($current->lte($fin)) {
        $dow = $current->dayOfWeek;   // 0=Dom … 6=Sab
        foreach ($sesiones as $s) {
            if ($dow === $s) $count++;
        }
        $current->addDay();
    }
    return $count;
}

// ──────────────────────────────────────────────
// Datos de referencia
// ──────────────────────────────────────────────

$hoy       = Carbon::today();
$todasClases  = Clase::all();
$clasesSinLady = $todasClases->filter(fn($c) => stripos($c->name, 'lady') === false)->values();

Setting::preload(['price_8h','price_12h','price_16h','price_24h','price_full1','price_full2']);

$precios = [
    '8'     => (float) Setting::get('price_8h',    0),
    '12'    => (float) Setting::get('price_12h',   0),
    '16'    => (float) Setting::get('price_16h',   0),
    '24'    => (float) Setting::get('price_24h',   0),
    'full1' => (float) Setting::get('price_full1', 0),
    'full2' => (float) Setting::get('price_full2', 0),
];

// ──────────────────────────────────────────────
// Lectura del CSV
// ──────────────────────────────────────────────

$csvPath = __DIR__ . '/importacion.csv';
$filas   = array_map('str_getcsv', file($csvPath));
array_shift($filas); // cabecera

$importados = 0;
$errores    = [];

echo "Iniciando importación — " . $hoy->format('d/m/Y') . PHP_EOL;
echo str_repeat('─', 70) . PHP_EOL;

DB::beginTransaction();
try {
    foreach ($filas as $i => $fila) {
        $nombre = trim($fila[0] ?? '');
        if ($nombre === '') continue;   // filas vacías

        $planStr  = trim($fila[1] ?? '');
        $inicioStr = trim($fila[2] ?? '');
        $finStr    = trim($fila[3] ?? '');
        $sexo      = strtolower(trim($fila[4] ?? ''));
        $telefono  = preg_replace('/\D/', '', trim($fila[5] ?? '')) ?: null;
        $promoStr  = trim($fila[6] ?? '');

        // Parsear fechas
        try {
            $inicio = parseFecha($inicioStr, $meses);
            $fin    = parseFecha($finStr,   $meses);
        } catch (\Throwable $e) {
            $errores[] = "Fila " . ($i + 2) . " ($nombre): " . $e->getMessage();
            echo "✗ $nombre — fecha inválida: $inicioStr / $finStr" . PHP_EOL;
            continue;
        }

        $cuota     = mapCuota($planStr);
        $promocion = mapPromocion($promoStr);
        $esMasculino = ($sexo === 'masculino');

        // Cursos a inscribir
        $clasesAlumno = $esMasculino ? $clasesSinLady : $todasClases;

        // classes_remaining
        if (in_array($cuota, ['full1', 'full2'])) {
            $restantes = null;
        } else {
            $futuras   = sesionesRestantes($hoy, $fin, $clasesAlumno, $diasSemana);
            $restantes = min((int) $cuota, $futuras);
        }

        // Precio con descuento si aplica
        $precio = $precios[$cuota] ?? 0;
        if ($promocion === 'promo_2x1') {
            $precio = round($precio * 0.5, 2);
        }

        // Crear o recuperar alumno (mismo nombre = mismo alumno)
        $alumno = Student::firstOrCreate(
            ['name' => $nombre],
            ['phone' => $telefono, 'active' => true, 'dni' => null, 'notes' => null]
        );

        // Actualizar teléfono si llegó vacío la primera vez
        if (!$alumno->phone && $telefono) {
            $alumno->update(['phone' => $telefono]);
        }

        // Crear plan
        StudentPlan::create([
            'student_id'        => $alumno->id,
            'start_date'        => $inicio->toDateString(),
            'end_date'          => $fin->toDateString(),
            'class_quota'       => $cuota,
            'classes_remaining' => $restantes,
            'price'             => $precio,
            'promotion'         => $promocion,
        ]);

        // Inscribir en cursos (syncWithoutDetaching conserva inscripciones previas)
        $syncData = $clasesAlumno->mapWithKeys(
            fn($c) => [$c->id => ['enrolled_at' => $inicio->toDateString()]]
        )->all();
        $alumno->clases()->syncWithoutDetaching($syncData);

        $restantesStr = $restantes !== null ? $restantes : 'null (full)';
        $promoStr2    = $promocion ? " [$promocion]" : '';
        echo sprintf(
            "✓ %-30s %6s  %s → %s  restantes=%-4s%s",
            $nombre,
            $cuota,
            $inicio->format('d/m'),
            $fin->format('d/m'),
            $restantesStr,
            $promoStr2
        ) . PHP_EOL;

        $importados++;
    }

    DB::commit();

    echo str_repeat('─', 70) . PHP_EOL;
    echo "Importados: $importados planes." . PHP_EOL;
    echo "Alumnos únicos: " . Student::count() . PHP_EOL;

    if ($errores) {
        echo PHP_EOL . "Errores:" . PHP_EOL;
        foreach ($errores as $e) {
            echo "  ✗ $e" . PHP_EOL;
        }
    }
} catch (\Throwable $e) {
    DB::rollBack();
    echo PHP_EOL . "ERROR CRÍTICO — rollback aplicado." . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
