@extends('layouts.app')

@section('content')
<div class="bg-emerald-600 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('reports.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-white">{{ $clase->name }}</h1>
            <p class="text-emerald-200 text-sm">Reporte del curso</p>
        </div>
    </div>

    <form method="GET" class="flex gap-2 items-end">
        <div class="flex-1">
            <label class="text-xs text-emerald-200 block mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-emerald-700 text-white border border-emerald-500 focus:outline-none">
        </div>
        <div class="flex-1">
            <label class="text-xs text-emerald-200 block mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-emerald-700 text-white border border-emerald-500 focus:outline-none">
        </div>
        <button type="submit"
                class="bg-white text-emerald-700 font-semibold text-sm px-4 py-2 rounded-lg whitespace-nowrap">
            Ver
        </button>
    </form>
</div>

<div class="p-4">
    @php
        $totalPresent = $byStudent->sum('present');
        $totalPossible = $byStudent->sum('total');
    @endphp
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
        {{ $sessions->count() }} sesión(es) · <span class="font-medium text-gray-700 dark:text-gray-300">{{ $totalPresent }} asistentes / {{ $totalPossible }} total</span>
    </p>

    @forelse($byStudent as $row)
        @php
            $rateColor = $row['rate'] >= 80
                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                : ($row['rate'] >= 60
                    ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
                    : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400');
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl px-4 py-3.5 mb-2 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center">
            <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm shrink-0 mr-3">
                {{ strtoupper(substr($row['student']->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $row['student']->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['present'] }} / {{ $row['total'] }} sesiones</p>
            </div>
            <span class="text-sm font-bold px-3 py-1 rounded-lg {{ $rateColor }}">
                {{ $row['rate'] }}%
            </span>
        </div>
    @empty
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No hay registros en este período.</p>
        </div>
    @endforelse
</div>
@endsection
