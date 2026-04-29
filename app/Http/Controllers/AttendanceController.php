<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\StudentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $students = \App\Models\Student::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'dni']);

        return view('attendance.index', compact('students'));
    }

    public function takeByStudent(Request $request, \App\Models\Student $student)
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

        $dayMap = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $dayKey = $dayMap[$date->dayOfWeek];

        $clases      = $student->clases()->where('active', true)->get();
        $enrolledIds = $clases->pluck('id');

        $existing = Attendance::where('student_id', $student->id)
            ->where('date', $date->toDateString())
            ->whereIn('clase_id', $enrolledIds)
            ->pluck('present', 'clase_id');

        $unenrolledTodayClases = Clase::where('active', true)
            ->whereNotIn('id', $enrolledIds)
            ->get()
            ->filter(fn($c) => is_array($c->schedule) && isset($c->schedule[$dayKey]))
            ->values();

        $planStatus = $student->currentPlan?->status() ?? 'no_plan';

        return view('attendance.take-student', compact(
            'student', 'clases', 'date', 'existing', 'planStatus', 'dayKey', 'unenrolledTodayClases'
        ));
    }

    public function take(Request $request, Clase $clase)
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

        $dayMap = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $dayKey = $dayMap[$date->dayOfWeek];
        $dateInSchedule = is_array($clase->schedule) && isset($clase->schedule[$dayKey]);

        $students = $clase->students()->with('currentPlan')->orderBy('students.name')->get();

        $existing = Attendance::where('clase_id', $clase->id)
            ->where('date', $date->toDateString())
            ->pluck('present', 'student_id');

        $defaultPresent = false;

        $planStatuses = $students->pluck('currentPlan', 'id')->map(
            fn($plan) => $plan?->status() ?? 'no_plan'
        );

        // Todos los alumnos activos no inscritos en este curso (para búsqueda)
        $enrolledIds = $students->pluck('id');
        $extraStudents = \App\Models\Student::where('active', true)
            ->with('currentPlan')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->get();

        $extraPlanStatuses = $extraStudents->pluck('currentPlan', 'id')->map(
            fn($plan) => $plan?->status() ?? 'no_plan'
        );

        return view('attendance.take', compact(
            'clase', 'students', 'date', 'existing', 'defaultPresent',
            'planStatuses', 'extraStudents', 'extraPlanStatuses', 'dateInSchedule'
        ));
    }

    /** Suma `$delta` a classes_remaining (negativo = descuenta). Nunca baja de 0. */
    private function adjustRemaining(int $planId, int $delta): void
    {
        if ($delta === 0) return;

        StudentPlan::where('id', $planId)
            ->whereNotNull('classes_remaining')
            ->update([
                'classes_remaining' => DB::raw('MAX(0, classes_remaining + ' . $delta . ')'),
            ]);
    }

    private function resolvePlanId(int $studentId, string $date): ?int
    {
        return StudentPlan::where('student_id', $studentId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->value('id');
    }

    public function addStudent(Request $request, Clase $clase)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date'       => 'required|date',
        ]);

        // Inscribir al alumno si no lo está
        $clase->students()->syncWithoutDetaching([
            $request->student_id => ['enrolled_at' => today()->toDateString()]
        ]);

        // Guardar asistencia como presente
        $date   = \Carbon\Carbon::parse($request->date)->toDateString();
        $planId = $this->resolvePlanId($request->student_id, $date);

        $existing   = Attendance::where('clase_id', $clase->id)
            ->where('student_id', $request->student_id)
            ->where('date', $date)
            ->first();

        $wasPresent = $existing?->present ?? false;

        Attendance::updateOrCreate(
            ['clase_id' => $clase->id, 'student_id' => $request->student_id, 'date' => $date],
            ['present'  => true, 'plan_id' => $planId]
        );

        if ($planId && !$wasPresent) {
            $this->adjustRemaining($planId, -1);
        }

        return response()->json(['ok' => true]);
    }

    public function toggle(Request $request, Clase $clase)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date'       => 'required|date',
            'present'    => 'required|boolean',
        ]);

        $date    = \Carbon\Carbon::parse($request->date)->toDateString();
        $planId  = $this->resolvePlanId($request->student_id, $date);

        $existing    = Attendance::where('clase_id', $clase->id)
            ->where('student_id', $request->student_id)
            ->where('date', $date)
            ->first();

        $wasPresent = $existing?->present ?? false;
        $isPresent  = (bool) $request->present;

        Attendance::updateOrCreate(
            ['clase_id' => $clase->id, 'student_id' => $request->student_id, 'date' => $date],
            ['present'  => $isPresent, 'plan_id' => $planId]
        );

        // Ajustar clases restantes solo si cambió el estado
        if ($planId && $wasPresent !== $isPresent) {
            $this->adjustRemaining($planId, $isPresent ? -1 : +1);
        }

        return response()->json(['ok' => true]);
    }

    public function save(Request $request, Clase $clase)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $students   = $clase->students()->pluck('students.id');
        $presentMap = $request->input('present', []);
        $date       = $request->date;

        // Cargar asistencias previas para detectar cambios
        $existing = Attendance::where('clase_id', $clase->id)
            ->where('date', $date)
            ->whereIn('student_id', $students)
            ->pluck('present', 'student_id');

        // Cargar planes activos en esa fecha en una sola query
        $plansByStudent = StudentPlan::whereIn('student_id', $students)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->pluck('id', 'student_id');

        $records = $students->map(fn($studentId) => [
            'clase_id'   => $clase->id,
            'student_id' => $studentId,
            'plan_id'    => $plansByStudent[$studentId] ?? null,
            'date'       => $date,
            'present'    => isset($presentMap[$studentId]) && $presentMap[$studentId] === '1',
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        Attendance::upsert($records, ['clase_id', 'student_id', 'date'], ['present', 'plan_id', 'updated_at']);

        // Calcular deltas por plan y aplicar en batch
        $planDeltas = [];
        foreach ($students as $studentId) {
            $wasPresent = (bool) ($existing[$studentId] ?? false);
            $isPresent  = isset($presentMap[$studentId]) && $presentMap[$studentId] === '1';

            if ($wasPresent === $isPresent) continue;

            $planId = $plansByStudent[$studentId] ?? null;
            if (!$planId) continue;

            $planDeltas[$planId] = ($planDeltas[$planId] ?? 0) + ($isPresent ? -1 : +1);
        }

        foreach ($planDeltas as $planId => $delta) {
            $this->adjustRemaining($planId, $delta);
        }

        return redirect()->route('attendance.index')
            ->with('success', 'Asistencia guardada correctamente.');
    }
}
