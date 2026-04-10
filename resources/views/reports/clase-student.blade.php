@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('reports.clase', [$clase, 'from' => $from, 'to' => $to]) }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-white truncate">{{ $student->name }}</h1>
            <p class="text-white/60 text-sm">{{ $clase->name }}</p>
        </div>
    </div>
</div>

<div class="p-4">
    @php $attended = $attendances->where('present', true); @endphp

    <p class="text-xs text-white/50 mb-3">{{ $attended->count() }} {{ $attended->count() == 1 ? 'clase asistida' : 'clases asistidas' }} en el período</p>

    @forelse($attended as $att)
        <div class="rounded-xl px-4 py-3 mb-2 border border-white/20">
            <p class="text-sm text-white">
                {{ \Carbon\Carbon::parse($att->date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
            </p>
        </div>
    @empty
        <div class="text-center py-10 text-white/40">
            <p class="text-sm">No hay asistencias registradas en este período.</p>
        </div>
    @endforelse
</div>
@endsection
