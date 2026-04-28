<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPlan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $monthlyPlans = StudentPlan::whereYear('start_date', now()->year)
                            ->whereMonth('start_date', now()->month)
                            ->count();

        Setting::preload(['notify_days_before', 'notify_classes_remaining']);
        $daysBefore       = (int) Setting::get('notify_days_before', 3);
        $classesThreshold = (int) Setting::get('notify_classes_remaining', 1);

        $today    = today()->toDateString();
        $students = Student::with('currentPlan')->get();

        $activeStudents = $students->filter(function ($s) use ($today) {
            $plan = $s->currentPlan;
            if (!$plan) return false;
            return $plan->start_date <= $today
                && $plan->end_date   >= $today
                && ($plan->classes_remaining === null || $plan->classes_remaining > 0);
        })->count();

        $expiringCount = $students->filter(function ($s) use ($daysBefore, $classesThreshold) {
            $plan = $s->currentPlan;
            if (!$plan || !in_array($plan->status(), ['ok', 'exhausted'])) return false;
            $remaining = $plan->classesRemaining();
            $daysLeft  = (int) now()->startOfDay()->diffInDays(Carbon::parse($plan->end_date), false);
            return ($remaining !== null && $remaining <= $classesThreshold) || $daysLeft <= $daysBefore;
        })->count();

        $dayMap   = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $todayKey = $dayMap[now()->dayOfWeek];

        $all = Clase::where('active', true)->withCount('students')->get();

        $todayClases = $all
            ->filter(fn($c) => is_array($c->schedule) && isset($c->schedule[$todayKey]))
            ->sortBy(fn($c) => is_array($c->schedule[$todayKey]) ? $c->schedule[$todayKey]['start'] : $c->schedule[$todayKey])
            ->values();

        $otherClases = $all
            ->filter(fn($c) => !is_array($c->schedule) || !isset($c->schedule[$todayKey]))
            ->sortBy('name')
            ->values();

        $activeClases = $todayClases->merge($otherClases);

        return view('dashboard.index', compact(
            'activeStudents',
            'monthlyPlans',
            'expiringCount',
            'activeClases',
            'todayKey'
        ));
    }
}
