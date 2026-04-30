<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index()
    {
        $clases = Clase::withCount('students')->orderBy('name')->get();

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

        $data['schedule'] = $this->parseSchedule($request->input('schedule', []));

        $clase->update($data);

        return redirect()->route('clases.index')
            ->with('success', 'Curso actualizado correctamente.');
    }

    private function parseSchedule(array $input): ?array
    {
        $order = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom'];
        $schedule = collect($order)
            ->filter(fn ($day) => ! empty($input[$day]['start']) && preg_match('/^\d{2}:\d{2}$/', $input[$day]['start']))
            ->mapWithKeys(fn ($day) => [$day => [
                'start' => $input[$day]['start'],
                'end' => preg_match('/^\d{2}:\d{2}$/', $input[$day]['end'] ?? '') ? $input[$day]['end'] : '',
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
