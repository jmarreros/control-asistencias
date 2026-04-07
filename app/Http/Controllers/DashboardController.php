<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;
use App\Models\StudentPlan;

class DashboardController extends Controller
{
    public function index()
    {
        $activeStudents  = Student::where('active', true)->count();
        $monthlyPlans    = StudentPlan::whereYear('start_date', now()->year)
                              ->whereMonth('start_date', now()->month)
                              ->count();
        $monthlyIncome   = StudentPlan::whereYear('start_date', now()->year)
                              ->whereMonth('start_date', now()->month)
                              ->whereNotNull('price')
                              ->sum('price');

        $activeClases = Clase::where('active', true)->withCount('students')->get();

        return view('dashboard.index', compact(
            'activeStudents',
            'monthlyPlans',
            'monthlyIncome',
            'activeClases'
        ));
    }
}
