@extends('layouts.app')

@push('head')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
@php
    $todayClases = $clases->filter(fn($c) => is_array($c->schedule) && isset($c->schedule[$dayKey]));
    $todayIds    = $todayClases->pluck('id')->values();

    $resolveImg = fn($name) => str_contains(strtolower($name), 'salsa')   ? asset('images/salsa.jpg')
                             : (str_contains(strtolower($name), 'bachata') ? asset('images/bachata.jpg')
                             : (str_contains(strtolower($name), 'lady')    ? asset('images/lady.jpg')
                             : null));

    $clasesData = $clases->map(function ($c) use ($existing, $resolveImg) {
        return [
            'id'        => $c->id,
            'name'      => $c->name,
            'img'       => $resolveImg($c->name),
            'present'   => (bool) ($existing[$c->id] ?? false),
            'saving'    => false,
            'error'     => false,
            'toggleUrl' => route('attendance.toggle', $c),
        ];
    })->values();

    $unenrolledData = $unenrolledTodayClases->map(fn($c) => [
        'id'        => $c->id,
        'name'      => $c->name,
        'img'       => $resolveImg($c->name),
        'addUrl'    => route('attendance.add-student', $c),
        'toggleUrl' => route('attendance.toggle', $c),
        'added'     => false,
        'saving'    => false,
        'error'     => false,
    ])->values();
@endphp

