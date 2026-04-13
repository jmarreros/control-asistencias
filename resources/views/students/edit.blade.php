@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
    <h1 class="text-xl font-bold text-white">Editar Alumno</h1>
</div>

<form id="form-update" method="POST" action="{{ route('students.update', $student) }}" class="p-4 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Nombre completo *</label>
        <input type="text" name="name" value="{{ old('name', $student->name) }}" required
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      @error('name') border-red-400 @enderror">
        @error('name')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">DNI</label>
        <input type="text" name="dni" value="{{ old('dni', $student->dni) }}" maxlength="20" inputmode="numeric"
               placeholder="Ej. 12345678"
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      @error('dni') border-red-400 @enderror">
        @error('dni')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">WhatsApp *</label>
        <input type="tel" name="phone" value="{{ old('phone', $student->phone ?? '+51') }}" required
               placeholder="+51 987 654 321"
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      @error('phone') border-red-400 @enderror">
        @error('phone')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Notas</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white
                         bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15">{{ old('notes', $student->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" class="sr-only peer"
                   {{ old('active', $student->active) ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-white/20 rounded-full peer
                        peer-checked:after:translate-x-full peer-checked:after:border-white
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                        after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                        peer-checked:bg-indigo-600"></div>
        </label>
        <span class="text-sm font-medium text-white/80">Alumno activo</span>
    </div>
</form>

{{-- Plan actual --}}
<div class="p-4 pt-0 mb-2">
<div class="border border-white/15 rounded-xl overflow-hidden backdrop-blur-sm">
    <div class="flex items-center justify-between px-4 py-3 bg-white/10">
        <span class="text-sm font-semibold text-white/80">Plan de clases</span>
        <a href="{{ route('students.plans.index', $student) }}"
           class="text-xs font-medium text-indigo-400">
            Gestionar →
        </a>
    </div>
    @if($currentPlan)
        @php $status = $currentPlan->status(); @endphp
        <div class="px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-white">
                    {{ ['full1' => 'Full-1 (ilimitado)', 'full2' => 'Full-2 (ilimitado)'][$currentPlan->class_quota] ?? ($currentPlan->class_quota . ' clases') }}
                </p>
                <p class="text-xs text-white/50 mt-0.5">
                    {{ \Carbon\Carbon::parse($currentPlan->start_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    →
                    {{ \Carbon\Carbon::parse($currentPlan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
                </p>
            </div>
            <span @class([
                'text-xs font-semibold px-2 py-1 rounded-full',
                'bg-green-500/20 text-green-300'   => $status === 'ok',
                'bg-blue-500/20 text-blue-300'     => $status === 'pending',
                'bg-orange-500/20 text-orange-300' => $status === 'exhausted',
                'bg-red-500/20 text-red-300'       => $status === 'expired',
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
        <p class="px-4 py-3 text-sm text-white/40">Sin plan registrado.</p>
    @endif
</div>
</div>

{{-- Botones --}}
<div class="px-4 space-y-2 mb-6">
    <button type="submit" form="form-update"
            class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-lg">
        Guardar cambios
    </button>

    @if($student->active)
        <form method="POST" action="{{ route('students.destroy', $student) }}"
              onsubmit="return confirm('¿Desactivar a {{ $student->name }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full text-red-400 border border-red-500/30 font-medium py-3 rounded-xl text-sm">
                Desactivar alumno
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('students.update', $student) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="name" value="{{ $student->name }}">
            <input type="hidden" name="dni" value="{{ $student->dni }}">
            <input type="hidden" name="phone" value="{{ $student->phone }}">
            <input type="hidden" name="notes" value="{{ $student->notes }}">
            <input type="hidden" name="active" value="1">
            <button type="submit" class="w-full text-green-400 border border-green-500/30 font-medium py-3 rounded-xl text-sm">
                Activar alumno
            </button>
        </form>
    @endif
</div>

@endsection
