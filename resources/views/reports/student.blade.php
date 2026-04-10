@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('reports.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div>
            <h1 class="text-xl font-bold text-white">{{ $student->name }}</h1>
            <p class="text-white/60 text-sm">Historial de asistencias</p>
        </div>
    </div>

    <form method="GET" class="flex gap-2 items-end">
        <div class="flex-1">
            <label class="text-xs text-white/50 block mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-white/15 text-white border border-white/20 focus:outline-none">
        </div>
        <div class="flex-1">
            <label class="text-xs text-white/50 block mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-white/15 text-white border border-white/20 focus:outline-none">
        </div>
        <button type="submit"
                class="bg-green-500 text-white font-semibold text-sm px-4 py-2 rounded-lg whitespace-nowrap">
            Ver
        </button>
    </form>
</div>

<div class="p-4">
    @if($byClase->isNotEmpty())
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Resumen por curso</h2>
        <div class="space-y-2 mb-6">
            @foreach($byClase as $row)
                @php
                    $rateColor = $row['rate'] >= 80
                        ? 'bg-green-500/20 text-green-300'
                        : ($row['rate'] >= 60
                            ? 'bg-yellow-500/20 text-yellow-300'
                            : 'bg-red-500/20 text-red-300');
                @endphp
                <div class="rounded-xl px-4 py-3 border border-white/20 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-white">{{ $row['clase']->name }}</p>
                        <p class="text-xs text-white/50">{{ $row['present'] }} / {{ $row['total'] }} clases</p>
                    </div>
                    <span class="text-sm font-bold px-3 py-1 rounded-lg {{ $rateColor }}">
                        {{ $row['rate'] }}%
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Detalle</h2>
    <div class="space-y-2">
        @forelse($attendances as $att)
            <div class="rounded-xl px-4 py-3 border border-white/20 flex items-center justify-between">
                <div>
                    <p class="font-medium text-white text-sm">{{ $att->clase->name }}</p>
                    <p class="text-xs text-white/50">
                        {{ \Carbon\Carbon::parse($att->date)->locale('es')->isoFormat('ddd D MMM YYYY') }}
                    </p>
                </div>
                @if($att->present)
                    <span class="text-xs font-semibold text-green-300 bg-green-500/20 px-3 py-1 rounded-full">Presente</span>
                @else
                    <span class="text-xs font-semibold text-red-300 bg-red-500/20 px-3 py-1 rounded-full">Ausente</span>
                @endif
            </div>
        @empty
            <div class="text-center py-8 text-white/40">
                <p class="text-sm">No hay registros en este período.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
