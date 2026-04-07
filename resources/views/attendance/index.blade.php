@extends('layouts.app')

@section('content')
<div class="bg-teal-600 px-4 pt-6 pb-4">
    <h1 class="text-xl font-bold text-white">Tomar Asistencia</h1>
    <p class="text-teal-200 text-sm mt-0.5">Selecciona un curso</p>
</div>

<div class="p-4 space-y-3">
    @forelse($clases as $clase)
        @php $isToday = is_array($clase->schedule) && isset($clase->schedule[$todayKey]); @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border overflow-hidden
                    {{ $isToday ? 'border-teal-200 dark:border-teal-800' : 'border-gray-100 dark:border-gray-700' }}">
            <a href="{{ route('attendance.take', $clase) }}"
               class="flex items-center p-4 active:bg-gray-50 dark:active:bg-gray-700">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 dark:text-white text-lg">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{!! $clase->scheduleText() !!}</p>
                    @endif
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $clase->students_count }} alumno{{ $clase->students_count != 1 ? 's' : '' }}</p>
                </div>
                <div class="text-right ml-3 shrink-0">
                    @if($isToday)
                        @php
                            $todaySlot = $clase->schedule[$todayKey];
                            $start = is_array($todaySlot) ? $todaySlot['start'] : $todaySlot;
                            $end   = is_array($todaySlot) ? ($todaySlot['end'] ?? '') : '';
                        @endphp
                        <span class="block bg-teal-600 text-white text-sm font-semibold px-4 py-2 rounded-lg text-center">
                            Hoy
                        </span>
                        <span class="block text-teal-600 dark:text-teal-400 text-sm font-bold mt-1 text-center">
                            {{ $start }}{{ $end ? '–'.$end : '' }}
                        </span>
                    @endif
                </div>
            </a>
        </div>
    @empty
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No hay clases activas.</p>
            <a href="{{ route('clases.create') }}" class="text-teal-600 dark:text-teal-400 text-sm font-medium mt-1 inline-block">
                Crear primer curso →
            </a>
        </div>
    @endforelse
</div>
@endsection
