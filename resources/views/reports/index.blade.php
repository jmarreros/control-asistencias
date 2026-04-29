@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
        <h1 class="text-xl font-bold text-white">Reportes</h1>
    </div>
    <p class="text-white/60 text-sm mt-0.5">Selecciona un curso o alumno</p>
</div>

<div class="p-4">
    {{-- Ganancias --}}
    @if($showEarnings)
    <div class="mb-5">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Ganancias</h2>
        <a href="{{ route('reports.earnings') }}"
           class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white/15 active:bg-white/20">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-400/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-medium text-white">Ganancias</p>
                <p class="text-xs text-white/50">Reporte de ingresos por período</p>
            </div>
            <svg class="w-5 h-5 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    @endif

    {{-- Exportar alumnos --}}
    <div class="flex justify-end mb-5">
        <a href="{{ route('reports.students.export') }}"
           data-turbo="false"
           class="flex items-center gap-2 bg-emerald-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar alumnos
        </a>
    </div>

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Asistencias por curso</h2>
    <div class="space-y-2 mb-6">
        @forelse($clases as $clase)
            @php
                $nombre = strtolower($clase->name);
                $imgCurso = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
                          : (str_contains($nombre, 'bachata') ? 'bachata.jpg'
                          : (str_contains($nombre, 'lady')    ? 'lady.jpg'
                          : null));
            @endphp
            <a href="{{ route('reports.clase', $clase) }}"
               class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white/15 active:bg-white/20">
                @if($imgCurso)
                    <img src="{{ asset('images/' . $imgCurso) }}"
                         class="w-10 h-10 rounded-full object-cover shrink-0" alt="{{ $clase->name }}">
                @endif
                <div class="flex-1">
                    <p class="font-medium text-white">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-xs text-white/50">{!! $clase->scheduleText() !!}</p>
                    @endif
                </div>
                <svg class="w-5 h-5 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
