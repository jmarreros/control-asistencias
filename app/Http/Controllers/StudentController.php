<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('currentPlan')->where('active', true)->orderBy('name')->get();

        Setting::preload(['notify_days_before', 'notify_classes_remaining', 'notify_message', 'notify_expired_message']);

        $daysBefore = (int) Setting::get('notify_days_before', 3);
        $classesThreshold = (int) Setting::get('notify_classes_remaining', 1);
        $messageTemplate = Setting::get('notify_message', 'Hola {nombre}, tu plan está por vencer. Te quedan {clases} clase(s) y vence el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!');
        $expiredTemplate = Setting::get('notify_expired_message', 'Hola {nombre}, tu plan venció el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!');

        $students->each(function ($s) use ($daysBefore, $classesThreshold, $messageTemplate, $expiredTemplate) {
            $plan = $s->currentPlan;
            $s->planStatus = $plan?->status() ?? 'no_plan';
            $s->isExpiring = false;
            $s->waUrl = null;
            $s->waUrlExpired = null;
            $s->planEndDate = $plan ? Carbon::parse($plan->end_date)->format('d/m/Y') : null;
            $s->planClassesLeft = $plan ? $plan->classesRemaining() : null;

            if (! $plan) {
                return;
            }

            $firstName = explode(' ', trim($s->name))[0];
            $fecha = Carbon::parse($plan->end_date)->format('d/m/Y');
            $phone = $s->phone ? preg_replace('/\D/', '', $s->phone) : null;
            if ($phone && strlen($phone) === 9) {
                $phone = '51'.$phone;
            }

            $status = $plan->status();

            // Por vencer
            if (in_array($status, ['ok', 'exhausted'])) {
                $remaining = $plan->classesRemaining();
                $daysLeft = (int) now()->startOfDay()->diffInDays(Carbon::parse($plan->end_date), false);

                if (($remaining !== null && $remaining <= $classesThreshold) || $daysLeft <= $daysBefore) {
                    $s->isExpiring = true;

                    if ($phone) {
                        $clases = $remaining !== null ? $remaining : 'ilimitadas';
                        $message = str_replace(['{nombre}', '{clases}', '{fecha}'], [$firstName, $clases, $fecha], $messageTemplate);
                        $s->waUrl = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
                    }
                }
            }

            // Vencido
            if (in_array($status, ['expired', 'exhausted']) && $phone) {
                $message = str_replace(['{nombre}', '{fecha}'], [$firstName, $fecha], $expiredTemplate);
                $s->waUrlExpired = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
            }
        });

        return view('students.index', compact('students'));
    }

    public function create()
    {
        $existingStudents = Student::orderBy('name')
            ->get(['id', 'name', 'dni', 'phone', 'active'])
            ->map(fn ($s) => array_merge($s->toArray(), [
                'edit_url' => route('students.edit', $s->id),
            ]));

        return view('students.create', compact('existingStudents'));
    }

    public function store(Request $request)
    {
        if ($request->filled('inactive_student_id')) {
            $student = Student::where('id', (int) $request->inactive_student_id)
                ->where('active', false)
                ->firstOrFail();

            $student->update(['active' => true]);

            return redirect()->route('students.plans.index', $student)
                ->with('success', 'Alumno reactivado correctamente. Ahora agrega su plan de clases.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (count(array_filter(explode(' ', trim($value)))) < 2) {
                    $fail('El nombre debe contener al menos dos palabras.');
                }
            }],
            'dni' => 'required|string|min:8|max:12|unique:students,dni',
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'notes' => 'nullable|string',
        ], [
            'dni.unique' => 'El documento de identidad ingresado ya está registrado.',
            'dni.min'    => 'El documento debe tener al menos 8 caracteres.',
            'dni.max'    => 'El documento no puede tener más de 12 caracteres.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.min' => 'El teléfono debe tener al menos 8 caracteres.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'phone.regex' => 'El teléfono solo puede contener dígitos, espacios, guiones o paréntesis.',
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
        if ($request->boolean('_activate_only')) {
            $student->update(['active' => true]);
            return redirect()->route('students.index')
                ->with('success', 'Alumno activado correctamente.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (count(array_filter(explode(' ', trim($value)))) < 2) {
                    $fail('El nombre debe contener al menos dos palabras.');
                }
            }],
            'dni' => ['required', 'string', 'min:8', 'max:12', 'unique:students,dni,'.$student->id],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'notes' => 'nullable|string',
            'active' => 'boolean',
        ], [
            'dni.required' => 'El documento de identidad es obligatorio.',
            'dni.unique'   => 'El documento de identidad ingresado ya está registrado.',
            'dni.min'      => 'El documento debe tener al menos 8 caracteres.',
            'dni.max'      => 'El documento no puede tener más de 12 caracteres.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.min' => 'El teléfono debe tener al menos 8 caracteres.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'phone.regex' => 'El teléfono solo puede contener dígitos, espacios, guiones o paréntesis.',
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
