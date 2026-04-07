@extends('layouts.app')

@section('content')
<div class="bg-indigo-600 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-white">Editar Alumno</h1>
</div>

{{-- Datos del alumno --}}
<form id="form-update" method="POST" action="{{ route('students.update', $student) }}" class="p-4 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo *</label>
        <input type="text" name="name" value="{{ old('name', $student->name) }}" required
               class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono / WhatsApp</label>
        <input type="tel" name="phone" value="{{ old('phone', $student->phone) }}"
               class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                      focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes', $student->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" class="sr-only peer"
                   {{ old('active', $student->active) ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 rounded-full peer
                        peer-checked:after:translate-x-full peer-checked:after:border-white
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                        after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                        peer-checked:bg-indigo-600"></div>
        </label>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Alumno activo</span>
    </div>
</form>

{{-- Plan actual --}}
<div class="p-4 pt-0 mb-2">
<div class="border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Plan de clases</span>
        <a href="{{ route('students.plans.index', $student) }}"
           class="text-xs font-medium text-indigo-600 dark:text-indigo-400">
            Gestionar →
        </a>
    </div>
    @if($currentPlan)
        @php $status = $currentPlan->status(); @endphp
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $currentPlan->class_quota === 'full' ? 'Full (ilimitado)' : $currentPlan->class_quota . ' clases' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($currentPlan->start_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    →
                    {{ \Carbon\Carbon::parse($currentPlan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
                </p>
            </div>
            <span @class([
                'text-xs font-semibold px-2 py-1 rounded-full',
                'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' => $status === 'ok',
                'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400'     => $status === 'pending',
                'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400' => $status === 'exhausted',
                'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400'         => $status === 'expired',
            ])>
                {{ match($status) {
                    'ok'        => 'Activo',
                    'pending'   => 'Por iniciar',
                    'exhausted' => 'Clases agotadas',
                    'expired'   => 'Vencido',
                    default     => ''
                } }}
            </span>
        </div>
    @else
        <p class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">Sin plan registrado.</p>
    @endif
</div>
</div>

{{-- Botones --}}
<div class="px-4 space-y-2 mb-6">
    <button type="submit" form="form-update"
            class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-lg">
        Guardar cambios
    </button>

    @if($student->active)
        <form method="POST" action="{{ route('students.destroy', $student) }}"
              onsubmit="return confirm('¿Desactivar a {{ $student->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full text-red-500 border border-red-200 dark:border-red-800 font-medium py-3 rounded-xl text-sm">
                Desactivar alumno
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('students.update', $student) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="name" value="{{ $student->name }}">
            <input type="hidden" name="phone" value="{{ $student->phone }}">
            <input type="hidden" name="notes" value="{{ $student->notes }}">
            <input type="hidden" name="active" value="1">
            <button type="submit" class="w-full text-green-600 border border-green-200 dark:border-green-800 font-medium py-3 rounded-xl text-sm">
                Activar alumno
            </button>
        </form>
    @endif
</div>

@endsection
