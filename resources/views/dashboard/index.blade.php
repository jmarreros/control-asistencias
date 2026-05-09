@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6 pt-2">
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
            <div>
                <h1 class="text-xl font-bold text-white">Salsa Latin Motion</h1>
                <p class="text-white/60 text-sm">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <a href="{{ route('settings.edit') }}" class="text-white/50 p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-white/50 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-indigo-500/20 border border-indigo-400/20 rounded-xl p-3 text-center backdrop-blur-sm">
            <p class="text-2xl font-bold text-indigo-300">{{ $activeStudents }}</p>
            <p class="text-xs text-white/60 mt-0.5">Con plan activo</p>
        </div>
        <div class="bg-purple-500/20 border border-purple-400/20 rounded-xl p-3 text-center backdrop-blur-sm">
            <p class="text-2xl font-bold text-purple-300">{{ $monthlyPlans }}</p>
            <p class="text-xs text-white/60 mt-0.5">Planes este mes</p>
        </div>
        <div class="bg-orange-500/20 border border-orange-400/20 rounded-xl p-3 text-center backdrop-blur-sm">
            <p class="text-2xl font-bold text-orange-300">{{ $expiringCount }}</p>
            <p class="text-xs text-white/60 mt-0.5">Por vencer</p>
        </div>
    </div>

    <a href="{{ route('checkin.show') }}"
       class="flex items-center gap-3 bg-emerald-600/30 border border-emerald-400/30 rounded-xl px-4 py-3.5 mb-4 active:bg-emerald-600/50">
        <div class="bg-emerald-500/30 rounded-lg p-2 shrink-0">
            <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm">Registrar Asistencias por DNI</p>
            <p class="text-emerald-300/70 text-xs mt-0.5">Registro de asistencias por el alumno</p>
        </div>
        <svg class="w-4 h-4 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <a href="{{ route('matricula.index') }}"
       class="flex items-center gap-3 bg-indigo-600/20 border border-indigo-400/25 rounded-xl px-4 py-3.5 mb-4 active:bg-indigo-600/40">
        <div class="bg-indigo-500/25 rounded-lg p-2 shrink-0">
            <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm">Registro de Matrícula y Planes</p>
            <p class="text-indigo-300/60 text-xs mt-0.5">Buscar o crear alumno y gestionar su plan</p>
        </div>
        <svg class="w-4 h-4 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Tomar asistencia por curso</h2>

    <div class="space-y-3">
    @forelse($activeClases as $clase)
        @php
            $isToday = is_array($clase->schedule) && isset($clase->schedule[$todayKey]);
            $nombre  = strtolower($clase->name);
            $img     = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
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
        <div class="text-center py-8 text-white/40">
            <p class="text-sm">No hay clases activas.</p>
            <a href="{{ route('clases.create') }}" class="text-teal-400 text-sm font-medium mt-1 inline-block">
                Crear primer curso →
            </a>
        </div>
    @endforelse
    </div>
</div>
@endsection
