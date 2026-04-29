<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    private const QUOTA_MAP = [
        '8 horas'  => '8',
        '12 horas' => '12',
        '16 horas' => '16',
        '24 horas' => '24',
        'full-1'   => 'full1',
        'full-2'   => 'full2',
        'full 1'   => 'full1',
        'full 2'   => 'full2',
        'full1'    => 'full1',
        'full2'    => 'full2',
    ];

    private const QUOTA_REMAINING = [
        '8'     => 8,
        '12'    => 12,
        '16'    => 16,
        '24'    => 24,
        'full1' => null,
        'full2' => null,
    ];

    public function show()
    {
        return view('import.index');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.mimes'    => 'El archivo debe ser un CSV.',
            'file.max'      => 'El archivo no debe superar los 5 MB.',
        ]);

        $path   = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');

        $firstLine = fgets($handle);
        rewind($handle);

        $separator = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $rawHeaders = fgetcsv($handle, 2000, $separator);
        if (!$rawHeaders) {
            fclose($handle);
            return back()->withErrors(['file' => 'El archivo CSV está vacío o sin encabezados.']);
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

        $col = fn(string ...$names) => collect($names)
            ->map(fn($n) => array_search($n, $headers))
            ->filter(fn($i) => $i !== false)
            ->first();

        $iName      = $col('name', 'nombre');
        $iDni       = $col('dni');
        $iPhone     = $col('phone', 'telefono');
        $iStartDate = $col('start_date', 'fecha_inicio');
        $iEndDate   = $col('end_date', 'fecha_fin');
        $iPlan      = $col('nombre_plan', 'plan');
        $iPrice     = $col('price', 'precio');
        $iRemaining = $col('clases_restantes', 'classes_remaining');

        if ($iName === null) {
            fclose($handle);
            return back()->withErrors(['file' => 'El archivo debe tener una columna "name" o "nombre".']);
        }

        $studentsCreated = 0;
        $plansCreated    = 0;
        $skippedPlan     = 0;
        $warnings        = [];
        $rowNumber       = 1;

        while (($data = fgetcsv($handle, 2000, $separator)) !== false) {
            $rowNumber++;

            $name = trim($data[$iName] ?? '');
            if ($name === '') {
                $warnings[] = "Fila $rowNumber: nombre vacío, omitida.";
                continue;
            }

            $dni   = $iDni   !== null ? trim($data[$iDni]   ?? '') : '';
            $phone = $iPhone !== null ? trim($data[$iPhone] ?? '') : '';

            // Buscar alumno existente por DNI primero, luego por nombre
            $student = null;
            if ($dni !== '') {
                $student = Student::where('dni', $dni)->first();
            }
            if (!$student) {
                $student = Student::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])->first();
            }

            if (!$student) {
                $student = Student::create([
                    'name'   => $name,
                    'dni'    => $dni  ?: null,
                    'phone'  => $phone ?: null,
                    'active' => true,
                ]);
                $studentsCreated++;
            }

            // Datos de plan
            $startDateRaw = $iStartDate !== null ? trim($data[$iStartDate] ?? '') : '';
            $endDateRaw   = $iEndDate   !== null ? trim($data[$iEndDate]   ?? '') : '';
            $planName     = $iPlan      !== null ? trim($data[$iPlan]      ?? '') : '';

            if ($startDateRaw === '' || $endDateRaw === '' || $planName === '') {
                continue;
            }

            $quotaKey = self::QUOTA_MAP[strtolower($planName)] ?? null;
            if (!$quotaKey) {
                $warnings[] = "Fila $rowNumber: nombre_plan \"$planName\" no reconocido, plan omitido.";
                continue;
            }

            // Verificar plan activo
            $currentPlan = $student->currentPlan;
            if ($currentPlan && in_array($currentPlan->status(), ['ok', 'pending'])) {
                $skippedPlan++;
                continue;
            }

            // Parsear fechas
            try {
                $startDate = $this->parseDate($startDateRaw);
                $endDate   = $this->parseDate($endDateRaw);
            } catch (\Exception) {
                $warnings[] = "Fila $rowNumber: fecha inválida ($startDateRaw / $endDateRaw), plan omitido.";
                continue;
            }

            $price = 0;
            if ($iPrice !== null && trim($data[$iPrice] ?? '') !== '') {
                $price = (float) str_replace(',', '.', trim($data[$iPrice]));
            }

            $defaultRemaining = self::QUOTA_REMAINING[$quotaKey];
            $remaining        = $defaultRemaining;
            if ($iRemaining !== null && trim($data[$iRemaining] ?? '') !== '') {
                $remaining = in_array($quotaKey, ['full1', 'full2'])
                    ? null
                    : (int) trim($data[$iRemaining]);
            }

            StudentPlan::create([
                'student_id'        => $student->id,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'class_quota'       => $quotaKey,
                'classes_remaining' => $remaining,
                'price'             => $price,
            ]);
            $plansCreated++;
        }

        fclose($handle);

        $msg = "Importación completada: {$studentsCreated} alumno(s) creado(s), {$plansCreated} plan(es) importado(s)";
        if ($skippedPlan > 0) {
            $msg .= ", {$skippedPlan} plan(es) omitido(s) por plan activo";
        }
        $msg .= '.';

        $session = ['success' => $msg];
        if ($warnings) {
            $session['import_warnings'] = $warnings;
        }

        return redirect()->route('import.show')->with($session);
    }

    private function parseDate(string $date): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            return Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        }
        return Carbon::parse($date)->toDateString();
    }
}
