@extends('layouts.app')

@push('head')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
<div x-data="{
    students: {{ $students->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'dni' => $s->dni ?? '', 'present' => (bool)($existing[$s->id] ?? $defaultPresent), 'saving' => false, 'error' => false, 'planStatus' => $planStatuses[$s->id] ?? 'no_plan'])->toJson() }},
    extraStudents: {{ $extraStudents->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'phone' => $s->phone ?? '', 'planStatus' => $extraPlanStatuses[$s->id] ?? 'no_plan'])->toJson() }},
    search: '',
    modalOpen: false,
    modalSearch: '',
    date: '{{ $date->toDateString() }}',
    dateInSchedule: {{ $dateInSchedule ? 'true' : 'false' }},
    toggleUrl: '{{ route('attendance.toggle', $clase) }}',
    addUrl: '{{ route('attendance.add-student', $clase) }}',
    csrfToken: '{{ csrf_token() }}',
    get presentCount() { return this.students.filter(function(s) { return s.present; }).length },
    get filteredStudents() {
        var q = this.search.toLowerCase().trim();
        if (!q) return this.students;
        return this.students.filter(function(s) {
            return s.name.toLowerCase().indexOf(q) !== -1 ||
                   (s.dni && s.dni.indexOf(q) !== -1);
        });
    },
    getSearchResults() {
        var q = this.modalSearch.toLowerCase().trim();
        var ids = this.students.map(function(s) { return s.id; });
        return this.extraStudents.filter(function(s) {
            return ids.indexOf(s.id) === -1 &&
                   (!q || s.name.toLowerCase().indexOf(q) !== -1 || s.phone.toLowerCase().indexOf(q) !== -1);
        });
    },
    async savePresent(student) {
        student.saving = true;
        student.error = false;
        try {
            const res = await fetch(this.toggleUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json'},
                body: JSON.stringify({student_id: student.id, date: this.date, present: student.present}),
            });
            if (!res.ok) throw new Error();
        } catch {
            student.error = true;
        } finally {
            student.saving = false;
        }
    },
    async addStudent(s) {
        this.students.push({id: s.id, name: s.name, present: true, saving: true, error: false, planStatus: s.planStatus});
        this.modalOpen = false;
        this.modalSearch = '';
        var student = this.students[this.students.length - 1];
        try {
            const res = await fetch(this.addUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json'},
                body: JSON.stringify({student_id: s.id, date: this.date}),
            });
            if (!res.ok) throw new Error();
        } catch {
            student.error = true;
        } finally {
            student.saving = false;
        }
    },
    async toggle(student) {
        student.saving = true;
        student.error = false;
        student.present = !student.present;
        try {
            const res = await fetch(this.toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    student_id: student.id,
                    date: this.date,
                    present: student.present,
                }),
            });
            if (!res.ok) throw new Error();
        } catch {
            student.present = !student.present;
            student.error = true;
        } finally {
            student.saving = false;
        }
    },
}">

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        @php
            $nombre = strtolower($clase->name);
            $imgCurso = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
                      : (str_contains($nombre, 'bachata') ? 'bachata.jpg'
                      : (str_contains($nombre, 'lady')    ? 'lady.jpg'
                      : null));
        @endphp
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ route('dashboard') }}" class="text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-white">{{ $clase->name }}</h1>
                @if($clase->schedule)
                    <p class="text-white/40 text-xs mt-0.5">{!! $clase->scheduleText() !!}</p>
                @endif
                <p class="text-white/60 text-sm mt-0.5">
                    {{ $date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    @if($date->isToday())
                        <span class="ml-1 text-teal-300">(Hoy)</span>
                    @endif
                </p>
            </div>
            @if($imgCurso)
                <img src="{{ asset('images/' . $imgCurso) }}"
                     class="w-10 h-10 rounded-full object-cover shrink-0" alt="{{ $clase->name }}">
            @endif
        </div>

        {{-- Selector de fecha --}}
        <div class="flex items-center gap-2 bg-white/15 border border-white/20 rounded-xl px-3 py-2 mt-3">
            <svg class="w-4 h-4 text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date"
                   value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   onchange="window.location.href='{{ route('attendance.take', $clase) }}?date=' + this.value"
                   style="background:transparent;"
                   class="flex-1 text-sm text-white focus:outline-none w-full">
        </div>

        @if(!$dateInSchedule)
            <div class="bg-red-500/20 border border-red-500/40 text-red-300 text-sm font-medium px-3 py-2 rounded-lg mt-2 flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                </svg>
                <span>
                    Este curso no tiene clase el <strong>{{ $date->locale('es')->isoFormat('dddd') }}</strong>.
                    <br>
                    <a href="{{ route('clases.edit', $clase) }}" class="underline font-semibold">Editar horario del curso →</a>
                </span>
            </div>
        @elseif(!$date->isToday())
            <div class="bg-yellow-400/20 border border-yellow-400/30 text-yellow-300 text-xs font-medium px-3 py-2 rounded-lg mt-2">
                Estás viendo asistencia de una fecha pasada
            </div>
        @endif
    </div>

    {{-- Barra de estado --}}
    <div class="bg-white/5 backdrop-blur-sm border-b border-white/10 px-4 py-3 flex items-center justify-between">
        <p class="text-sm text-white/70">
            <span class="font-bold text-green-400" x-text="presentCount"></span>
            / {{ $students->count() }} total
        </p>
        <button type="button" @click="dateInSchedule && (modalOpen = true)"
                :disabled="!dateInSchedule"
                :class="dateInSchedule ? 'text-teal-300 bg-teal-500/20 border-teal-400/20' : 'text-white/20 bg-white/5 border-white/10 cursor-not-allowed'"
                class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 border rounded-lg">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Añadir alumno
        </button>
    </div>

    @if($students->isEmpty())
        <div class="text-center py-12 text-white/40 px-4">
            <p class="text-sm">Este curso no tiene alumnos inscritos.</p>
            <a href="{{ route('clases.enroll', $clase) }}"
               class="text-teal-400 text-sm font-medium mt-1 inline-block">
                Gestionar matrícula →
            </a>
        </div>
    @else
        {{-- Buscador --}}
        <div class="slm-sticky px-4 py-2 border-b border-white/10" style="position:sticky; top:0; z-index:20;">
            <div class="relative">
                <svg class="w-4 h-4 text-white/40 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="search" x-model="search"
                       placeholder="Buscar alumno por nombre o DNI"
                       autocomplete="off"
                       :class="search ? 'pr-8' : 'pr-4'"
                       class="w-full pl-9 py-2 rounded-xl text-sm border border-white/20
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

        <div class="divide-y divide-white/10">
            <template x-for="student in filteredStudents" :key="student.id">
                <div :class="dateInSchedule ? (student.present ? 'bg-green-500/15' : 'bg-red-500/10') : 'bg-white/5 opacity-60'"
                     class="flex items-center px-4 py-4 transition-colors select-none"
                     @dblclick="dateInSchedule && toggle(student)">

                    <div class="flex-1 min-w-0 mr-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-medium text-white text-base" x-text="student.name"></p>
                            <span x-show="student.planStatus === 'exhausted'"
                                  class="text-xs font-medium text-orange-300 bg-orange-500/20 px-1.5 py-0.5 rounded-full">
                                Clases agotadas
                            </span>
                            <span x-show="student.planStatus === 'expired'"
                                  class="text-xs font-medium text-red-300 bg-red-500/20 px-1.5 py-0.5 rounded-full">
                                Plan vencido
                            </span>
                            <span x-show="student.planStatus === 'no_plan'"
                                  class="text-xs font-medium text-white/40 bg-white/10 px-1.5 py-0.5 rounded-full">
                                Sin plan
                            </span>
                        </div>
                        <p class="text-xs mt-0.5"
                           :class="student.error ? 'text-orange-400' : (student.present ? 'text-green-400' : 'text-red-400')"
                           x-text="student.error ? 'Error al guardar' : (student.present ? 'Presente' : 'Ausente')"></p>
                    </div>

                    <span x-show="student.saving" class="mr-3">
                        <svg class="w-4 h-4 text-white/40 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </span>
                    <span x-show="student.error && !student.saving" class="mr-3 text-orange-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                        </svg>
                    </span>

                    <button type="button"
                            @click="dateInSchedule && toggle(student)"
                            :disabled="student.saving || !dateInSchedule"
                            :class="student.present ? 'bg-green-500' : 'bg-white/20'"
                            class="relative w-14 h-8 rounded-full transition-colors duration-200 focus:outline-none shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span :class="student.present ? 'translate-x-7' : 'translate-x-1'"
                              class="absolute top-1 w-6 h-6 bg-white rounded-full shadow transition-transform duration-200 block">
                        </span>
                    </button>
                </div>
            </template>
        </div>
    @endif

    {{-- Modal: Añadir alumno no inscrito --}}
    <div x-show="modalOpen"
         class="flex items-center justify-center"
         style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background-color:rgba(0,0,0,0.7);"
         @click.self="modalOpen = false">

        <div class="slm-modal-panel rounded-2xl flex flex-col shadow-2xl"
             style="width:90%; max-width:26rem; height:70vh; background:rgba(15,15,30,0.95); backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.15);"
             @click.stop>

            {{-- Cabecera modal --}}
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-white/10 shrink-0">
                <h2 class="font-semibold text-white text-base">Añadir alumno no inscrito</h2>
                <button type="button" @click="modalOpen = false"
                        class="text-white/40 hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Buscador --}}
            <div class="px-4 py-3 shrink-0">
                <div class="relative">
                    <svg class="w-4 h-4 text-white/40 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="search" x-model="modalSearch"
                           x-ref="modalInput"
                           placeholder="Buscar alumno..."
                           autocomplete="off"
                           class="w-full pl-9 pr-4 py-2 rounded-xl text-sm border border-white/20
                                  bg-white/10 text-white placeholder-white/40
                                  focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
            </div>

            {{-- Lista --}}
            <div class="overflow-y-auto flex-1 divide-y divide-white/10">
                <template x-for="result in getSearchResults()" :key="result.id">
                    <button type="button" @click="addStudent(result)"
                            class="w-full flex items-center px-4 py-3 text-left hover:bg-teal-500/20">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white" x-text="result.name"></p>
                            <p class="text-xs text-white/50" x-text="result.phone"></p>
                        </div>
                        <span class="text-xs text-teal-400 font-medium ml-2 shrink-0">+ Añadir</span>
                    </button>
                </template>
                <div x-show="getSearchResults().length === 0"
                     class="px-4 py-8 text-center text-sm text-white/40">
                    <span x-text="modalSearch ? 'Sin resultados para la búsqueda' : '😃 Todos los alumnos están inscritos'"></span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
