@extends('layouts.student')

@section('content')
<div x-data="{
    dni: '',
    loading: false,
    searched: false,
    found: false,
    message: '',
    student: null,
    plan: null,
    init() {
        this.$watch('dni', val => {
            if (val.trim() === '') {
                this.searched = false;
                this.found    = false;
                this.student  = null;
                this.plan     = null;
            }
        });
    },
    async search() {
        var q = this.dni.trim();
        if (!q) return;
        this.loading  = true;
        this.searched = false;
        try {
            const res  = await fetch('{{ route('student.lookup') }}?dni=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.found   = data.found;
            this.message = data.message ?? '';
            this.student = data.found ? data : null;
            this.plan    = data.found ? data.plan : null;
        } catch {
            this.found   = false;
            this.message = 'Error de conexión. Intenta nuevamente.';
        } finally {
            this.loading  = false;
            this.searched = true;
        }
    }
}" @keydown.enter.window="if (document.activeElement.name === 'dni') search()">

    {{-- Cabecera --}}
    <div class="px-4 pt-10 pb-6 text-center">
        <img src="{{ asset('images/logo-xs.jpg') }}" class="w-20 h-20 rounded-full object-cover mx-auto mb-4 shadow-lg" style="box-shadow:0 0 30px black" alt="Logo">
        <h1 class="text-2xl font-bold text-white">Salsa Latin Motion</h1>
        <p class="text-white/50 text-sm mt-1">Consulta tu plan de clases</p>
    </div>

    {{-- Buscador --}}
    <div class="px-4 pb-6">
        <div class="bg-black/40 border border-white/20 rounded-2xl p-4">
            <label class="block text-sm font-medium text-white/70 mb-2">Ingresa tu DNI</label>
            <div class="flex gap-2">
                <input type="text" name="dni"
                       x-model="dni"
                       inputmode="numeric"
                       maxlength="12"
                       placeholder="Ej: 12345678"
                       autocomplete="off"
                       class="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white text-base
                              placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <button type="button" @click="search()"
                        :disabled="loading || !dni.trim()"
                        class="bg-indigo-600 text-white font-semibold px-5 py-3 rounded-xl shrink-0
                               disabled:opacity-40 disabled:cursor-not-allowed active:bg-indigo-700">
                    <span x-show="!loading">Buscar</span>
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- No encontrado --}}
    <div x-show="searched && !found" class="px-4 pb-4">
        <div class="bg-red-500/15 border border-red-500/30 rounded-2xl px-4 py-4 text-center">
            <svg class="w-8 h-8 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
            </svg>
            <p class="text-red-300 text-sm font-medium" x-text="message"></p>
        </div>
    </div>

    {{-- Resultado --}}
    <div x-show="searched && found" class="px-4 pb-6 space-y-3">

        {{-- Nombre del alumno --}}
        <div class="bg-white/5 backdrop-blur-sm border border-white/20 rounded-2xl px-4 py-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-indigo-500/30 border border-indigo-400/20 flex items-center justify-center shrink-0">
                <span class="text-indigo-300 font-bold text-lg"
                      x-text="student ? student.name.charAt(0).toUpperCase() : ''"></span>
            </div>
            <div>
                <p class="text-white font-semibold text-lg" x-text="student ? student.name : ''"></p>
                <p class="text-white/50 text-xs mt-0.5">Alumno activo</p>
            </div>
        </div>

        {{-- Sin plan --}}
        <div x-show="!plan" class="bg-white/5 border border-white/10 rounded-2xl px-4 py-5 text-center">
            <p class="text-white/50 text-sm">No tienes un plan registrado actualmente.</p>
        </div>

        {{-- Plan activo --}}
        <div x-show="plan" class="bg-white/5 backdrop-blur-sm border border-white/20 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between">
                <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Plan actual</p>
                <span x-show="plan && plan.status === 'ok'"
                      class="text-xs font-medium text-green-300 bg-green-500/20 px-2 py-0.5 rounded-full">Activo</span>
                <span x-show="plan && plan.status === 'exhausted'"
                      class="text-xs font-medium text-orange-300 bg-orange-500/20 px-2 py-0.5 rounded-full">Clases agotadas</span>
                <span x-show="plan && plan.status === 'expired'"
                      class="text-xs font-medium text-red-300 bg-red-500/20 px-2 py-0.5 rounded-full">Vencido</span>
                <span x-show="plan && plan.status === 'pending'"
                      class="text-xs font-medium text-blue-300 bg-blue-500/20 px-2 py-0.5 rounded-full">Pendiente</span>
            </div>

            <div class="divide-y divide-white/10">
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-white/60">Tipo</span>
                    <span class="text-sm font-medium text-white" x-text="plan ? plan.quota_label : ''"></span>
                </div>
                <div x-show="plan && plan.remaining !== null" class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-white/60">Clases restantes</span>
                    <span class="text-2xl font-bold"
                          :class="plan && plan.remaining <= 1 ? 'text-orange-400' : 'text-teal-300'"
                          x-text="plan ? plan.remaining : ''"></span>
                </div>
                <div x-show="plan && plan.remaining === null" class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-white/60">Clases</span>
                    <span class="text-sm font-medium text-teal-300">Ilimitadas</span>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-white/60">Inicio</span>
                    <span class="text-sm font-medium text-white" x-text="plan ? plan.start_date : ''"></span>
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-white/60">Vencimiento</span>
                    <span class="text-sm font-medium text-white" x-text="plan ? plan.end_date : ''"></span>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="px-4 pb-8 text-center space-y-2">
        <p class="text-white/20 text-xs">Salsa Latin Motion · Control de Asistencias</p>
        <a href="{{ route('login') }}" class="inline-block text-white/30 text-xs hover:text-white/60 transition-colors">
            Acceso administración
        </a>
    </div>

</div>
@endsection
