<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Student;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function edit(Clase $clase)
    {
        $allStudents = Student::where('active', true)->orderBy('name')->get();
        $enrolledIds = $clase->students()->pluck('students.id')->toArray();

        return view('clases.enroll', compact('clase', 'allStudents', 'enrolledIds'));
    }

    public function update(Request $request, Clase $clase)
    {
        $studentIds = $request->input('student_ids', []);

        $syncData = [];
        foreach ($studentIds as $id) {
            $syncData[$id] = ['enrolled_at' => now()->toDateString()];
        }

        $clase->students()->sync($syncData);

        return redirect()->route('clases.index')
            ->with('success', 'Matrícula del curso actualizada correctamente.');
    }
}
