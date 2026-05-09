@extends('layouts.app')

@section('content')
@php
    $daysList    = ['lun' => 'Lun', 'mar' => 'Mar', 'mie' => 'Mié', 'jue' => 'Jue', 'vie' => 'Vie', 'sab' => 'Sáb', 'dom' => 'Dom'];
    $oldSchedule = old('schedule', []);
    $emptyTimes  = array_fill_keys(array_keys($daysList), ['start' => '', 'end' => '']);
    $initTimes   = array_merge($emptyTimes, array_map(fn($v) => is_array($v) ? $v : ['start' => $v, 'end' => ''], $oldSchedule));
@endphp
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('clases.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
    <h1 class="text-xl font-bold text-white">Nuevo Curso</h1>
</div>

<form method="POST" action="{{ route('clases.store') }}"
      x-data="scheduleSelector({{ Js::from(array_keys(array_filter($oldSchedule))) }}, {{ Js::from($initTimes) }})"
      class="p-4 space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Nombre del curso *</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
               placeholder="Ej: Salsa Principiantes"
               class="w-full border rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 border-white/20
                      focus:outline-none focus:border-purple-400 focus:bg-white/15 @error('name') border-red-400 @enderror">
        @error('name')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Horario --}}
    <div>
        <label class="block text-sm font-medium text-white/80 mb-2">Horario *</label>

        <div class="flex gap-1.5 flex-wrap mb-3">
            @foreach($daysList as $key => $label)
                <button type="button" @click="toggle('{{ $key }}')"
                        :class="selected.includes('{{ $key }}')
                            ? 'bg-purple-600 text-white border-purple-600'
                            : 'bg-white/10 text-white/60 border-white/20'"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="space-y-2">
            @foreach($daysList as $key => $label)
                <div x-show="selected.includes('{{ $key }}')" class="flex items-center gap-2">
                    <span class="text-sm font-medium text-white/70 w-8">{{ $label }}</span>
                    <input type="time" name="schedule[{{ $key }}][start]"
                           x-model="times['{{ $key }}'].start"
                           @change="autoEnd('{{ $key }}'); propagate('{{ $key }}')"
                           class="border border-white/50 rounded-lg px-3 py-1.5 text-sm
                                  bg-white/10 text-white
                                  focus:outline-none focus:border-purple-400 focus:bg-white/15">
                    <span class="text-white/40 text-sm">–</span>
                    <input type="time" name="schedule[{{ $key }}][end]"
                           x-model="times['{{ $key }}'].end"
                           class="border border-white/50 rounded-lg px-3 py-1.5 text-sm
                                  bg-white/10 text-white
                                  focus:outline-none focus:border-purple-400 focus:bg-white/15">
                </div>
            @endforeach
        </div>

        <p x-show="selected.length === 0" class="text-red-400 text-xs mt-2">Selecciona al menos un día.</p>
        <p x-show="scheduleHint" x-text="scheduleHint" class="text-amber-400 text-xs mt-2"></p>
        @error('schedule')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Descripción</label>
        <textarea name="description" rows="3" placeholder="Descripción opcional..."
                  class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                         bg-white/10
                         focus:outline-none focus:border-purple-400 focus:bg-white/15">{{ old('description') }}</textarea>
    </div>

    <div class="pt-2">
        <button type="submit"
                :disabled="!hasValidSchedule"
                :class="!hasValidSchedule ? 'opacity-50 cursor-not-allowed' : ''"
                class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-lg transition-opacity">
            Crear curso
        </button>
    </div>
</form>

<script>
(function () {
    function registerScheduleSelector() {
        Alpine.data('scheduleSelector', (initialSelected, initialTimes) => ({
            selected: initialSelected,
            times:    initialTimes,

            toggle(day) {
                if (this.selected.includes(day)) {
                    this.selected = this.selected.filter(function(d) { return d !== day; });
                    this.times[day] = { start: '', end: '' };
                } else {
                    this.selected.push(day);
                    var ref = this.getRef(day);
                    this.times[day].start = ref.start;
                    this.times[day].end   = ref.end;
                    this.autoEnd(day);
                }
            },

            getRef(excludeDay) {
                for (var i = 0; i < this.selected.length; i++) {
                    var d = this.selected[i];
                    if (d !== excludeDay && this.times[d].start) {
                        return { start: this.times[d].start, end: this.times[d].end };
                    }
                }
                return { start: '18:00', end: '' };
            },

            autoEnd(day) {
                var start = this.times[day].start;
                if (!start || this.times[day].end) return;
                var parts = start.split(':');
                var h = parseInt(parts[0]) + 1;
                if (h > 23) h = 23;
                this.times[day].end = (h < 10 ? '0' + h : '' + h) + ':' + parts[1];
            },

            propagate(day) {
                if (!this.times[day].start) return;
                for (var i = 0; i < this.selected.length; i++) {
                    var d = this.selected[i];
                    if (d !== day && !this.times[d].start) {
                        this.times[d].start = this.times[day].start;
                        this.times[d].end   = this.times[day].end;
                    }
                }
            },

            get hasValidSchedule() {
                if (this.selected.length === 0) return false;
                for (var i = 0; i < this.selected.length; i++) {
                    var d = this.selected[i];
                    if (!this.times[d].start || !this.times[d].end) return false;
                    if (this.times[d].end <= this.times[d].start) return false;
                }
                return true;
            },

            get scheduleHint() {
                if (this.selected.length === 0) return null;
                for (var i = 0; i < this.selected.length; i++) {
                    var d = this.selected[i];
                    if (!this.times[d].start || !this.times[d].end) return 'Completa la hora de inicio y fin de cada día.';
                    if (this.times[d].end <= this.times[d].start) return 'La hora de fin debe ser mayor a la hora de inicio.';
                }
                return null;
            },
        }));
    }
    if (window.Alpine) {
        registerScheduleSelector();
    } else {
        document.addEventListener('alpine:init', registerScheduleSelector);
    }
})();
</script>
@endsection
