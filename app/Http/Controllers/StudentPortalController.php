<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;

class StudentPortalController extends Controller
{
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
