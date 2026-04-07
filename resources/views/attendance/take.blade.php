@extends('layouts.app')

@section('content')
<div x-data="{
    students: {{ $students->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'present' => (bool)($existing[$s->id] ?? $defaultPresent), 'saving' => false, 'error' => false, 'planStatus' => $planStatuses[$s->id] ?? 'no_plan'])->toJson() }},
    extraStudents: {{ $extraStudents->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'phone' => $s->phone ?? '', 'planStatus' => $extraPlanStatuses[$s->id] ?? 'no_plan'])->toJson() }},
    search: '',
    modalOpen: false,
    modalSearch: '',
    date: '{{ $date->toDateString() }}',
    toggleUrl: '{{ route('attendance.toggle', $clase) }}',
    addUrl: '{{ route('attendance.add-student', $clase) }}',
    csrfToken: '{{ csrf_token() }}',
    get presentCount() { return this.students.filter(function(s) { return s.present; }).length },
    get filteredStudents() {
        var q = this.search.toLowerCase().trim();
        if (!q) return this.students;
        return this.students.filter(function(s) { return s.name.toLowerCase().indexOf(q) !== -1; });
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
    <div class="bg-teal-600 px-4 pt-6 pb-4">
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ route('attendance.index') }}" class="text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-white">{{ $clase->name }}</h1>
                <p class="text-teal-200 text-sm">
                    {{ $date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    @if($date->isToday())
                        <span class="ml-1 text-teal-300">(Hoy)</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Selector de fecha --}}
        <div class="flex items-center gap-2 bg-white dark:bg-gray-700 rounded-xl px-3 py-2 mt-3">
            <svg class="w-4 h-4 text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <input type="date"
                   value="{{ $date->toDateString() }}"
                   max="{{ today()->toDateString() }}"
                   onchange="window.location.href='{{ route('attendance.take', $clase) }}?date=' + this.value"
                   style="background:transparent; color: inherit;"
                   class="flex-1 text-gray-800 dark:text-gray-100 text-sm focus:outline-none w-full">
        </div>

        @if(!$date->isToday())
            <div class="bg-yellow-400 text-yellow-900 text-xs font-medium px-3 py-2 rounded-lg mt-2">
                Estás viendo asistencia de una fecha pasada
            </div>
        @endif
    </div>

    {{-- Barra de estado --}}
    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-b border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-bold text-green-600 dark:text-green-400" x-text="presentCount"></span>
            / {{ $students->count() }} total
        </p>
        <button type="button" @click="modalOpen = true"
                class="flex items-center gap-1.5 text-xs text-teal-600 dark:text-teal-400 font-medium px-3 py-1.5 bg-teal-50 dark:bg-teal-900/30 rounded-lg">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Añadir alumno
        </button>
    </div>

    @if($students->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500 px-4">
            <p class="text-sm">Este curso no tiene alumnos inscritos.</p>
            <a href="{{ route('clases.enroll', $clase) }}"
               class="text-teal-600 dark:text-teal-400 text-sm font-medium mt-1 inline-block">
                Gestionar matrícula →
            </a>
        </div>
    @else
        {{-- Buscador --}}
        <div class="bg-white dark:bg-gray-800 px-4 py-2 border-b border-gray-100 dark:border-gray-700">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="search" x-model="search"
                       placeholder="Buscar alumno..."
                       autocomplete="off"
                       :class="search ? 'pr-8' : 'pr-4'"
                       class="w-full pl-9 py-2 rounded-xl text-sm border border-gray-200 dark:border-gray-600
                              bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                              focus:outline-none focus:ring-2 focus:ring-teal-400">
                <button x-show="search" @click="search = ''"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
            <template x-for="student in filteredStudents" :key="student.id">
                <div :class="student.present ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'"
                     class="flex items-center px-4 py-4 transition-colors select-none"
                     @dblclick="toggle(student)">

                    <div class="flex-1 min-w-0 mr-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-medium text-gray-900 dark:text-white text-base" x-text="student.name"></p>
                            <span x-show="student.planStatus === 'exhausted'"
                                  class="text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/30 px-1.5 py-0.5 rounded-full">
                                Clases agotadas
                            </span>
                            <span x-show="student.planStatus === 'expired'"
                                  class="text-xs font-medium text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 px-1.5 py-0.5 rounded-full">
                                Plan vencido
                            </span>
                            <span x-show="student.planStatus === 'no_plan'"
                                  class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                Sin plan
                            </span>
                        </div>
                        <p class="text-xs mt-0.5"
                           :class="student.error ? 'text-orange-500' : (student.present ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400')"
                           x-text="student.error ? 'Error al guardar' : (student.present ? 'Presente' : 'Ausente')"></p>
                    </div>

                    <span x-show="student.saving" class="mr-3">
                        <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
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
                            @click="toggle(student)"
                            :disabled="student.saving"
                            :class="student.present ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                            class="relative w-14 h-8 rounded-full transition-colors duration-200 focus:outline-none shrink-0 disabled:opacity-60">
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
         style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background-color:rgba(0,0,0,0.5);"
         @click.self="modalOpen = false">

        <div style="width:90%; max-width:26rem; height:70vh;"
             class="bg-white dark:bg-gray-900 rounded-2xl flex flex-col shadow-xl"
             @click.stop>

            {{-- Cabecera modal --}}
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <h2 class="font-semibold text-gray-900 dark:text-white text-base">Añadir alumno no inscrito</h2>
                <button type="button" @click="modalOpen = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Buscador --}}
            <div class="px-4 py-3 shrink-0">
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="search" x-model="modalSearch"
                           x-ref="modalInput"
                           placeholder="Buscar alumno..."
                           autocomplete="off"
                           class="w-full pl-9 pr-4 py-2 rounded-xl text-sm border border-gray-200 dark:border-gray-600
                                  bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
            </div>

            {{-- Lista --}}
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="result in getSearchResults()" :key="result.id">
                    <button type="button" @click="addStudent(result)"
                            class="w-full flex items-center px-4 py-3 text-left hover:bg-teal-50 dark:hover:bg-teal-900/20">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="result.name"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="result.phone"></p>
                        </div>
                        <span class="text-xs text-teal-600 dark:text-teal-400 font-medium ml-2 shrink-0">+ Añadir</span>
                    </button>
                </template>
                <div x-show="getSearchResults().length === 0"
                     class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                    <span x-text="modalSearch ? 'Sin resultados para la búsqueda' : 'No hay alumnos no inscritos'"></span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