<div x-data="{
    clases: {{ $clasesData->toJson() }},
    unenrolled: {{ $unenrolledData->toJson() }},
    todayIds: {{ $todayIds->toJson() }},
    date: '{{ $date->toDateString() }}',
    csrfToken: '{{ csrf_token() }}',
    get presentCount() { return this.clases.filter(function(c) { return c.present; }).length; },
    async addToClase(u) {
        u.saving = true;
        u.error  = false;
        try {
            const res = await fetch(u.addUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json'},
                body: JSON.stringify({student_id: {{ $student->id }}, date: this.date}),
            });
            if (!res.ok) throw new Error();
            u.added = true;
            this.todayIds.push(u.id);
            this.clases.push({id: u.id, name: u.name, present: true, saving: false, error: false, toggleUrl: u.toggleUrl});
        } catch {
            u.error = true;
        } finally {
            u.saving = false;
        }
    },
    async toggle(clase) {
        clase.saving = true;
        clase.error  = false;
        clase.present = !clase.present;
        try {
            const res = await fetch(clase.toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    student_id: {{ $student->id }},
                    date: this.date,
                    present: clase.present,
                }),
            });
            if (!res.ok) throw new Error();
        } catch {
            clase.present = !clase.present;
            clase.error = true;
        } finally {
            clase.saving = false;
        }
    },
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ route('attendance.index') }}" class="text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold text-white truncate">{{ $student->name }}</h1>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    @if($student->dni)
                        <span class="text-white/40 text-xs">DNI {{ $student->dni }}</span>
                    @endif
                    @if($planStatus === 'ok')
                        <span class="text-xs font-medium text-green-300 bg-green-500/20 px-1.5 py-0.5 rounded-full">Plan activo</span>
                    @elseif($planStatus === 'exhausted')
                        <span class="text-xs font-medium text-orange-300 bg-orange-500/20 px-1.5 py-0.5 rounded-full">Clases agotadas</span>
                    @elseif($planStatus === 'expired')
                        <span class="text-xs font-medium text-red-300 bg-red-500/20 px-1.5 py-0.5 rounded-full">Plan vencido</span>
                    @elseif($planStatus === 'pending')
                        <span class="text-xs font-medium text-blue-300 bg-blue-500/20 px-1.5 py-0.5 rounded-full">Plan pendiente</span>
                    @else
                        <span class="text-xs font-medium text-white/40 bg-white/10 px-1.5 py-0.5 rounded-full">Sin plan</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Selector de fecha --}}
        <div class="flex items-center gap-2 bg-white/15 border border-white/20 rounded-xl px-3 py-2">
            <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date"
                   value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   onchange="window.location.href='{{ route('attendance.take-student', $student) }}?date=' + this.value"
                   style="background:transparent;"
                   class="flex-1 text-sm text-white focus:outline-none w-full">
        </div>
    </div>

    {{-- Barra de estado --}}
    <div class="bg-white/5 backdrop-blur-sm border-b border-white/10 px-4 py-3 flex items-center justify-between">
        <p class="text-sm text-white/70">
            <span class="font-bold text-green-400" x-text="presentCount"></span>
            <span x-text="presentCount === 1 ? 'curso presente hoy' : 'cursos presentes hoy'"></span>
        </p>
        <p class="text-xs text-white/40">
            {{ $date->locale('es')->isoFormat('dddd D [de] MMMM') }}
            @if($date->isToday()) <span class="text-teal-300">(Hoy)</span> @endif
        </p>
    </div>

    @if($clases->isEmpty())
        <div class="text-center py-12 text-white/40 px-4">
            <p class="text-sm">Este alumno no está inscrito en ningún curso.</p>
            <a href="{{ route('clases.index') }}" class="text-teal-400 text-sm font-medium mt-1 inline-block">
                Gestionar cursos →
            </a>
        </div>
    @else

        {{-- Cursos del día --}}
        <div x-show="clases.filter(c => todayIds.includes(c.id)).length > 0">
            <div class="px-4 pt-4 pb-1">
                <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Cursos de hoy</p>
            </div>
            <div class="divide-y divide-white/10">
                <template x-for="clase in clases.filter(c => todayIds.includes(c.id))" :key="clase.id">
                    <div :class="clase.present ? 'bg-green-500/15' : 'bg-red-500/10'"
                         class="flex items-center px-4 py-4 transition-colors select-none"
                         @dblclick="toggle(clase)">

                        <template x-if="clase.img">
                            <img :src="clase.img" :alt="clase.name" class="w-10 h-10 rounded-full object-cover shrink-0 mr-3">
                        </template>
                        <template x-if="!clase.img">
                            <div class="w-10 h-10 rounded-full bg-indigo-500/30 border border-indigo-400/20 flex items-center justify-center shrink-0 mr-3">
                                <span class="text-indigo-300 font-bold text-sm" x-text="clase.name.charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                        <div class="flex-1 min-w-0 mr-3">
                            <p class="font-medium text-white" x-text="clase.name"></p>
                            <p class="text-xs mt-0.5"
                               :class="clase.error ? 'text-orange-400' : (clase.present ? 'text-green-400' : 'text-red-400')"
                               x-text="clase.error ? 'Error al guardar' : (clase.present ? 'Presente' : 'Ausente')"></p>
                        </div>

                        <span x-show="clase.saving" class="mr-3">
                            <svg class="w-4 h-4 text-white/40 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </span>
                        <span x-show="clase.error && !clase.saving" class="mr-3 text-orange-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                            </svg>
                        </span>

                        <button type="button"
                                @click="toggle(clase)"
                                :disabled="clase.saving"
                                :class="clase.present ? 'bg-green-500' : 'bg-white/20'"
                                class="relative w-14 h-8 rounded-full transition-colors duration-200 focus:outline-none shrink-0 disabled:opacity-40">
                            <span :class="clase.present ? 'translate-x-7' : 'translate-x-1'"
                                  class="absolute top-1 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200 block"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cursos de hoy no inscritos --}}
        <div x-show="unenrolled.filter(u => !u.added).length > 0">
            <div class="px-4 pt-4 pb-1">
                <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Cursos de hoy no inscritos</p>
            </div>
            <div class="divide-y divide-white/10">
                <template x-for="u in unenrolled.filter(u => !u.added)" :key="u.id">
                    <div class="flex items-center px-4 py-4 bg-white/5">
                        <template x-if="u.img">
                            <img :src="u.img" :alt="u.name" class="w-10 h-10 rounded-full object-cover shrink-0 mr-3 opacity-60">
                        </template>
                        <template x-if="!u.img">
                            <div class="w-10 h-10 rounded-full bg-white/10 border border-white/10 flex items-center justify-center shrink-0 mr-3">
                                <span class="text-white/40 font-bold text-sm" x-text="u.name.charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                        <div class="flex-1 min-w-0 mr-3">
                            <p class="font-medium text-white/70" x-text="u.name"></p>
                            <p class="text-xs text-white/40 mt-0.5" x-show="!u.error">No inscrito</p>
                            <p class="text-xs text-orange-400 mt-0.5" x-show="u.error">Error al añadir</p>
                        </div>
                        <span x-show="u.saving" class="mr-3">
                            <svg class="w-4 h-4 text-white/40 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                        </span>
                        <button type="button" @click="addToClase(u)"
                                :disabled="u.saving"
                                x-show="!u.saving"
                                class="flex items-center gap-1.5 text-xs font-medium text-teal-300 bg-teal-500/20 border border-teal-400/20 px-3 py-1.5 rounded-lg shrink-0 disabled:opacity-40">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Añadir
                        </button>
                    </div>
                </template>
            </div>
        </div>

        @if($todayClases->isEmpty() && $unenrolledTodayClases->isEmpty())
            <div class="px-4 py-3 mx-4 mt-4 bg-yellow-400/10 border border-yellow-400/20 rounded-xl">
                <p class="text-yellow-300 text-sm">No hay cursos programados para hoy.</p>
            </div>
        @endif

    @endif

</div>
@endsection
