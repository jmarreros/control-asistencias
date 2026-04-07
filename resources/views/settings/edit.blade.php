@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo">
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

    <button type="submit"
            class="w-full bg-white/20 border border-white/30 text-white font-bold py-4 rounded-xl text-base">
        Guardar configuración
    </button>
</form>
@endsection
