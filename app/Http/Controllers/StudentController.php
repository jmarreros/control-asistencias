<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('currentPlan')->orderBy('name')->get();

        $students->each(fn($s) => $s->planStatus = $s->currentPlan?->status() ?? 'no_plan');

        return view('students.index', compact('students'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'dni'   => 'nullable|string|max:20|unique:students,dni',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $student = Student::create($data);

        return redirect()->route('students.plans.index', $student)
            ->with('success', 'Alumno registrado. Ahora agrega su plan de clases.');
    }

    public function edit(Student $student)
    {
        $currentPlan = $student->currentPlan;

        return view('students.edit', compact('student', 'currentPlan'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'dni'    => 'nullable|string|max:20|unique:students,dni,' . $student->id,
            'phone'  => 'nullable|string|max:20',
            'notes'  => 'nullable|string',
            'active' => 'boolean',
        ]);

        $student->update($data);

        return redirect()->route('students.index')
            ->with('success', 'Alumno actualizado correctamente.');
    }

    public function destroy(Student $student)
    {
        $student->update(['active' => false]);

        return redirect()->route('students.index')
            ->with('success', 'Alumno desactivado.');
    }
}
