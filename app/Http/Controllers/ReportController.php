<?php

namespace App\Http\Controllers;

use App\Exports\EarningsExport;
use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;
use App\Models\StudentPlan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $clases = Clase::orderBy('name')->get();
        $students = Student::where('active', true)->orderBy('name')->get();

        return view('reports.index', compact('clases', 'students'));
    }

    public function byClase(Request $request, Clase $clase)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $attendances = Attendance::with('student')
            ->where('clase_id', $clase->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $sessions = $attendances->pluck('date')->unique()->sortDesc()->values();

        $byStudent = $attendances->groupBy('student_id')->map(function ($rows) use ($sessions) {
            $total   = $sessions->count();
            $present = $rows->where('present', true)->count();
            return [
                'student' => $rows->first()->student,
                'present' => $present,
                'total'   => $total,
                'rate'    => $total > 0 ? round($present / $total * 100) : 0,
            ];
        })->sortBy('student.name')->values();

        return view('reports.clase', compact('clase', 'byStudent', 'sessions', 'from', 'to'));
    }

    public function earnings(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $plans = StudentPlan::with('student')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotNull('price')
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $plans->sum('price');

        $byQuota = $plans->groupBy('class_quota')->map(fn($g) => [
            'count' => $g->count(),
            'total' => $g->sum('price'),
        ]);

        // Por curso: alumnos inscritos en cada clase que tienen plan en el período
        $clases = Clase::with(['students' => function ($q) use ($from, $to) {
            $q->whereHas('plans', fn($p) => $p->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->whereNotNull('price'));
        }])->orderBy('name')->get();

        $byClase = $clases->map(function ($clase) use ($from, $to) {
            $students = $clase->students->map(function ($student) use ($from, $to) {
                $plan = $student->plans()
                    ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->whereNotNull('price')
                    ->orderByDesc('created_at')
                    ->first();
                return $plan ? ['student' => $student, 'plan' => $plan] : null;
            })->filter()->values();

            return [
                'clase'   => $clase,
                'students' => $students,
                'total'   => $students->sum(fn($r) => $r['plan']->price),
            ];
        })->filter(fn($r) => $r['students']->isNotEmpty())->values();

        return view('reports.earnings', compact('from', 'to', 'plans', 'total', 'byQuota', 'byClase'));
    }

    public function earningsExport(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $filename = 'ganancias_' . $from . '_' . $to . '.xlsx';

        return Excel::download(new EarningsExport($from, $to), $filename);
    }

    public function byStudent(Request $request, Student $student)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $attendances = Attendance::with('clase')
            ->where('student_id', $student->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc')
            ->get();

        $byClase = $attendances->groupBy('clase_id')->map(function ($rows) {
            $total   = $rows->count();
            $present = $rows->where('present', true)->count();
            return [
                'clase'   => $rows->first()->clase,
                'present' => $present,
                'total'   => $total,
                'rate'    => $total > 0 ? round($present / $total * 100) : 0,
            ];
        })->sortBy('clase.name')->values();

        return view('reports.student', compact('student', 'attendances', 'byClase', 'from', 'to'));
    }
}
