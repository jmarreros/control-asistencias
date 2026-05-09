<?php

namespace App\Http\Controllers;

use App\Models\Student;

class MatriculaController extends Controller
{
    public function index()
    {
        $students = Student::where('active', true)
            ->with('currentPlan')
            ->orderBy('name')
            ->get(['id', 'name', 'dni']);

        return view('matricula.index', compact('students'));
    }
}
