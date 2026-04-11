@extends('layouts.app')

@section('content')
@php
    $nombre = strtolower($clase->name);
    $imgCurso = str_contains($nombre, 'salsa')    ? 'salsa.jpg'
              : (str_contains($nombre, 'bachata') ? 'bachata.jpg'
              : (str_contains($nombre, 'lady')    ? 'lady.jpg'
              : null));
@endphp
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('clases.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
    <div class="flex-1">
        <h1 class="text-xl font-bold text-white">Editar Curso</h1>
        <p class="text-white/60 text-sm">{{ $clase->name }}</p>
    </div>
    @if($imgCurso)
        <img src="{{ asset('images/' . $imgCurso) }}"
             class="w-10 h-10 rounded-full object-cover shrink-0" alt="{{ $clase->name }}">
    @endif
</div>

<form method="POST" action="{{ route('clases.update', $clase) }}" class="p-4 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Nombre del curso *</label>
        <input type="text" name="name" value="{{ old('name', $clase->name) }}" required
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white
                      bg-white/10
                      focus:outline-none focus:border-purple-400 focus:bg-white/15">
    </div>

    {{-- Horario --}}
    @php
        $daysList = ['lun' => 'Lun', 'mar' => 'Mar', 'mie' => 'Mié', 'jue' => 'Jue', 'vie' => 'Vie', 'sab' => 'Sáb', 'dom' => 'Dom'];
        $currentSchedule = old('schedule', $clase->schedule ?? []);
    @endphp
    @php
        $emptyTimes = array_fill_keys(array_keys($daysList), ['start' => '', 'end' => '']);
        $initTimes  = array_merge($emptyTimes, array_map(fn($v) => is_array($v) ? $v : ['start' => $v, 'end' => ''], (array)$currentSchedule));
    @endphp
    <div x-data="scheduleSelector({{ Js::from(array_keys(array_filter((array)$currentSchedule))) }}, {{ Js::from($initTimes) }})">
        <label class="block text-sm font-medium text-white/80 mb-2">Horario</label>

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
                           @change="propagate('{{ $key }}')"
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
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Descripción</label>
        <textarea name="description" rows="3"
                  class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white
                         bg-white/10
                         focus:outline-none focus:border-purple-400 focus:bg-white/15">{{ old('description', $clase->description) }}</textarea>
    </div>

    <div class="flex items-center gap-3 py-2">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" class="sr-only peer"
                   {{ old('active', $clase->active) ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer
                        peer-checked:after:translate-x-full peer-checked:after:border-white
                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                        after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                        peer-checked:bg-purple-600"></div>
        </label>
        <span class="text-sm font-medium text-white/80">Curso activo</span>
    </div>

    <div class="pt-2">
        <button type="submit"
                class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-lg">
            Guardar cambios
        </button>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('scheduleSelector', (initialSelected, initialTimes) => ({
        selected: initialSelected,
        times: initialTimes,
        toggle(day) {
            if (this.selected.includes(day)) {
                this.selected = this.selected.filter(function(d) { return d !== day; });
                this.times[day] = { start: '', end: '' };
            } else {
                this.selected.push(day);
                var ref = this.getRef(day);
                this.times[day].start = ref.start;
                this.times[day].end   = ref.end;
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
        propagate(day) {
            var val = this.times[day];
            if (!val.start) return;
            for (var i = 0; i < this.selected.length; i++) {
                var d = this.selected[i];
                if (d !== day && !this.times[d].start) {
                    this.times[d].start = val.start;
                    this.times[d].end   = val.end;
                }
            }
        }
    }));
});
</script>
@endsection
