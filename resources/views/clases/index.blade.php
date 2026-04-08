@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
            <h1 class="text-xl font-bold text-white">Cursos</h1>
        </div>
        <a href="{{ route('clases.create') }}"
           class="bg-white/20 border border-white/30 text-white font-semibold text-sm px-4 py-2 rounded-lg">
            + Nuevo
        </a>
    </div>
</div>

<div class="divide-y divide-white/10">
    @forelse($clases as $clase)
        <div class="flex items-center px-4 py-3 {{ !$clase->active ? 'opacity-50' : '' }}">

            <div class="w-10 h-10 rounded-full bg-purple-500/30 border border-purple-400/30 flex items-center justify-center
                        text-purple-300 font-bold text-sm mr-3 shrink-0">
                {{ strtoupper(substr($clase->name, 0, 1)) }}
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-medium text-white truncate">{{ $clase->name }}</p>
                @if($clase->schedule)
                    <p class="text-white/60 mt-0.5">{!! $clase->scheduleText() !!}</p>
                @endif
                <div class="flex gap-1 mt-0.5">
                    <span class="text-xs text-white/40">{{ $clase->students_count }} alumno{{ $clase->students_count != 1 ? 's' : '' }}</span>
                    @if(!$clase->active)
                        <span class="text-xs text-red-400">· Inactivo</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 ml-2">
                <a href="{{ route('clases.edit', $clase) }}"
                   class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-purple-500/20 border border-purple-400/20 text-purple-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                    </svg>
                    Editar
                </a>
                <a href="{{ route('clases.enroll', $clase) }}"
                   class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-white/10 border border-white/15 text-white/70">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Alumnos
                </a>
            </div>
        </div>
    @empty
        <div class="text-center py-12 text-white/40">
            <p class="text-sm">No hay cursos registrados.</p>
        </div>
    @endforelse
</div>
@endsection
