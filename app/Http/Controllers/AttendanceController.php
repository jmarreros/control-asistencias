<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $dayMap = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $todayKey = $dayMap[now()->dayOfWeek];

        $clases = Clase::where('active', true)->withCount('students')->get();

        $todayClases = $clases
            ->filter(fn($c) => is_array($c->schedule) && isset($c->schedule[$todayKey]))
            ->sortBy(fn($c) => is_array($c->schedule[$todayKey]) ? $c->schedule[$todayKey]['start'] : $c->schedule[$todayKey])
            ->values();

        $otherClases = $clases
            ->filter(fn($c) => !is_array($c->schedule) || !isset($c->schedule[$todayKey]))
            ->sortBy('name')
            ->values();

        $clases = $todayClases->merge($otherClases);

        return view('attendance.index', compact('clases', 'todayKey'));
    }

    public function take(Request $request, Clase $clase)
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

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
            'planStatuses', 'extraStudents', 'extraPlanStatuses'
        ));
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
        Attendance::updateOrCreate(
            [
                'clase_id'   => $clase->id,
                'student_id' => $request->student_id,
                'date'       => \Carbon\Carbon::parse($request->date)->toDateString(),
            ],
            ['present' => true]
        );

        return response()->json(['ok' => true]);
    }

    public function toggle(Request $request, Clase $clase)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date'       => 'required|date',
            'present'    => 'required|boolean',
        ]);

        Attendance::updateOrCreate(
            [
                'clase_id'   => $clase->id,
                'student_id' => $request->student_id,
                'date'       => \Carbon\Carbon::parse($request->date)->toDateString(),
            ],
            ['present' => $request->present]
        );

        return response()->json(['ok' => true]);
    }

    public function save(Request $request, Clase $clase)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $students = $clase->students()->pluck('students.id');
        $presentMap = $request->input('present', []);

        $records = $students->map(fn($studentId) => [
            'clase_id'   => $clase->id,
            'student_id' => $studentId,
            'date'       => $request->date,
            'present'    => isset($presentMap[$studentId]) && $presentMap[$studentId] === '1',
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        Attendance::upsert(
            $records,
            ['clase_id', 'student_id', 'date'],
            ['present', 'updated_at']
        );

        return redirect()->route('attendance.index')
            ->with('success', 'Asistencia guardada correctamente.');
    }
}
