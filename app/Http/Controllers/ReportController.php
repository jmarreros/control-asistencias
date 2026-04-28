<?php

namespace App\Http\Controllers;

use App\Exports\EarningsExport;
use App\Exports\StudentsExport;
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

        $attendances = Attendance::with(['student.currentPlan'])
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
                'plan'    => $rows->first()->student->currentPlan,
                'present' => $present,
                'total'   => $total,
            ];
        })->sortBy('student.name')->values();

        return view('reports.clase', compact('clase', 'byStudent', 'sessions', 'from', 'to'));
    }

    public function earnings(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $plans = StudentPlan::with('student')
            ->whereBetween('start_date', [$from, $to])
            ->whereNotNull('price')
            ->orderBy('start_date', 'desc')
            ->get();

        $total = $plans->sum('price');

        $byQuota = $plans->groupBy('class_quota')->map(fn($g) => [
            'count' => $g->count(),
            'total' => $g->sum('price'),
        ]);

        return view('reports.earnings', compact('from', 'to', 'plans', 'total', 'byQuota'));
    }

    public function studentsExport()
    {
        $filename = 'alumnos_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new StudentsExport(), $filename);
    }

    public function earningsExport(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $filename = 'ganancias_' . $from . '_' . $to . '.xlsx';

        return Excel::download(new EarningsExport($from, $to), $filename);
    }

    public function byClaseStudent(Request $request, Clase $clase, Student $student)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $attendances = Attendance::where('clase_id', $clase->id)
            ->where('student_id', $student->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc')
            ->get();

        return view('reports.clase-student', compact('clase', 'student', 'attendances', 'from', 'to'));
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
