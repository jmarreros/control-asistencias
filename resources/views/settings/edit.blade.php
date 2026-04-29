@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div>
            <h1 class="text-xl font-bold text-white">Configuración</h1>
            <p class="text-white/60 text-sm">Precios, promociones y notificaciones</p>
        </div>
    </div>
</div>

{{-- Aspecto visual: sólo localStorage, fuera del form --}}
<div class="px-4 pt-4"
     x-data="{
         theme: localStorage.getItem('slm-theme') || 'dark',
         setTheme(t) {
             this.theme = t;
             localStorage.setItem('slm-theme', t);
             var h = document.documentElement;
             if (t === 'light') {
                 h.classList.add('light');
                 h.style.background = '#f0f2f7';
             } else {
                 h.classList.remove('light');
                 h.style.background = '#0a0a14';
             }
         }
     }">
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Aspecto visual</p>
        </div>
        <div class="flex items-center justify-between px-4 py-4">
            <div>
                <p class="text-sm font-medium text-white/90">Tema de la aplicación</p>
                <p class="text-xs text-white/40 mt-0.5"
                   x-text="theme === 'light' ? 'Modo claro activo' : 'Modo oscuro activo'"></p>
            </div>
            <div class="flex items-center gap-1 bg-white/10 rounded-xl p-1">
                <button type="button" @click="setTheme('dark')"
                        :class="theme === 'dark' ? 'bg-indigo-600 text-white shadow' : 'text-white/50'"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                    </svg>
                    Oscuro
                </button>
                <button type="button" @click="setTheme('light')"
                        :class="theme === 'light' ? 'bg-amber-400 text-white shadow' : 'text-white/50'"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    Claro
                </button>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="p-4 space-y-4">
    @csrf

    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Precio por cantidad de horas</p>
        </div>

        @foreach([
            'price_8h'    => '8 horas',
            'price_12h'   => '12 horas',
            'price_full1' => 'Full-1',
            'price_16h'   => '16 horas',
            'price_24h'   => '24 horas',
            'price_full2' => 'Full-2',
        ] as $key => $label)
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 last:border-0">
            <label for="{{ $key }}" class="text-sm font-medium text-white/80">{{ $label }}</label>
            <div class="flex items-center gap-1.5">
                <span class="text-sm text-white/40">S/</span>
                <input type="number"
                       id="{{ $key }}"
                       name="{{ $key }}"
                       value="{{ old($key, $prices[$key]) }}"
                       min="0"
                       step="1"
                       class="w-20 text-right text-sm font-semibold text-white bg-white/10
                              border border-white/20 rounded-lg px-2 py-1.5
                              focus:outline-none focus:ring-2 focus:ring-white/30">
            </div>
        </div>
        @endforeach
    </div>

    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Promociones activas</p>
        </div>

        @foreach([
            'promo_10'  => ['label' => 'Descuento 10%',  'desc' => '10% de descuento sobre el precio del plan'],
            'promo_20'  => ['label' => 'Descuento 20%',  'desc' => '20% de descuento sobre el precio del plan'],
            'promo_30'  => ['label' => 'Descuento 30%',  'desc' => '30% de descuento sobre el precio del plan'],
            'promo_2x1' => ['label' => 'Promoción 2x1',  'desc' => 'Dos alumnos por el precio de uno'],
        ] as $key => $promo)
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 last:border-0">
            <div>
                <p class="text-sm font-medium text-white/90">{{ $promo['label'] }}</p>
                <p class="text-xs text-white/40 mt-0.5">{{ $promo['desc'] }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer ml-4 shrink-0">
                <input type="hidden" name="{{ $key }}" value="0">
                <input type="checkbox" name="{{ $key }}" value="1" class="sr-only peer"
                       {{ old($key, $promos[$key]) ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-white/20 rounded-full peer
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                            peer-checked:bg-emerald-500"></div>
            </label>
        </div>
        @endforeach
    </div>

    {{-- Reportes --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Reportes</p>
        </div>
        <div class="flex items-center justify-between px-4 py-4">
            <div>
                <p class="text-sm font-medium text-white/90">Mostrar reporte de ganancias</p>
                <p class="text-xs text-white/40 mt-0.5">Habilita la sección de ingresos en Reportes</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer ml-4 shrink-0">
                <input type="hidden" name="show_earnings" value="0">
                <input type="checkbox" name="show_earnings" value="1" class="sr-only peer"
                       {{ old('show_earnings', $reports['show_earnings']) ? 'checked' : '' }}>
                <div class="w-11 h-6 bg-white/20 rounded-full peer
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                            after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                            peer-checked:bg-emerald-500"></div>
            </label>
        </div>
    </div>

    {{-- Notificaciones WhatsApp --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Notificaciones WhatsApp</p>
            <p class="text-xs text-white/30 mt-0.5">Filtro "Por vencer" en la lista de alumnos</p>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <div>
                <label for="notify_days_before" class="text-sm font-medium text-white/80">Días antes del vencimiento</label>
                <p class="text-xs text-white/40 mt-0.5">Avisar cuando falten N días o menos</p>
            </div>
            <input type="number"
                   id="notify_days_before"
                   name="notify_days_before"
                   value="{{ old('notify_days_before', $notify['notify_days_before']) }}"
                   min="0" max="30"
                   class="w-16 text-right text-sm font-semibold text-white bg-white/10
                          border border-white/20 rounded-lg px-2 py-1.5
                          focus:outline-none focus:ring-2 focus:ring-white/30">
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <div>
                <label for="notify_classes_remaining" class="text-sm font-medium text-white/80">Clases restantes</label>
                <p class="text-xs text-white/40 mt-0.5">Avisar cuando queden N clases o menos</p>
            </div>
            <input type="number"
                   id="notify_classes_remaining"
                   name="notify_classes_remaining"
                   value="{{ old('notify_classes_remaining', $notify['notify_classes_remaining']) }}"
                   min="0" max="10"
                   class="w-16 text-right text-sm font-semibold text-white bg-white/10
                          border border-white/20 rounded-lg px-2 py-1.5
                          focus:outline-none focus:ring-2 focus:ring-white/30">
        </div>

        <div class="px-4 py-3">
            <label for="notify_message" class="text-sm font-medium text-white/80 block mb-1.5">Mensaje</label>
            <p class="text-xs text-white/40 mb-2">Variables disponibles: <code class="text-amber-400">{nombre}</code> · <code class="text-amber-400">{clases}</code> · <code class="text-amber-400">{fecha}</code></p>
            <textarea id="notify_message"
                      name="notify_message"
                      rows="3"
                      maxlength="255"
                      class="w-full text-sm text-white bg-white/10 border border-white/20 rounded-lg px-3 py-2
                             focus:outline-none focus:ring-2 focus:ring-white/30 resize-none"
                      placeholder="Hola {nombre}, tu plan vence el {fecha}...">{{ old('notify_message', $notify['notify_message']) }}</textarea>
        </div>

        <div class="px-4 py-3 border-t border-white/10">
            <label for="notify_expired_message" class="text-sm font-medium text-white/80 block mb-1.5">Mensaje para plan vencido</label>
            <p class="text-xs text-white/40 mb-2">Variables disponibles: <code class="text-amber-400">{nombre}</code> · <code class="text-amber-400">{fecha}</code></p>
            <textarea id="notify_expired_message"
                      name="notify_expired_message"
                      rows="3"
                      maxlength="255"
                      class="w-full text-sm text-white bg-white/10 border border-white/20 rounded-lg px-3 py-2
                             focus:outline-none focus:ring-2 focus:ring-white/30 resize-none"
                      placeholder="Hola {nombre}, tu plan venció el {fecha}...">{{ old('notify_expired_message', $notify['notify_expired_message']) }}</textarea>
        </div>
    </div>

    {{-- Cambiar PIN admin --}}
    <div class="rounded-xl overflow-hidden border border-white/20"
         x-data="{ open: false }">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Cambiar PIN de administrador</p>
            <svg class="w-4 h-4 text-white/40 transition-transform duration-200"
                 :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-transition>
            <div class="px-4 py-3 border-b border-white/10">
                <label for="current_pin" class="text-sm font-medium text-white/80 block mb-1">PIN actual</label>
                <input type="password" id="current_pin" name="current_pin"
                       inputmode="numeric" maxlength="8" autocomplete="current-password"
                       placeholder="••••"
                       class="w-full border border-white/20 rounded-xl px-4 py-3 text-base text-white placeholder-white/30
                              bg-white/10 focus:outline-none focus:border-indigo-400
                              @error('current_pin') border-red-400 @enderror">
                @error('current_pin')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="px-4 py-3 border-b border-white/10">
                <label for="new_pin" class="text-sm font-medium text-white/80 block mb-1">PIN nuevo</label>
                <input type="password" id="new_pin" name="new_pin"
                       inputmode="numeric" maxlength="8" autocomplete="new-password"
                       placeholder="••••"
                       class="w-full border border-white/20 rounded-xl px-4 py-3 text-base text-white placeholder-white/30
                              bg-white/10 focus:outline-none focus:border-indigo-400
                              @error('new_pin') border-red-400 @enderror">
                @error('new_pin')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="px-4 py-3">
                <label for="new_pin_confirmation" class="text-sm font-medium text-white/80 block mb-1">Confirmar PIN nuevo</label>
                <input type="password" id="new_pin_confirmation" name="new_pin_confirmation"
                       inputmode="numeric" maxlength="8" autocomplete="new-password"
                       placeholder="••••"
                       class="w-full border border-white/20 rounded-xl px-4 py-3 text-base text-white placeholder-white/30
                              bg-white/10 focus:outline-none focus:border-indigo-400">
                <p class="text-xs text-white/30 mt-1.5">Entre 4 y 8 dígitos. Dejar vacío para no cambiar.</p>
            </div>
        </div>
    </div>

    <button type="submit"
            class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-base">
        Guardar configuración
    </button>
</form>

{{-- Importar datos --}}
<div class="px-4 pb-6 pt-2">
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Importar datos</p>
        </div>
        <div class="flex items-center justify-between px-4 py-4">
            <div>
                <p class="text-sm font-medium text-white/90">Importar alumnos desde CSV</p>
                <p class="text-xs text-white/40 mt-0.5">Carga masiva de alumnos y planes</p>
            </div>
            <a href="{{ route('import.show') }}"
               class="flex items-center gap-2 bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Importar
            </a>
        </div>
    </div>
</div>
@endsection
