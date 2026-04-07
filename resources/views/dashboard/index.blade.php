@extends('layouts.app')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Academia de Baile</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM') }}</p>
        </div>
        <div class="flex items-center gap-1">
            <a href="{{ route('settings.edit') }}" class="text-gray-400 dark:text-gray-500 p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 dark:text-gray-500 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $totalStudents }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Alumnos</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/30 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $totalClases }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Cursos</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/30 rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $todayAttendances }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Hoy</p>
        </div>
    </div>

    <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Tomar asistencia</h2>

    @forelse($activeClases as $clase)
        <a href="{{ route('attendance.take', $clase) }}"
           class="block bg-white dark:bg-gray-800 rounded-xl p-4 mb-3 shadow-sm border border-gray-100 dark:border-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $clase->name }}</p>
                    @if($clase->schedule)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{!! $clase->scheduleText() !!}</p>
                    @endif
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $clase->students_count }} alumno{{ $clase->students_count != 1 ? 's' : '' }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    @empty
        <div class="text-center py-8 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No hay clases activas.</p>
            <a href="{{ route('clases.create') }}" class="text-indigo-600 dark:text-indigo-400 text-sm font-medium mt-1 inline-block">
                Crear primer curso →
            </a>
        </div>
    @endforelse
</div>
@endsection
