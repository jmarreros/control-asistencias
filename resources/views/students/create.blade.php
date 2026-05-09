@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
    <h1 class="text-xl font-bold text-white">Nuevo Alumno</h1>
</div>

<form method="POST" action="{{ route('students.store') }}" x-data="studentCreate" class="p-4 space-y-4">
    @csrf

    <input type="hidden" name="inactive_student_id" x-bind:value="selectedId">

    {{-- Banner: reactivación de inactivo --}}
    <div x-show="selectedId !== null"
         class="flex items-start gap-3 px-4 py-3 rounded-xl bg-amber-500/15 border border-amber-400/30">
        <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-amber-300 text-sm font-medium">Alumno encontrado en el sistema</p>
            <p class="text-amber-200/70 text-xs mt-0.5">Al confirmar, se reactivará el alumno existente.</p>
        </div>
        <button type="button" @click="clear()"
                class="text-amber-400/70 hover:text-amber-300 text-xs underline shrink-0">
            No es este alumno
        </button>
    </div>

    {{-- Banner: DNI duplicado en alumno activo --}}
    <div x-show="dniConflict !== null"
         class="flex items-start gap-3 px-4 py-3 rounded-xl bg-red-500/15 border border-red-400/30">
        <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="min-w-0">
            <p class="text-red-300 text-sm font-medium">Este DNI ya está registrado</p>
            <p class="text-red-200/70 text-xs mt-0.5">
                Pertenece a <span class="font-semibold" x-text="dniConflict ? dniConflict.name : ''"></span>.
            </p>
        </div>
    </div>

    {{-- Nombre --}}
    <div class="relative">
        <label class="block text-sm font-medium text-white/80 mb-1">Nombre completo *</label>
        <input type="text" name="name"
               x-model="name"
               @input="onNameInput()"
               @blur="normalizeName()"
               @focus="showSuggestions = suggestions.length > 0"
               @click.away="showSuggestions = false"
               :readonly="selectedId !== null"
               required autofocus autocomplete="off"
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      read-only:opacity-60 read-only:cursor-default
                      @error('name') border-red-400 @enderror">

        {{-- Dropdown de sugerencias (alumnos inactivos) --}}
        <div x-show="showSuggestions && suggestions.length > 0"
             class="absolute left-0 right-0 mt-1 rounded-xl overflow-hidden border border-white/20 shadow-xl"
             style="z-index:200; background:rgba(30,30,50,0.97);">
            <template x-for="s in suggestions" :key="s.id">
                <button type="button" @click="select(s)"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-white/10 border-b border-white/10 last:border-0">
                    <div class="w-8 h-8 rounded-full bg-indigo-500/30 border border-indigo-400/30 flex items-center justify-center
                                text-indigo-300 font-bold text-xs shrink-0"
                         x-text="s.name.charAt(0)"></div>
                    <div class="min-w-0">
                        <p class="text-white text-sm font-medium truncate" x-text="s.name"></p>
                        <p class="text-white/40 text-xs" x-text="[s.dni, s.phone].filter(Boolean).join(' · ')"></p>
                    </div>
                    <span class="ml-auto text-xs text-amber-400 shrink-0">Inactivo</span>
                </button>
            </template>
        </div>

        @if(!$errors->has('name'))
            <p x-show="name.length > 0 && !hasValidName && selectedId === null"
               class="text-red-400 text-xs mt-1">El nombre debe contener al menos dos palabras.</p>
        @endif
        @error('name')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Advertencia: nombre similar a alumno activo --}}
    <div x-show="activeNameConflicts.length > 0 && !nameConflictConfirmed"
         class="px-4 py-3 rounded-xl bg-yellow-500/10 border border-yellow-400/30 space-y-2">
        <div class="flex items-center justify-between gap-2">
            <p class="text-yellow-300 text-sm font-medium">Nombre similar a un alumno existente</p>
            <button type="button" @click="nameConflictConfirmed = true"
                    class="text-xs text-yellow-300 font-medium underline shrink-0">
                Es otro alumno
            </button>
        </div>
        <template x-for="s in activeNameConflicts" :key="s.id">
            <div class="flex items-center gap-2">
                <p class="text-yellow-200/70 text-xs truncate flex-1" x-text="'· ' + s.name + (s.dni ? ' — DNI ' + s.dni : '')"></p>
                <a :href="s.edit_url" class="text-xs text-yellow-300 font-medium underline shrink-0">Recuperar alumno</a>
            </div>
        </template>
    </div>

    {{-- DNI --}}
    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">DNI *</label>
        <input type="text" name="dni"
               x-model="dni"
               @input="onDniInput()"
               :readonly="selectedId !== null"
               :required="selectedId === null"
               maxlength="12" autocomplete="off"
               placeholder="Ej. 12345678"
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      read-only:opacity-60 read-only:cursor-default
                      @error('dni') border-red-400 @enderror">
        @error('dni')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- WhatsApp --}}
    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">WhatsApp *</label>
        <input type="tel" name="phone"
               x-model="phone"
               :readonly="selectedId !== null"
               placeholder="+51 987 654 321"
               class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15
                      read-only:opacity-60 read-only:cursor-default
                      @error('phone') border-red-400 @enderror">
        @error('phone')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Notas (solo para alumnos nuevos) --}}
    <div x-show="selectedId === null">
        <label class="block text-sm font-medium text-white/80 mb-1">Notas</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-white/50 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                         bg-white/10 focus:outline-none focus:border-indigo-400 focus:bg-white/15">{{ old('notes') }}</textarea>
    </div>

    <div class="pt-2">
        <button type="submit"
                :disabled="!canSubmit"
                :class="!canSubmit
                    ? 'bg-white/10 text-white/30 cursor-not-allowed'
                    : (selectedId !== null ? 'bg-indigo-600' : 'bg-emerald-600')"
                class="w-full font-bold py-4 rounded-xl text-lg transition-colors text-white">
            <span x-text="selectedId !== null ? 'Reactivar alumno' : 'Registrar alumno'"></span>
        </button>
    </div>
