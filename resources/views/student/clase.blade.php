@extends('layouts.student')

@section('content')

{{-- Header --}}
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('student.dashboard') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('student.dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-white truncate">{{ $clase->name }}</h1>
            <p class="text-white/50 text-xs">{{ $student->name }}</p>
        </div>
    </div>

    @if($plan)
        <div class="mt-3 flex items-center gap-2 text-xs text-white/40">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Plan {{ $plan->class_quota === 'full' ? 'Full' : $plan->class_quota . ' clases' }}
            &middot;
            {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM') }}
            –
            {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
        </div>
    @endif
</div>

{{-- Resumen --}}
<div class="px-4 pt-4 pb-2">
    @php
        $rate = $total > 0 ? round($present / $total * 100) : null;
        $rateClass = match(true) {
            $rate === null => 'bg-white/10 text-white/50',
            $rate >= 80   => 'bg-green-500/20 text-green-300 border-green-500/30',
            $rate >= 60   => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
            default       => 'bg-red-500/20 text-red-300 border-red-500/30',
        };
    @endphp
    <div class="flex gap-3">
        <div class="flex-1 bg-white/10 border border-white/15 rounded-xl p-3 text-center backdrop-blur-sm">
            <p class="text-2xl font-bold text-white">{{ $present }}</p>
            <p class="text-xs text-white/50 mt-0.5">Presentes</p>
        </div>
        <div class="flex-1 bg-white/10 border border-white/15 rounded-xl p-3 text-center backdrop-blur-sm">
            <p class="text-2xl font-bold text-white">{{ $total - $present }}</p>
            <p class="text-xs text-white/50 mt-0.5">Ausentes</p>
        </div>
        <div class="flex-1 border rounded-xl p-3 text-center backdrop-blur-sm {{ $rateClass }}">
            <p class="text-2xl font-bold">{{ $rate !== null ? $rate . '%' : '—' }}</p>
            <p class="text-xs mt-0.5 opacity-70">Asistencia</p>
        </div>
    </div>
</div>

{{-- Detalle día a día --}}
<div class="p-4">
    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Detalle por día</h2>
    <div class="space-y-2">
        @forelse($attendances as $att)
            <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="font-medium text-white text-sm">
                        {{ \Carbon\Carbon::parse($att->date)->locale('es')->isoFormat('dddd') }}
                    </p>
                    <p class="text-xs text-white/50">
                        {{ \Carbon\Carbon::parse($att->date)->locale('es')->isoFormat('D [de] MMMM YYYY') }}
                    </p>
                </div>
                @if($att->present)
                    <span class="text-xs font-semibold text-green-300 bg-green-500/20 px-3 py-1 rounded-full">
                        Presente
                    </span>
                @else
                    <span class="text-xs font-semibold text-red-300 bg-red-500/20 px-3 py-1 rounded-full">
                        Ausente
                    </span>
                @endif
            </div>
        @empty
            <div class="text-center py-10 text-white/40">
                <p class="text-sm">No hay registros en este período.</p>
            </div>
        @endforelse
    </div>
</div>

@endsection
