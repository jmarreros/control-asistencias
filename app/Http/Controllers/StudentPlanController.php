<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPlanController extends Controller
{
    public function index(Student $student)
    {
        $plans = $student->plans()->withTrashed()->latest('start_date')->get();
        $nonDeleted = $plans->whereNull('deleted_at')->values();

        // Plan corriendo actualmente (ok, exhausted, expired)
        $currentPlan = $nonDeleted->first(fn ($p) => in_array($p->status(), ['ok', 'exhausted', 'expired']));
        // Plan futuro ya registrado (pending)
        $nextPlan = $nonDeleted->first(fn ($p) => $p->status() === 'pending');
        // Si no hay ninguno en esos estados, mostrar el primero disponible
        if (! $currentPlan && ! $nextPlan) {
            $currentPlan = $nonDeleted->first();
        }

        // Fecha de inicio sugerida: día siguiente al fin del plan más reciente activo
        $latestEnd = $nonDeleted->max('end_date');
        if ($latestEnd && $latestEnd >= now()->toDateString()) {
            $startDefault = Carbon::parse($latestEnd)->addDay();
            if ($startDefault->isWeekend()) {
                $startDefault = $startDefault->nextWeekday();
            }
        } else {
            $startDefault = now()->isWeekend() ? now()->nextWeekday() : now();
        }

        $defaultStartDate = $startDefault->toDateString();
        $endDefault = $startDefault->copy();
        $count = 0;
        while (true) {
            if (! in_array($endDefault->dayOfWeek, [0, 6])) {
                $count++;
            }
            if ($count === 20) {
                break;
            }
            $endDefault->addDay();
        }
        $defaultEndDate = $endDefault->toDateString();

        $prices = [
            '8' => (float) Setting::get('price_8h', 120),
            '12' => (float) Setting::get('price_12h', 150),
            '16' => (float) Setting::get('price_16h', 170),
            '24' => (float) Setting::get('price_24h', 200),
            'full1' => (float) Setting::get('price_full1', 190),
            'full2' => (float) Setting::get('price_full2', 210),
        ];

        $promos = collect([
            'promo_10' => ['label' => 'Descuento 10%',  'discount' => 10],
            'promo_20' => ['label' => 'Descuento 20%',  'discount' => 20],
            'promo_30' => ['label' => 'Descuento 30%',  'discount' => 30],
            'promo_2x1' => ['label' => 'Promoción 2x1',  'discount' => 50],
        ])->filter(fn ($_, $key) => (bool) Setting::get($key, 0));

        $clases = Clase::where('active', true)->orderBy('name')->get();
        $enrolledIds = $student->clases()->pluck('clases.id')->toArray();
        if (empty($enrolledIds) && $nonDeleted->isEmpty()) {
            $enrolledIds = $clases->pluck('id')->toArray();
        }

        return view('students.plans', compact('student', 'plans', 'currentPlan', 'nextPlan', 'defaultStartDate', 'defaultEndDate', 'prices', 'promos', 'clases', 'enrolledIds'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'class_quota' => 'required|in:8,12,16,24,full1,full2',
            'price' => 'nullable|numeric|min:0',
            'promotion' => 'nullable|in:promo_10,promo_20,promo_30,promo_2x1',
            'clases' => 'nullable|array',
            'clases.*' => 'exists:clases,id',
        ]);

        $overlap = $student->plans()
            ->where('start_date', '<=', $request->end_date)
            ->where('end_date', '>=', $request->start_date)
            ->exists();
        if ($overlap) {
            return back()->with('error', 'Las fechas del nuevo plan se solapan con un plan ya registrado.');
        }

        $remainingMap = ['8' => 8, '12' => 12, '16' => 16, '24' => 24, 'full1' => null, 'full2' => null];

        DB::transaction(function () use ($request, $student, $remainingMap) {
            $student->plans()->create([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'class_quota' => $request->class_quota,
                'classes_remaining' => $remainingMap[$request->class_quota],
                'price' => $request->price ?: null,
                'promotion' => $request->promotion ?: null,
            ]);

            if ($request->has('clases')) {
                $syncData = collect($request->clases)->mapWithKeys(fn ($id) => [
                    $id => ['enrolled_at' => $request->start_date],
                ])->toArray();
                $student->clases()->sync($syncData);
            }
        });

        return redirect()->route('students.index')
            ->with('success', 'Plan registrado correctamente.');
    }

    public function destroy(Student $student, StudentPlan $plan)
    {
        abort_if($plan->student_id !== $student->id, 403);

        $plan->delete();

        return redirect()->route('students.plans.index', $student)
            ->with('success', 'Plan cancelado y movido al historial.');
    }
}