</form>

<script>
(function () {
    function register() {
        Alpine.data('studentCreate', () => ({
            name:     '{{ old('name', '') }}',
            dni:      '{{ old('dni', '') }}',
            phone:    '{{ old('phone', '+51') }}',
            selectedId:            null,
            showSuggestions:       false,
            nameConflictConfirmed: false,
            existingStudents:      @json($existingStudents),

            get inactive() {
                return this.existingStudents.filter(s => !s.active);
            },

            get suggestions() {
                if (this.name.length < 2 || this.selectedId !== null) return [];
                const q = this.name.toLowerCase();
                return this.inactive
                    .filter(s => s.name.toLowerCase().includes(q))
                    .slice(0, 6);
            },

            get dniConflict() {
                if (this.selectedId !== null) return null;
                const d = this.dni.trim();
                if (d.length < 8) return null;
                return this.existingStudents.find(s => s.dni === d) || null;
            },

            get activeNameConflicts() {
                if (this.name.length < 3 || this.selectedId !== null) return [];
                const q = this.name.toLowerCase().trim();
                return this.existingStudents.filter(s => s.active && s.name.toLowerCase().includes(q));
            },

            get hasValidName() {
                return this.name.trim().split(/\s+/).filter(Boolean).length >= 2;
            },

            get canSubmit() {
                if (this.selectedId !== null) return true;
                if (!this.hasValidName) return false;
                if (this.dniConflict !== null) return false;
                if (this.activeNameConflicts.length > 0 && !this.nameConflictConfirmed) return false;
                return true;
            },

            normalizeName() {
                if (this.selectedId !== null) return;
                this.name = this.name
                    .toLowerCase()
                    .split(' ')
                    .map(w => w.length > 0 ? w.charAt(0).toUpperCase() + w.slice(1) : w)
                    .join(' ');
            },

            onNameInput() {
                if (this.selectedId !== null) this.selectedId = null;
                this.nameConflictConfirmed = false;
                this.showSuggestions = this.suggestions.length > 0;
            },

            onDniInput() {
                const conflict = this.dniConflict;
                if (conflict && !conflict.active) {
                    this.select(conflict);
                }
            },

            select(student) {
                this.name            = student.name;
                this.dni             = student.dni   ?? '';
                this.phone           = student.phone  ?? '+51';
                this.selectedId      = student.id;
                this.showSuggestions = false;
            },

            clear() {
                this.name                  = '';
                this.dni                   = '';
                this.phone                 = '+51';
                this.selectedId            = null;
                this.showSuggestions       = false;
                this.nameConflictConfirmed = false;
                this.$nextTick(() => this.$el.querySelector('[name=name]').focus());
            },
        }));
    }

    if (window.Alpine) {
        register();
    } else {
        document.addEventListener('alpine:init', register);
    }
})();
</script>
@endsection
