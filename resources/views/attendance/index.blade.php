@extends('layouts.app')

@section('content')
<div x-data="{
    students: {{ $students->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'dni' => $s->dni ?? ''])->toJson() }},
    search: '',
    get filtered() {
        var q = this.search.toLowerCase().trim();
        if (!q) return this.students;
        return this.students.filter(function(s) {
            return s.name.toLowerCase().indexOf(q) !== -1 ||
                   (s.dni && s.dni.indexOf(q) !== -1);
        });
    }
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
            <div>
                <h1 class="text-xl font-bold text-white">Tomar Asistencia</h1>
                <p class="text-white/60 text-sm">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM') }}</p>
            </div>
        </div>
    </div>

    {{-- Buscador --}}
    <div class="px-4 py-3 border-b border-white/10  bg-black" style="position:sticky; top:0; z-index:20;">
        <div class="relative">
            <svg class="w-4 h-4 text-white/40 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="search" x-model="search"
                   placeholder="Buscar alumno por nombre o DNI"
                   autocomplete="off"
                   autofocus
                   :class="search ? 'pr-8' : 'pr-4'"
                   class="w-full pl-9 py-2.5 rounded-xl text-sm border border-white/20
                          bg-white/10 text-white placeholder-white/40
                          focus:outline-none focus:ring-2 focus:ring-teal-400">
            <button x-show="search" @click="search = ''"
                    class="absolute right-3 top-2.5 text-white/40 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Lista de alumnos --}}
    <div class="divide-y divide-white/10">
        {{-- Sin búsqueda: instrucción --}}
        <div x-show="!search" class="px-2 py-4 text-center text-white/40 flex flex-col items-center gap-2">
            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <p class="text-sm">Escribe el nombre o DNI del alumno</p>
        </div>

        {{-- Sin resultados --}}
        <div x-show="search && filtered.length === 0" class="px-4 py-8 text-center text-white/40">
            <p class="text-sm">No se encontró ningún alumno.</p>
        </div>

        {{-- Resultados --}}

        <template x-for="student in filtered" :key="student.id">
            <a :href="'{{ url('attendance/student') }}/' + student.id"
               class="flex items-center gap-3 px-4 py-4 active:bg-white/10">
                <div class="w-10 h-10 rounded-full bg-teal-500/20 border border-teal-400/20 flex items-center justify-center shrink-0">
                    <span class="text-teal-300 font-bold text-sm" x-text="student.name.charAt(0).toUpperCase()"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white" x-text="student.name"></p>
                    <p class="text-xs text-white/40 mt-0.5" x-text="student.dni ? 'DNI: ' + student.dni : 'Sin DNI'"></p>
                </div>
                <svg class="w-5 h-5 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </template>

    </div>

</div>
@endsection
