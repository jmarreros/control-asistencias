<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentPlanController extends Controller
{
    public function index(Student $student)
    {
        $plans = $student->plans()->withTrashed()->get();
        $currentPlan = $plans->whereNull('deleted_at')->first();

        $startDefault = now()->isWeekend()
            ? now()->nextWeekday()
            : now();

        $defaultStartDate = $startDefault->toDateString();
        $endDefault = $startDefault->copy();
        $count = 0;
        while (true) {
            if (!in_array($endDefault->dayOfWeek, [0, 6])) $count++;
            if ($count === 20) break;
            $endDefault->addDay();
        }
        $defaultEndDate = $endDefault->toDateString();

        $prices = [
            '8'    => (float) Setting::get('price_8h',   120),
            '12'   => (float) Setting::get('price_12h',  150),
            '16'   => (float) Setting::get('price_16h',  170),
            '24'    => (float) Setting::get('price_24h',   200),
            'full1' => (float) Setting::get('price_full1', 190),
            'full2' => (float) Setting::get('price_full2', 210),
        ];

        $promos = collect([
            'promo_10'  => ['label' => 'Descuento 10%',  'discount' => 10],
            'promo_20'  => ['label' => 'Descuento 20%',  'discount' => 20],
            'promo_30'  => ['label' => 'Descuento 30%',  'discount' => 30],
            'promo_2x1' => ['label' => 'Promoción 2x1',  'discount' => 50],
        ])->filter(fn($_, $key) => (bool) Setting::get($key, 0));

        return view('students.plans', compact('student', 'plans', 'currentPlan', 'defaultStartDate', 'defaultEndDate', 'prices', 'promos'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'class_quota' => 'required|in:8,12,16,24,full1,full2',
            'price'       => 'nullable|numeric|min:0',
            'promotion'   => 'nullable|in:promo_10,promo_20,promo_30,promo_2x1',
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
            'promotion'   => $request->promotion ?: null,
        ]);

        return redirect()->route('students.index')
            ->with('success', 'Plan registrado correctamente.');
    }

    public function destroy(Student $student, \App\Models\StudentPlan $plan)
    {
        $plan->delete(); // soft delete — queda en historial

        return redirect()->route('students.plans.index', $student)
            ->with('success', 'Plan cancelado y movido al historial.');
    }
}
