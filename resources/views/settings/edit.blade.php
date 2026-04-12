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

    <button type="submit"
            class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-base">
        Guardar configuración
    </button>
</form>
@endsection
