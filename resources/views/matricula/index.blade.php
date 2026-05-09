@extends('layouts.app')

@section('content')
<div x-data="{
    students: {{ $students->map(fn($s) => [
        'id'     => $s->id,
        'name'   => $s->name,
        'dni'    => $s->dni ?? '',
        'status' => $s->currentPlan?->status() ?? 'no_plan',
    ])->toJson() }},
    search: '',
    planUrl:   '{{ url('students') }}',
    createUrl: '{{ route('students.create') }}',
    statusLabels: {
        ok:        'Plan activo',
        pending:   'Por iniciar',
        exhausted: 'Clases agotadas',
        expired:   'Plan vencido',
        no_plan:   'Sin plan',
    },
    statusClasses: {
        ok:        'bg-green-500/20 text-green-300',
        pending:   'bg-blue-500/20 text-blue-300',
        exhausted: 'bg-orange-500/20 text-orange-300',
        expired:   'bg-red-500/20 text-red-300',
        no_plan:   'bg-white/10 text-white/40',
    },
    get filtered() {
        var q = this.search.toLowerCase().trim();
        if (!q) return [];
        return this.students.filter(function(s) {
            return s.name.toLowerCase().indexOf(q) !== -1 ||
                   (s.dni && s.dni.indexOf(q) !== -1);
        });
    }
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-white shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo">
            </a>
            <div>
                <h1 class="text-xl font-bold text-white">Registro de Matrícula y Planes</h1>
                <p class="text-white/50 text-xs">Buscar y crear alumno y gestionar su plan</p>
            </div>
        </div>
    </div>

    {{-- Buscador (sticky) --}}
    <div class="px-4 py-3 border-b border-white/10 bg-black" style="position:sticky; top:0; z-index:20;">
        <div class="relative">
            <svg class="w-4 h-4 text-white/40 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="search" x-model="search"
                   placeholder="Nombre o DNI del alumno"
                   autocomplete="off"
                   autofocus
                   :class="search ? 'pr-8' : 'pr-4'"
                   class="w-full pl-9 py-2.5 rounded-xl text-sm border border-white/20
                          bg-white/10 text-white placeholder-white/40
                          focus:outline-none focus:ring-2 focus:ring-emerald-400">
            <button x-show="search" @click="search = ''"
                    class="absolute right-3 top-2.5 text-white/40 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Sin búsqueda: instrucción --}}
    <div x-show="!search" class="px-4 py-14 text-center flex flex-col items-center gap-3">
        <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <p class="text-white/40 text-sm">Escribe el nombre o DNI del alumno</p>
    </div>

    {{-- Sin resultados --}}
    <div x-show="search && filtered.length === 0"
         class="px-4 py-10 text-center flex flex-col items-center gap-4">
        <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-white/50 text-sm">No se encontró ningún alumno.</p>
            <p class="text-white/30 text-xs mt-1">¿Es un alumno nuevo?</p>
        </div>
        <a :href="createUrl"
           class="inline-flex items-center gap-2 bg-emerald-600/25 border border-emerald-400/30
                  text-emerald-300 text-sm font-medium px-5 py-2.5 rounded-xl active:bg-emerald-600/40">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar nuevo alumno
        </a>
    </div>

    {{-- Resultados --}}
    <div class="divide-y divide-white/10">
        <template x-for="student in filtered" :key="student.id">
            <a :href="planUrl + '/' + student.id + '/plans'"
               class="flex items-center gap-3 px-4 py-4 active:bg-white/10">
                <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-400/20
                            flex items-center justify-center shrink-0">
                    <span class="text-emerald-300 font-bold text-sm"
                          x-text="student.name.charAt(0).toUpperCase()"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white truncate" x-text="student.name"></p>
                    <p class="text-xs text-white/40 mt-0.5"
                       x-text="student.dni ? 'DNI: ' + student.dni : 'Sin DNI'"></p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full shrink-0 whitespace-nowrap"
                      :class="statusClasses[student.status] || 'bg-white/10 text-white/40'"
                      x-text="statusLabels[student.status] || ''">
                </span>
                <svg class="w-5 h-5 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </template>
    </div>

</div>
@endsection
