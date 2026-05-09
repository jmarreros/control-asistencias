@extends('layouts.app')

@push('head')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
<div
    x-data="{
        claseId: '{{ $detected?->id ?? '' }}',
        isAutoDetected: {{ $detected ? 'true' : 'false' }},
        clases: {{ $clases->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name, 'schedule' => $c->schedule])->toJson() }},
        get claseImage() {
            if (!this.claseId) return null;
            var c = this.clases.find(c => c.id === this.claseId);
            if (!c) return null;
            var n = c.name.toLowerCase();
            if (n.indexOf('salsa') !== -1) return '{{ asset('images/salsa.jpg') }}';
            if (n.indexOf('bachata') !== -1) return '{{ asset('images/bachata.jpg') }}';
            if (n.indexOf('lady') !== -1) return '{{ asset('images/lady.jpg') }}';
            return null;
        },
        get claseScheduleText() {
            if (!this.claseId) return null;
            var c = this.clases.find(c => c.id === this.claseId);
            if (!c || !c.schedule) return null;
            var labels = {lun:'Lun', mar:'Mar', mie:'Mié', jue:'Jue', vie:'Vie', sab:'Sáb', dom:'Dom'};
            var groups = {};
            for (var day in c.schedule) {
                var times = c.schedule[day];
                var start = times.start || '';
                var end = times.end || '';
                var key = start + '|' + end;
                if (!groups[key]) groups[key] = [];
                groups[key].push(labels[day] || day);
            }
            function fmt(t) {
                if (!t) return '';
                var p = t.split(':');
                var h = parseInt(p[0]), m = p[1];
                var ampm = h >= 12 ? 'pm' : 'am';
                h = h % 12 || 12;
                return h + ':' + m + ampm;
            }
            return Object.entries(groups).map(function([key, days]) {
                var parts = key.split('|');
                var timeStr = fmt(parts[0]) + (parts[1] ? ' - ' + fmt(parts[1]) : '');
                return days.join(' · ') + ' (' + timeStr + ')';
            }).join('  ·  ');
        },
        dni: '',
        loading: false,
        feedback: null,
        feedbackTimer: null,
        sessionList: [],
        confirmingId: null,
        checkinUrl: '{{ route('checkin.store') }}',
        removeUrl: '{{ route('checkin.destroy') }}',
        attendancesUrl: '{{ route('checkin.attendances') }}',
        csrfToken: '{{ csrf_token() }}',

        async submitDni() {
            var d = this.dni.trim();
            if (!d) return;
            if (!this.claseId) {
                this.showFeedback({ status: 'no_clase' });
                this.dni = '';
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(this.checkinUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ dni: d, clase_id: this.claseId }),
                });
                const data = await res.json();
                this.showFeedback(data);
                if (data.status === 'ok' || data.status === 'already') {
                    this.addToSession(data);
                }
            } catch {
                this.showFeedback({ status: 'error' });
            } finally {
                this.loading = false;
                this.dni = '';
                this.$nextTick(() => { if (this.$refs.dniInput) this.$refs.dniInput.focus(); });
            }
        },

        showFeedback(data) {
            this.feedback = data;
            clearTimeout(this.feedbackTimer);
            this.feedbackTimer = setTimeout(() => { this.feedback = null; }, 3000);
        },

        addToSession(data) {
            var now = new Date();
            var time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            this.sessionList.unshift({ studentId: data.student_id, time: time, name: data.name, status: data.status, notEnrolled: data.not_enrolled || false });
        },

        async removeEntry(entry) {
            if (!entry.studentId || !this.claseId) return;
            this.confirmingId = null;
            try {
                await fetch(this.removeUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ student_id: entry.studentId, clase_id: this.claseId }),
                });
            } catch {}
            this.sessionList = this.sessionList.filter(e => e.studentId !== entry.studentId);
            this.$nextTick(() => { if (this.$refs.dniInput) this.$refs.dniInput.focus(); });
        },

        get sortedSessionList() {
            return [...this.sessionList].sort((a, b) => b.time.localeCompare(a.time));
        },

        async loadAttendances() {
            if (!this.claseId) {
                this.sessionList = [];
                return;
            }
            try {
                const res = await fetch(this.attendancesUrl + '?clase_id=' + this.claseId, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                });
                this.sessionList = await res.json();
            } catch {}
        },

    }"
    x-init="$nextTick(() => { if ($refs.dniInput) $refs.dniInput.focus(); }); loadAttendances()"
    @click="if ($refs.dniInput && document.activeElement !== $refs.dniInput) $refs.dniInput.focus()"
