@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-2">
        <img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo">
        <h1 class="text-xl font-bold text-white">Reportes</h1>
    </div>
    <p class="text-white/60 text-sm mt-0.5">Selecciona un curso o alumno</p>
</div>

<div class="p-4">
    {{-- Ganancias --}}
    <a href="{{ route('reports.earnings') }}"
       class="flex items-center justify-between bg-emerald-500/15 border border-emerald-400/25 rounded-xl px-4 py-3.5 mb-5 backdrop-blur-sm active:bg-emerald-500/25">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-emerald-500/25 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-emerald-300">Ganancias</p>
                <p class="text-xs text-emerald-400/70">Ingresos por planes del período</p>
            </div>
        </div>
        <svg class="w-5 h-5 text-emerald-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Asistencias por curso</h2>
    <div class="space-y-2 mb-6">
        @forelse($clases as $clase)
            <a href="{{ route('reports.clase', $clase) }}"
               class="flex items-center justify-between bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white/15 active:bg-white/20">
                <div>
                    <p class="font-medium text-white">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-xs text-white/50">{!! $clase->scheduleText() !!}</p>
                    @endif
                </div>
                <svg class="w-5 h-5 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @empty
            <p class="text-sm text-white/40 text-center py-4">No hay clases.</p>
        @endforelse
    </div>

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Asistencias por alumno</h2>
    <div class="space-y-2">
        @forelse($students as $student)
            <a href="{{ route('reports.student', $student) }}"
               class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white/15 active:bg-white/20">
                <div class="w-9 h-9 rounded-full bg-indigo-500/30 border border-indigo-400/20 flex items-center justify-center text-indigo-300 font-bold text-sm shrink-0">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <p class="flex-1 font-medium text-white">{{ $student->name }}</p>
                <svg class="w-5 h-5 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @empty
            <p class="text-sm text-white/40 text-center py-4">No hay alumnos.</p>
        @endforelse
    </div>
</div>
@endsection
