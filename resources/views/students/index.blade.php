@extends('layouts.app')

@section('content')
<div x-data="{
    search: '',
    students: {{ $students->map(fn($s) => [
        'id'         => $s->id,
        'name'       => $s->name,
        'phone'      => $s->phone ?? '',
        'active'     => $s->active,
        'url'        => route('students.edit', $s),
        'planUrl'    => route('students.plans.index', $s),
        'initial'    => strtoupper(substr($s->name, 0, 1)),
        'planStatus' => $s->planStatus,
    ])->toJson() }},
    get filtered() {
        if (!this.search.trim()) return this.students;
        const q = this.search.toLowerCase();
        return this.students.filter(s =>
            s.name.toLowerCase().includes(q) ||
            s.phone.toLowerCase().includes(q)
        );
    }
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo">
                <h1 class="text-xl font-bold text-white">Alumnos</h1>
            </div>
            <a href="{{ route('students.create') }}"
               class="bg-white/20 border border-white/30 text-white font-semibold text-sm px-4 py-2 rounded-lg">
                + Nuevo
            </a>
        </div>

        <div class="relative">
            <svg class="w-4 h-4 text-white/40 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="search"
                   x-model="search"
                   placeholder="Buscar alumno..."
                   autocomplete="off"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm bg-white/15 border border-white/20 text-white placeholder-white/40
                          focus:outline-none focus:ring-2 focus:ring-white/30">
        </div>

        <p class="text-white/40 text-xs mt-2" x-text="filtered.length + ' alumno' + (filtered.length !== 1 ? 's' : '')"></p>
    </div>

    {{-- Lista --}}
    <div>
        <template x-for="student in filtered" :key="student.id">
            <div :class="!student.active ? 'opacity-50' : ''"
                 class="flex items-center px-4 py-3 border-b border-white/10">

                <div class="w-10 h-10 rounded-full bg-indigo-500/30 border border-indigo-400/30 flex items-center justify-center
                            text-indigo-300 font-bold text-sm mr-3 shrink-0"
                     x-text="student.initial">
                </div>

                <div class="flex-1 min-w-0">
                    <p class="font-medium text-white truncate" x-text="student.name"></p>
                    <p class="text-sm text-white/50 truncate" x-text="student.phone"></p>
                    <div class="flex gap-1 mt-0.5 flex-wrap">
                        <span x-show="!student.active" class="text-xs text-red-400">Inactivo</span>
                        <span x-show="student.planStatus === 'ok'"
                              class="text-xs font-medium text-green-400">Plan activo</span>
                        <span x-show="student.planStatus === 'pending'"
                              class="text-xs font-medium text-blue-400">Plan por iniciar</span>
                        <span x-show="student.planStatus === 'exhausted'"
                              class="text-xs font-medium text-orange-400">Plan inactivo</span>
                        <span x-show="student.planStatus === 'expired'"
                              class="text-xs font-medium text-red-400">Plan inactivo</span>
                        <span x-show="student.planStatus === 'no_plan'"
                              class="text-xs font-medium text-white/40">Sin plan</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 ml-2">
                    <a :href="student.url"
                       class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-500/20 border border-indigo-400/20 text-indigo-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                        </svg>
                        Editar
                    </a>
                    <a :href="student.planUrl"
                       class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium"
                       :class="{
                           'bg-orange-500/20 border border-orange-400/20 text-orange-300': student.planStatus === 'exhausted',
                           'bg-red-500/20 border border-red-400/20 text-red-300': student.planStatus === 'expired',
                           'bg-white/10 border border-white/15 text-white/60': student.planStatus === 'no_plan' || student.planStatus === 'pending',
                           'bg-green-500/20 border border-green-400/20 text-green-300': student.planStatus === 'ok',
                       }">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Plan
                    </a>
                </div>
            </div>
        </template>

        <div x-show="filtered.length === 0"
             class="text-center py-12">
            <svg class="w-12 h-12 mx-auto mb-3 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-sm text-white/40"
               x-text="search ? 'Sin resultados para &quot;' + search + '&quot;' : 'No hay alumnos registrados.'"></p>
        </div>
    </div>
</div>
@endsection
