@extends('layouts.app')

@section('content')
<div x-data="{
    search: '',
    tab: 'todos',
    students: {{ $students->map(fn($s) => [
        'id'              => $s->id,
        'name'            => $s->name,
        'phone'           => $s->phone ?? '',
        'active'          => $s->active,
        'url'             => route('students.edit', $s),
        'planUrl'         => route('students.plans.index', $s),
        'initial'         => strtoupper(substr($s->name, 0, 1)),
        'planStatus'      => $s->planStatus,
        'isExpiring'      => $s->isExpiring,
        'waUrl'           => $s->waUrl,
        'waUrlExpired'    => $s->waUrlExpired,
        'planEndDate'     => $s->planEndDate,
        'planClassesLeft' => $s->planClassesLeft,
    ])->toJson() }},
    get expiringCount() {
        return this.students.filter(s => s.isExpiring).length;
    },
    get activeCount() {
        return this.students.filter(s => s.planStatus === 'ok').length;
    },
    get expiredCount() {
        return this.students.filter(s => s.planStatus === 'expired' || s.planStatus === 'exhausted').length;
    },
    get filtered() {
        let list = this.students;
        if (this.tab === 'activos')    list = list.filter(s => s.planStatus === 'ok');
        if (this.tab === 'por-vencer') list = list.filter(s => s.isExpiring);
        if (this.tab === 'vencido')    list = list.filter(s => s.planStatus === 'expired' || s.planStatus === 'exhausted');
        if (!this.search.trim()) return list;
        const q = this.search.toLowerCase();
        return list.filter(s =>
            s.name.toLowerCase().includes(q) ||
            s.phone.toLowerCase().includes(q)
        );
    }
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
                <h1 class="text-xl font-bold text-white">Alumnos</h1>
            </div>
            <a href="{{ route('students.create') }}"
               class="bg-emerald-600 text-white font-semibold text-sm px-4 py-2 rounded-lg">
                + Nuevo
            </a>
        </div>

        {{-- Segmented control --}}
        <div class="flex bg-white/10 rounded-xl p-0.5 mb-3">
            <button @click="tab = 'todos'; search = ''"
                    :class="tab === 'todos' ? 'bg-white/20 text-white shadow-sm' : 'text-white/50'"
                    class="flex-1 text-xs py-1.5 rounded-[10px] transition-all duration-200">
                Todos
            </button>
            <button @click="tab = 'activos'; search = ''"
                    :class="tab === 'activos' ? 'bg-green-600 text-white shadow-sm' : 'text-white/50'"
                    class="flex-1 flex items-center justify-center gap-1 text-xs py-1.5 rounded-[10px] transition-all duration-200">
                <span>Activos</span>
                <span x-show="activeCount > 0"
                      :class="tab === 'activos' ? 'bg-white/25 text-white' : 'bg-white/15 text-white/60'"
                      class="text-[10px] px-1 py-0.5 rounded-full leading-none"
                      x-text="activeCount"></span>
            </button>
            <button @click="tab = 'por-vencer'; search = ''"
                    :class="tab === 'por-vencer' ? 'bg-amber-500 text-white shadow-sm' : 'text-white/50'"
                    class="flex-1 flex items-center justify-center gap-1 text-xs py-1.5 rounded-[10px] transition-all duration-200">
                <span>Por vencer</span>
                <span x-show="expiringCount > 0"
                      :class="tab === 'por-vencer' ? 'bg-white/25 text-white' : 'bg-white/15 text-white/60'"
                      class="text-[10px] px-1 py-0.5 rounded-full leading-none"
                      x-text="expiringCount"></span>
            </button>
            <button @click="tab = 'vencido'; search = ''"
                    :class="tab === 'vencido' ? 'bg-red-500 text-white shadow-sm' : 'text-white/50'"
                    class="flex-1 flex items-center justify-center gap-1 text-xs py-1.5 rounded-[10px] transition-all duration-200">
                <span>Vencido</span>
                <span x-show="expiredCount > 0"
                      :class="tab === 'vencido' ? 'bg-white/25 text-white' : 'bg-white/15 text-white/60'"
                      class="text-[10px] px-1 py-0.5 rounded-full leading-none"
                      x-text="expiredCount"></span>
            </button>
        </div>

        {{-- Buscador --}}
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

        <p class="text-white/40 text-xs mt-2"
           x-text="filtered.length + ' alumno' + (filtered.length !== 1 ? 's' : '')"></p>
    </div>

    {{-- Lista TAB: Todos --}}
    <div x-show="tab === 'todos' || tab === 'activos'">
        <template x-for="student in filtered" :key="student.id">
            <div :class="!student.active ? 'opacity-50' : ''"
                 class="px-4 py-3 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-500/30 border border-indigo-400/30 flex items-center justify-center
                                text-indigo-300 font-bold text-sm shrink-0"
                         x-text="student.initial">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-white truncate" x-text="student.name"></p>
                            <span x-show="!student.active"                   class="text-xs text-red-400 shrink-0">Inactivo</span>
                            <span x-show="student.active && student.planStatus === 'ok'"       class="text-xs font-medium text-green-400 shrink-0">Plan activo</span>
                            <span x-show="student.active && student.planStatus === 'pending'"  class="text-xs font-medium text-blue-400 shrink-0">Por iniciar</span>
                            <span x-show="student.active && student.planStatus === 'exhausted'" class="text-xs font-medium text-orange-400 shrink-0">Plan inactivo</span>
                            <span x-show="student.active && student.planStatus === 'expired'"  class="text-xs font-medium text-red-400 shrink-0">Plan inactivo</span>
                            <span x-show="student.active && student.planStatus === 'no_plan'"  class="text-xs font-medium text-white/40 shrink-0">Sin plan</span>
                        </div>
                        <p class="text-sm text-white/50 truncate" x-text="student.phone"></p>
                    </div>
                </div>
                <div class="flex gap-2 mt-2 justify-end">
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

        <div x-show="filtered.length === 0" class="text-center py-12">
            <svg class="w-12 h-12 mx-auto mb-3 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="text-sm text-white/40"
               x-text="search ? 'Sin resultados para &quot;' + search + '&quot;' : 'No hay alumnos registrados.'"></p>
        </div>
    </div>

    {{-- Lista TAB: Por vencer --}}
    <div x-show="tab === 'por-vencer'">
        <template x-for="student in filtered" :key="student.id">
            <div class="px-4 py-3.5 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-400/30 flex items-center justify-center
                                text-amber-300 font-bold text-sm shrink-0"
                         x-text="student.initial">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-white truncate" x-text="student.name"></p>
                            <span x-show="student.planStatus === 'ok'"        class="text-xs font-medium text-green-400 shrink-0">Plan activo</span>
                            <span x-show="student.planStatus === 'exhausted'" class="text-xs font-medium text-orange-400 shrink-0">Plan inactivo</span>
                        </div>
                        <p class="text-sm text-white/50 truncate" x-text="student.phone || 'Sin teléfono'"></p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="flex items-center gap-1 text-xs text-amber-300/80">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="'Vence: ' + student.planEndDate"></span>
                            </span>
                            <span x-show="student.planClassesLeft !== null"
                                  class="flex items-center gap-1 text-xs text-white/50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span x-text="student.planClassesLeft + ' clase' + (student.planClassesLeft !== 1 ? 's' : '') + ' restante' + (student.planClassesLeft !== 1 ? 's' : '')"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-2 justify-end">
                    <a x-show="student.waUrl" :href="student.waUrl" target="_blank"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600/25 border border-green-500/30 text-green-300">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.533 5.857L.073 23.927l6.232-1.638A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.001-1.371l-.359-.213-3.698.97.987-3.607-.234-.371A9.818 9.818 0 1112 21.818z"/>
                        </svg>
                        Avisar
                    </a>
                    <span x-show="!student.waUrl"
                          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white/5 border border-white/10 text-white/30">
                        Sin teléfono
                    </span>
                </div>
            </div>
        </template>

        <div x-show="filtered.length === 0" class="text-center py-12">
            <svg class="w-12 h-12 mx-auto mb-3 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-white/40">Ningún alumno por vencer. ¡Todo en orden!</p>
        </div>
    </div>

    {{-- Lista TAB: Vencido --}}
    <div x-show="tab === 'vencido'">
        <template x-for="student in filtered" :key="student.id">
            <div class="px-4 py-3.5 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-500/20 border border-red-400/30 flex items-center justify-center
                                text-red-300 font-bold text-sm shrink-0"
                         x-text="student.initial">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-white truncate" x-text="student.name"></p>
                            <span x-show="student.planStatus === 'expired'"   class="text-xs font-medium text-red-400 shrink-0">Vencido</span>
                            <span x-show="student.planStatus === 'exhausted'" class="text-xs font-medium text-orange-400 shrink-0">Agotado</span>
                        </div>
                        <p class="text-sm text-white/50 truncate" x-text="student.phone || 'Sin teléfono'"></p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span x-show="student.planStatus === 'expired'"
                                  class="flex items-center gap-1 text-xs text-red-400/80">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="'Venció: ' + student.planEndDate"></span>
                            </span>
                            <span x-show="student.planStatus === 'exhausted'"
                                  class="flex items-center gap-1 text-xs text-orange-400/80">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Cuota agotada
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 mt-2 justify-end">
                    <a x-show="student.waUrlExpired" :href="student.waUrlExpired" target="_blank"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600/25 border border-green-500/30 text-green-300">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.533 5.857L.073 23.927l6.232-1.638A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.001-1.371l-.359-.213-3.698.97.987-3.607-.234-.371A9.818 9.818 0 1112 21.818z"/>
                        </svg>
                        Avisar
                    </a>
                    <a :href="student.planUrl"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500/20 border border-red-400/30 text-red-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Renovar
                    </a>
                </div>
            </div>
        </template>

        <div x-show="filtered.length === 0" class="text-center py-12">
            <svg class="w-12 h-12 mx-auto mb-3 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-white/40">Ningún alumno con plan vencido.</p>
        </div>
    </div>
</div>
@endsection
