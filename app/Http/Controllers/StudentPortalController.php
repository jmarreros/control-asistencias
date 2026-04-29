<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    private const QUOTA_LABELS = [
        '8'     => '8 horas',
        '12'    => '12 horas',
        '16'    => '16 horas',
        '24'    => '24 horas',
        'full1' => 'Full-1 (ilimitado)',
        'full2' => 'Full-2 (ilimitado)',
    ];

    private const STATUS_LABELS = [
        'ok'        => 'Activo',
        'exhausted' => 'Clases agotadas',
        'expired'   => 'Vencido',
        'pending'   => 'Pendiente',
    ];

    public function publicSearch()
    {
        return view('student.lookup');
    }

    public function lookup(Request $request)
    {
        $dni = trim($request->input('dni', ''));

        if ($dni === '') {
            return response()->json(['found' => false, 'message' => 'Ingresa un DNI para buscar.']);
        }

        $student = Student::where('dni', $dni)
            ->where('active', true)
            ->with('currentPlan')
            ->first();

        if (!$student) {
            return response()->json(['found' => false, 'message' => 'No se encontró ningún alumno con ese DNI.']);
        }

        $plan = $student->currentPlan;

        return response()->json([
            'found' => true,
            'name'  => $student->name,
            'plan'  => $plan ? [
                'quota_label'  => self::QUOTA_LABELS[$plan->class_quota] ?? $plan->class_quota,
                'status'       => $plan->status(),
                'status_label' => self::STATUS_LABELS[$plan->status()] ?? '—',
                'remaining'    => $plan->classesRemaining(),
                'start_date'   => Carbon::parse($plan->start_date)->format('d/m/Y'),
                'end_date'     => Carbon::parse($plan->end_date)->format('d/m/Y'),
            ] : null,
        ]);
    }

    private function student(): Student
    {
        return Student::with('currentPlan')->findOrFail(session('student_id'));
    }

    public function index()
    {
        $student    = $this->student();
        $plan       = $student->currentPlan;
        $planStatus = $plan?->status() ?? 'no_plan';

        $from = $plan?->start_date;
        $to   = $plan?->end_date;

        $clases = $student->clases()->orderBy('name')->get();

        $stats = $clases->map(function ($clase) use ($student, $from, $to) {
            $query = Attendance::where('clase_id', $clase->id)
                ->where('student_id', $student->id);

            if ($from && $to) {
                $query->whereBetween('date', [$from, $to]);
            }

            $attendances = $query->get();
            $total       = $attendances->count();
            $present     = $attendances->where('present', true)->count();

            return [
                'clase'   => $clase,
                'total'   => $total,
                'present' => $present,
                'rate'    => $total > 0 ? round($present / $total * 100) : null,
            ];
        });

        return view('student.dashboard', compact('student', 'plan', 'planStatus', 'stats'));
    }

    public function byClase(Clase $clase)
    {
        $student = $this->student();

        if (!$student->clases()->where('clases.id', $clase->id)->exists()) {
            abort(403);
        }

        $plan = $student->currentPlan;
        $from = $plan?->start_date;
        $to   = $plan?->end_date;

        $query = Attendance::where('clase_id', $clase->id)
            ->where('student_id', $student->id);

        if ($from && $to) {
            $query->whereBetween('date', [$from, $to]);
        }

        $attendances = $query->orderBy('date', 'desc')->get();
        $present     = $attendances->where('present', true)->count();
        $total       = $attendances->count();

        return view('student.clase', compact('student', 'plan', 'clase', 'attendances', 'present', 'total'));
    }
}
