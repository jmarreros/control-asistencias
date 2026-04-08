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
            <p class="text-white/60 text-sm">Precios por defecto</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="p-4 space-y-4">
    @csrf

    <div class="bg-white/10 backdrop-blur-sm rounded-xl overflow-hidden border border-white/15">
        <div class="px-4 py-3 bg-white/5 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Precio por cantidad de horas</p>
        </div>

        @foreach([
            'price_8h'   => '8 horas',
            'price_12h'  => '12 horas',
            'price_16h'  => '16 horas',
            'price_full' => 'Full',
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

    <div class="bg-white/10 backdrop-blur-sm rounded-xl overflow-hidden border border-white/15">
        <div class="px-4 py-3 bg-white/5 border-b border-white/10">
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

    <button type="submit"
            class="w-full bg-white/20 border border-white/30 text-white font-bold py-4 rounded-xl text-base">
        Guardar configuración
    </button>
</form>
@endsection
