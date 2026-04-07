@extends('layouts.app')

@section('content')
<div class="bg-emerald-600 px-4 pt-6 pb-4">
    <h1 class="text-xl font-bold text-white">Reportes</h1>
    <p class="text-emerald-200 text-sm mt-0.5">Selecciona un curso o alumno</p>
</div>

<div class="p-4">
    {{-- Ganancias --}}
    <a href="{{ route('reports.earnings') }}"
       class="flex items-center justify-between bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl px-4 py-3.5 mb-5 shadow-sm active:bg-emerald-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-emerald-800 dark:text-emerald-300">Ganancias</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-500">Ingresos por planes del período</p>
            </div>
        </div>
        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Asistencias por curso</h2>
    <div class="space-y-2 mb-6">
        @forelse($clases as $clase)
            <a href="{{ route('reports.clase', $clase) }}"
               class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl px-4 py-3.5 shadow-sm border border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{!! $clase->scheduleText() !!}</p>
                    @endif
                </div>
                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @empty
            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No hay clases.</p>
        @endforelse
    </div>

    <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Asistencias por alumno</h2>
    <div class="space-y-2">
        @forelse($students as $student)
            <a href="{{ route('reports.student', $student) }}"
               class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-xl px-4 py-3.5 shadow-sm border border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
                <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm shrink-0">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <p class="flex-1 font-medium text-gray-900 dark:text-white">{{ $student->name }}</p>
                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @empty
            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No hay alumnos.</p>
        @endforelse
    </div>
</div>
@endsection