>

    {{-- Cabecera --}}
    <div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('dashboard') }}" class="text-white/60 hover:text-white shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo">
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold text-white">Registrar Asistencias por DNI</h1>
                <p class="text-white/50 text-xs">{{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</p>
            </div>
        </div>

        {{-- Selector de curso --}}
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <label class="text-xs text-white/50 font-medium uppercase tracking-wide">Curso activo</label>
                <span
                    :class="isAutoDetected && claseId ? 'bg-green-500/20 text-green-300 border-green-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30'"
                    class="text-xs font-medium px-2 py-0.5 rounded-full border"
                    x-text="isAutoDetected && claseId ? 'En horario' : 'Selección manual'">
                </span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full shrink-0 overflow-hidden border border-white/20 bg-white/10">
                    <img x-show="claseImage" :src="claseImage" class="w-full h-full object-cover" alt="">
                </div>
                <select
                    x-model="claseId"
                    @click.stop
                    @change="isAutoDetected = false; loadAttendances()"
                    class="flex-1 bg-white/10 border border-white/20 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 min-w-0">
                    <option value="" class="bg-gray-900 text-white/50">— Sin curso seleccionado —</option>
                    @foreach($clases as $clase)
                        <option value="{{ $clase->id }}" class="bg-gray-900">{{ $clase->name }}</option>
                    @endforeach
                </select>
            </div>
            <p x-show="claseId && claseScheduleText"
               x-text="claseScheduleText"
               class="text-white/40 text-xs mt-2" style="padding-left:60px"></p>
        </div>
    </div>

    {{-- Campo DNI --}}
    <div class="px-4 pt-8 pb-2">
        <p class="text-center text-white/30 text-xs uppercase tracking-widest mb-3">Ingresar DNI y presionar Enter</p>
        <div class="relative">
            <input
                type="text"
                x-ref="dniInput"
                x-model="dni"
                @keydown.enter.prevent="submitDni()"
                @click.stop
                inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                maxlength="12"
                :disabled="loading"
                placeholder="00000000"
                class="w-full text-center text-4xl font-bold tracking-[0.25em] bg-white/10 border-2 border-white/20 text-white placeholder-white/15 rounded-2xl px-4 py-5 focus:outline-none focus:border-teal-400 transition-colors disabled:opacity-50">
            <div x-show="loading" class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-6 h-6 text-white/40 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Feedback --}}
    <div class="px-4 pt-4 pb-2" style="min-height:7rem;">
        <div
            x-show="feedback"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">

            <div x-show="feedback && feedback.status === 'ok' && !feedback.not_enrolled"
                 class="bg-green-500/20 border border-green-500/40 rounded-2xl px-4 py-4 text-center">
                <p class="text-green-300 text-2xl font-bold" x-text="'Bienvenido/a, ' + (feedback ? feedback.name : '')"></p>
                <p class="text-green-400/70 text-sm mt-1">Asistencia registrada</p>
            </div>

            <div x-show="feedback && feedback.status === 'ok' && feedback.not_enrolled"
                 class="bg-green-500/15 border border-orange-500/30 rounded-2xl px-4 py-4 text-center">
                <p class="text-green-300 text-2xl font-bold" x-text="'Bienvenido/a, ' + (feedback ? feedback.name : '')"></p>
                <p class="text-orange-300/80 text-xs mt-1">No estaba inscrito/a en este curso — fue añadido/a automáticamente</p>
            </div>

            <div x-show="feedback && feedback.status === 'already'"
                 class="bg-amber-500/20 border border-amber-500/40 rounded-2xl px-4 py-4 text-center">
                <p class="text-amber-300 text-2xl font-bold" x-text="feedback ? feedback.name : ''"></p>
                <p class="text-amber-400/70 text-sm mt-1">Ya registraste tu asistencia hoy</p>
            </div>

            <div x-show="feedback && feedback.status === 'not_found'"
                 class="bg-red-500/20 border border-red-500/40 rounded-2xl px-4 py-4 text-center">
                <p class="text-red-300 text-2xl font-bold">DNI no encontrado</p>
                <p class="text-red-400/70 text-sm mt-1">Verifica el número ingresado</p>
            </div>

            <div x-show="feedback && feedback.status === 'no_clase'"
                 class="bg-red-500/20 border border-red-500/40 rounded-2xl px-4 py-4 text-center">
                <p class="text-red-300 text-xl font-bold">Selecciona un curso primero</p>
            </div>

            <div x-show="feedback && feedback.status === 'error'"
                 class="bg-red-500/20 border border-red-500/40 rounded-2xl px-4 py-4 text-center">
                <p class="text-red-300 text-xl font-bold">Error de conexión</p>
                <p class="text-red-400/70 text-sm mt-1">Intenta de nuevo</p>
            </div>
        </div>
    </div>

    {{-- Lista de la sesión --}}
    <div class="px-4 pb-6">
        <h2 class="text-xs font-semibold text-white/40 uppercase tracking-wide mb-3">
            Registros de esta sesión
            <span class="ml-1 text-white/50" x-text="'(' + sessionList.length + ')'"></span>
        </h2>

        <div x-show="sessionList.length === 0" class="text-center py-8 text-white/20 text-sm">
            Aún no hay registros en esta sesión
        </div>

        <div class="space-y-2">
            <template x-for="entry in sortedSessionList" :key="entry.studentId">
                <div class="bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 overflow-hidden">

                    {{-- Vista normal --}}
                    <div x-show="confirmingId !== entry.studentId"
                         class="flex items-center gap-3">
                        <span class="text-white/40 text-xs font-mono shrink-0" x-text="entry.time"></span>
                        <span class="flex-1 text-sm text-white font-medium truncate" x-text="entry.name"></span>
                        <span x-show="entry.status === 'ok'"
                              class="text-xs text-green-400 bg-green-500/20 px-2 py-0.5 rounded-full shrink-0 whitespace-nowrap">
                            Presente
                        </span>
                        <span x-show="entry.status === 'already'"
                              class="text-xs text-amber-400 bg-amber-500/20 px-2 py-0.5 rounded-full shrink-0 whitespace-nowrap">
                            Ya registrado
                        </span>
                        <button type="button"
                                @click.stop="confirmingId = entry.studentId"
                                title="Eliminar asistencia"
                                class="text-white/20 hover:text-red-400 transition-colors shrink-0 ml-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Confirmación inline --}}
                    <div x-show="confirmingId === entry.studentId"
                         class="flex items-center gap-2">
                        <span class="flex-1 text-xs text-red-300" x-text="'¿Eliminar asistencia de ' + entry.name + '?'"></span>
                        <button type="button"
                                @click.stop="removeEntry(entry)"
                                class="text-xs font-semibold text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-lg shrink-0">
                            Sí, eliminar
                        </button>
                        <button type="button"
                                @click.stop="confirmingId = null"
                                class="text-xs text-white/50 hover:text-white px-2 py-1 shrink-0">
                            Cancelar
                        </button>
                    </div>

                </div>
            </template>
        </div>
    </div>

</div>
@endsection
