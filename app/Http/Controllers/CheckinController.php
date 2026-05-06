<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    public function show()
    {
        $clases   = Clase::where('active', true)->orderBy('name')->get();
        $detected = $this->resolveCurrentClase();

        return view('checkin.show', compact('clases', 'detected'));
    }

    public function detectClase()
    {
        $clase = $this->resolveCurrentClase();

        return response()->json([
            'clase' => $clase ? ['id' => $clase->id, 'name' => $clase->name] : null,
        ]);
    }

    public function attendances(Request $request)
    {
        $request->validate(['clase_id' => 'required|exists:clases,id']);

        $records = Attendance::with('student')
            ->where('clase_id', $request->clase_id)
            ->where('date', today()->toDateString())
            ->where('present', true)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($a) => [
                'studentId'   => $a->student_id,
                'name'        => $a->student->name,
                'time'        => $a->updated_at->format('H:i'),
                'status'      => 'ok',
                'notEnrolled' => false,
            ]);

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dni'      => 'required|string|min:7|max:12',
            'clase_id' => 'required|exists:clases,id',
        ]);

        $student = Student::where('dni', $request->dni)->where('active', true)->first();

        if (! $student) {
            return response()->json(['status' => 'not_found']);
        }

        $clase = Clase::find($request->clase_id);
        $date  = today()->toDateString();

        $existing = Attendance::where('clase_id', $clase->id)
            ->where('student_id', $student->id)
            ->where('date', $date)
            ->first();

        if ($existing && $existing->present) {
            return response()->json(['status' => 'already', 'name' => $student->name, 'student_id' => $student->id]);
        }

        $enrolled    = $clase->students()->where('students.id', $student->id)->exists();
        $notEnrolled = ! $enrolled;

        if ($notEnrolled) {
            $clase->students()->syncWithoutDetaching([
                $student->id => ['enrolled_at' => $date],
            ]);
        }

        $planId     = $this->resolvePlanId($student->id, $date);
        $wasPresent = $existing?->present ?? false;

        Attendance::updateOrCreate(
            ['clase_id' => $clase->id, 'student_id' => $student->id, 'date' => $date],
            ['present' => true, 'plan_id' => $planId]
        );

        if ($planId && ! $wasPresent) {
            $this->adjustRemaining($planId, -1);
        }

        return response()->json([
            'status'       => 'ok',
            'student_id'   => $student->id,
            'name'         => $student->name,
            'not_enrolled' => $notEnrolled,
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'clase_id'   => 'required|exists:clases,id',
        ]);

        $date = today()->toDateString();

        $attendance = Attendance::where('clase_id', $request->clase_id)
            ->where('student_id', $request->student_id)
            ->where('date', $date)
            ->where('present', true)
            ->first();

        if (! $attendance) {
            return response()->json(['ok' => true]);
        }

        $attendance->update(['present' => false]);

        if ($attendance->plan_id) {
            $this->adjustRemaining($attendance->plan_id, +1);
        }

        return response()->json(['ok' => true]);
    }

    private function resolveCurrentClase(): ?Clase
    {
        $now    = Carbon::now();
        $dayMap = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $dayKey = $dayMap[$now->dayOfWeek];
        $nowHm  = $now->format('H:i');

        return Clase::where('active', true)->get()->first(function ($clase) use ($dayKey, $nowHm) {
            if (! is_array($clase->schedule) || ! isset($clase->schedule[$dayKey])) {
                return false;
            }

            $times = $clase->schedule[$dayKey];
            $start = $times['start'] ?? null;
            $end   = $times['end']   ?? null;

            if (! $start || ! $end) {
                return false;
            }

            $windowStart = Carbon::createFromFormat('H:i', $start)->subMinutes(15)->format('H:i');
            $windowEnd   = Carbon::createFromFormat('H:i', $end)->subMinutes(15)->format('H:i');

            return $nowHm >= $windowStart && $nowHm <= $windowEnd;
        });
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

    private function adjustRemaining(int $planId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        StudentPlan::where('id', $planId)
            ->whereNotNull('classes_remaining')
            ->update([
                'classes_remaining' => DB::raw('MAX(0, classes_remaining + ' . intval($delta) . ')'),
            ]);
    }
}
