@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-2">
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
        <h1 class="text-xl font-bold text-white">Tomar Asistencia</h1>
    </div>
    <p class="text-white/60 text-sm mt-0.5">Selecciona un curso</p>
</div>

<div class="p-4 space-y-3">
    @forelse($clases as $clase)
        @php $isToday = is_array($clase->schedule) && isset($clase->schedule[$todayKey]); @endphp
        @php
            $nombre = strtolower($clase->name);
            $img = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
                 : (str_contains($nombre, 'bachata') ? 'bachata.jpg'
                 : (str_contains($nombre, 'lady')    ? 'lady.jpg'
                 : null));
        @endphp
        <div class="bg-white/10 backdrop-blur-sm border rounded-xl overflow-hidden
                    {{ $isToday ? 'border-teal-400/40' : 'border-white/15' }}">
            <a href="{{ route('attendance.take', $clase) }}"
               class="flex items-center p-4 active:bg-white/10">
                @if($img)
                    <img src="{{ asset('images/' . $img) }}"
                         class="w-10 h-10 rounded-full object-cover shrink-0 mr-3" alt="{{ $clase->name }}">
                @endif
                <div class="flex-1">
                    <p class="font-semibold text-white text-lg">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-sm text-white/60">{!! $clase->scheduleText() !!}</p>
                    @endif
                    <p class="text-xs text-white/40 mt-1">{{ $clase->students_count }} alumno{{ $clase->students_count != 1 ? 's' : '' }}</p>
                </div>
                <div class="text-right ml-3 shrink-0">
                    @if($isToday)
                        @php
                            $todaySlot = $clase->schedule[$todayKey];
                            $start = is_array($todaySlot) ? $todaySlot['start'] : $todaySlot;
                            $end   = is_array($todaySlot) ? ($todaySlot['end'] ?? '') : '';
                        @endphp
                        <span class="block bg-teal-500 text-white text-sm font-semibold px-4 py-2 rounded-lg text-center">
                            Hoy
                        </span>
                        <span class="block text-teal-300 text-sm font-bold mt-1 text-center">
                            {{ $start }}{{ $end ? '–'.$end : '' }}
                        </span>
                    @endif
                </div>
            </a>
        </div>
    @empty
        <div class="text-center py-12 text-white/40">
            <p class="text-sm">No hay clases activas.</p>
            <a href="{{ route('clases.create') }}" class="text-teal-400 text-sm font-medium mt-1 inline-block">
                Crear primer curso →
            </a>
        </div>
    @endforelse
</div>
@endsection
