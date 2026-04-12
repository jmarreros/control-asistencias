<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentAuthController extends Controller
{
    public function show()
    {
        if (session('student_id')) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate(['dni' => 'required|string']);

        $student = Student::where('dni', $request->dni)->first();

        if (!$student) {
            return back()->withErrors(['dni' => 'DNI no encontrado.'])->withInput();
        }

        if (!$student->active) {
            return back()->withErrors(['dni' => 'Tu cuenta está desactivada. Consulta con la academia.'])->withInput();
        }

        session()->forget('pin_authenticated');
        session(['student_id' => $student->id]);

        return redirect()->route('student.dashboard');
    }

    public function logout()
    {
        session()->forget('student_id');

        return redirect()->route('student.login');
    }
}
