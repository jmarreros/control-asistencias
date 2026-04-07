<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentPlanController extends Controller
{
    public function index(Student $student)
    {
        $plans = $student->plans()->get();
        $currentPlan = $plans->first();

        $startDefault = now()->isWeekend()
            ? now()->nextWeekday()
            : now();

        $defaultStartDate = $startDefault->toDateString();
        $defaultEndDate   = $startDefault->copy()->addMonth()->toDateString();

        $prices = [
            '8'    => Setting::get('price_8h', 120),
            '12'   => Setting::get('price_12h', 150),
            '16'   => Setting::get('price_16h', 170),
            'full' => Setting::get('price_full', 190),
        ];

        return view('students.plans', compact('student', 'plans', 'currentPlan', 'defaultStartDate', 'defaultEndDate', 'prices'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'class_quota' => 'required|in:8,12,16,full',
            'price'       => 'nullable|numeric|min:0',
        ]);

        $currentPlan = $student->currentPlan;
        if ($currentPlan && $currentPlan->status() === 'ok') {
            return back()->with('error', 'No es posible agregar un nuevo plan mientras el alumno tiene un plan activo.');
        }

        $student->plans()->create([
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'class_quota' => $request->class_quota,
            'price'       => $request->price ?: null,
        ]);

        return redirect()->route('students.index')
            ->with('success', 'Plan registrado correctamente.');
    }

    public function destroy(Student $student, \App\Models\StudentPlan $plan)
    {
        $plan->delete();

        return redirect()->route('students.index')
            ->with('success', 'Plan eliminado.');
    }
}
