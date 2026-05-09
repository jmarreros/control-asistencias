<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index()
    {
        $clases = Clase::withCount(['students' => fn ($q) => $q->where('active', true)])->orderBy('name')->get();

        return view('clases.index', compact('clases'));
    }

    public function create()
    {
        return view('clases.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($err = $this->validateScheduleInput($request->input('schedule', []))) {
            return back()->withErrors(['schedule' => $err])->withInput();
        }

        $data['schedule'] = $this->parseSchedule($request->input('schedule', []));

        Clase::create($data);

        return redirect()->route('clases.index')
            ->with('success', 'Curso creado correctamente.');
    }

    public function edit(Clase $clase)
    {
        return view('clases.edit', compact('clase'));
    }

    public function update(Request $request, Clase $clase)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        if ($err = $this->validateScheduleInput($request->input('schedule', []))) {
            return back()->withErrors(['schedule' => $err])->withInput();
        }

        $data['schedule'] = $this->parseSchedule($request->input('schedule', []));

        $clase->update($data);

        return redirect()->route('clases.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    private function validateScheduleInput(array $schedule): ?string
    {
        $days   = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        $filled = array_filter($days, fn ($d) =>
            isset($schedule[$d]['start']) &&
            is_string($schedule[$d]['start']) &&
            preg_match('/^\d{2}:\d{2}$/', $schedule[$d]['start'])
        );

        if (empty($filled)) {
            return 'Selecciona al menos un día con horario.';
        }
        foreach ($filled as $day) {
            $end = $schedule[$day]['end'] ?? null;
            if (! is_string($end) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
                return 'Todos los días seleccionados deben tener hora de inicio y hora de fin.';
            }
            if ($end <= $schedule[$day]['start']) {
                return 'La hora de fin debe ser mayor a la hora de inicio.';
            }
        }
        return null;
    }

    private function parseSchedule(array $input): ?array
    {
        $order    = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        $schedule = collect($order)
            ->filter(fn ($day) =>
                isset($input[$day]['start']) &&
                is_string($input[$day]['start']) &&
                preg_match('/^\d{2}:\d{2}$/', $input[$day]['start'])
            )
            ->mapWithKeys(fn ($day) => [$day => [
                'start' => $input[$day]['start'],
                'end'   => (isset($input[$day]['end']) && is_string($input[$day]['end']) && preg_match('/^\d{2}:\d{2}$/', $input[$day]['end']))
                    ? $input[$day]['end']
                    : '',
            ]])
            ->toArray();

        return ! empty($schedule) ? $schedule : null;
    }

    public function destroy(Clase $clase)
    {
        $clase->update(['active' => false]);

        return redirect()->route('clases.index')
            ->with('success', 'Curso desactivado.');
    }
}
