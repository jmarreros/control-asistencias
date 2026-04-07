@extends('layouts.app')

@section('content')
<div class="bg-gray-700 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-white">Configuración</h1>
            <p class="text-gray-300 text-sm">Precios por defecto</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="p-4 space-y-4">
    @csrf

    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Precio por cantidad de horas</p>
        </div>

        @foreach([
            'price_8h'   => '8 horas',
            'price_12h'  => '12 horas',
            'price_16h'  => '16 horas',
            'price_full' => 'Full',
        ] as $key => $label)
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <label for="{{ $key }}" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
            <div class="flex items-center gap-1.5">
                <span class="text-sm text-gray-400">S/</span>
                <input type="number"
                       id="{{ $key }}"
                       name="{{ $key }}"
                       value="{{ old($key, $prices[$key]) }}"
                       min="0"
                       step="1"
                       class="w-20 text-right text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700
                              border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5
                              focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
        </div>
        @endforeach
    </div>

    <button type="submit"
            class="w-full bg-gray-700 dark:bg-gray-600 text-white font-bold py-4 rounded-xl text-base">
        Guardar configuración
    </button>
</form>
@endsection
