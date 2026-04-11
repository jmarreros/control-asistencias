@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    @php
        $nombre = strtolower($clase->name);
        $imgCurso = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
                  : (str_contains($nombre, 'bachata') ? 'bachata.jpg'
                  : (str_contains($nombre, 'lady')    ? 'lady.jpg'
                  : null));
    @endphp
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('reports.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div class="flex-1">
            <h1 class="text-xl font-bold text-white">{{ $clase->name }}</h1>
            <p class="text-white/60 text-sm">Reporte del curso</p>
        </div>
        @if($imgCurso)
            <img src="{{ asset('images/' . $imgCurso) }}"
                 class="w-10 h-10 rounded-full object-cover shrink-0" alt="{{ $clase->name }}">
        @endif
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
    <p class="text-xs text-white/50 mb-3">{{ $sessions->count() }} sesión(es) en el período</p>

    @forelse($byStudent as $row)
        <a href="{{ route('reports.clase.student', [$clase, $row['student'], 'from' => $from, 'to' => $to]) }}"
           class="block bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3.5 mb-2 border border-white/15 active:bg-white/20">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-full bg-indigo-500/30 border border-indigo-400/20 flex items-center justify-center text-indigo-300 font-bold text-sm shrink-0 mr-3">
                    {{ strtoupper(substr($row['student']->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white truncate">{{ $row['student']->name }}</p>
                    @if($row['plan'])
                        @php $status = $row['plan']->status(); @endphp
                        <p class="text-xs mt-0.5">
                            <span @class([
                                'font-medium',
                                'text-green-400'  => $status === 'ok',
                                'text-blue-400'   => $status === 'pending',
                                'text-orange-400' => $status === 'exhausted',
                                'text-red-400'    => $status === 'expired',
                            ])>
                                {{ ['full1' => 'Full-1', 'full2' => 'Full-2'][$row['plan']->class_quota] ?? ($row['plan']->class_quota . ' clases') }}
                                ·
                                {{ match($status) {
                                    'ok'        => 'Activo',
                                    'pending'   => 'Por iniciar',
                                    'exhausted' => 'Cuota agotada',
                                    'expired'   => 'Vencido',
                                    default     => ''
                                } }}
                            </span>
                        </p>
                    @else
                        <p class="text-xs text-white/40 mt-0.5">Sin plan</p>
                    @endif
                </div>
                <span class="text-sm font-bold text-green-300 bg-green-500/20 px-3 py-1 rounded-lg shrink-0 ml-2">
                    {{ $row['present'] }} {{ $row['present'] == 1 ? 'clase' : 'clases' }}
                </span>
            </div>
        </a>
    @empty
        <div class="text-center py-12 text-white/40">
            <p class="text-sm">No hay registros en este período.</p>
        </div>
    @endforelse
</div>
@endsection
