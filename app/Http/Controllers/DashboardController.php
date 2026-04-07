<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Clase;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::where('active', true)->count();
        $totalClases = Clase::where('active', true)->count();
        $todayAttendances = Attendance::whereDate('date', today())->distinct('clase_id')->count('clase_id');
        $activeClases = Clase::where('active', true)->withCount('students')->get();

        return view('dashboard.index', compact(
            'totalStudents',
            'totalClases',
            'todayAttendances',
            'activeClases'
        ));
    }
}
