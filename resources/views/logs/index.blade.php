@extends('layouts.app')

@push('head')
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('settings.edit') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div>
            <h1 class="text-xl font-bold text-white">Logs de acceso</h1>
            <p class="text-white/60 text-sm">{{ number_format($total) }} registros en total</p>
        </div>
    </div>
</div>

<div class="px-4 pt-4 pb-28"
     x-data="{ showDelete: false }">

    {{-- Filtros --}}
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('logs.index') }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $type === 'all' ? 'bg-indigo-600 text-white' : 'bg-white/10 text-white/70' }}">
            Todos
        </a>
        <a href="{{ route('logs.index', ['type' => 'admin']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $type === 'admin' ? 'bg-indigo-600 text-white' : 'bg-white/10 text-white/70' }}">
            Admin
        </a>
        <a href="{{ route('logs.index', ['type' => 'portal']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $type === 'portal' ? 'bg-indigo-600 text-white' : 'bg-white/10 text-white/70' }}">
            Portal
        </a>

        @if($total > 0)
        <button @click="showDelete = true"
                class="ml-auto px-3 py-1.5 rounded-lg text-sm font-medium text-white flex items-center gap-1.5"
                style="background-color:#dc2626;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Borrar
        </button>
        @endif
    </div>

    {{-- Lista de logs --}}
    @forelse($logs as $log)
    @php
        $colors = match($log->action) {
            'login'        => ['dot' => '#34d399', 'badge_bg' => 'rgba(6,78,59,0.5)',   'badge_text' => '#6ee7b7'],
            'logout'       => ['dot' => '#94a3b8', 'badge_bg' => 'rgba(30,41,59,0.5)',  'badge_text' => '#cbd5e1'],
            'login_failed' => ['dot' => '#f87171', 'badge_bg' => 'rgba(127,29,29,0.5)', 'badge_text' => '#fca5a5'],
            'page_visit'   => ['dot' => '#818cf8', 'badge_bg' => 'rgba(49,46,129,0.5)', 'badge_text' => '#a5b4fc'],
            'dni_lookup'   => ['dot' => '#fbbf24', 'badge_bg' => 'rgba(120,53,15,0.5)', 'badge_text' => '#fde68a'],
            default        => ['dot' => 'rgba(255,255,255,0.4)', 'badge_bg' => 'rgba(255,255,255,0.1)', 'badge_text' => 'rgba(255,255,255,0.6)'],
        };
        $labels = [
            'login'        => 'Login',
            'logout'       => 'Logout',
            'login_failed' => 'Login fallido',
            'page_visit'   => 'Página',
            'dni_lookup'   => 'Consulta DNI',
        ];
    @endphp
    <div class="flex items-start gap-3 py-3 border-b border-white/10">
        <div class="mt-1.5 w-2 h-2 rounded-full shrink-0" style="background-color:{{ $colors['dot'] }};"></div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                      style="background-color:{{ $colors['badge_bg'] }}; color:{{ $colors['badge_text'] }};">
                    {{ $labels[$log->action] ?? $log->action }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-white/10 text-white/50">
                    {{ $log->type === 'admin' ? 'Admin' : 'Portal' }}
                </span>
            </div>
            @if($log->detail)
            <p class="text-sm text-white/80 mt-1 truncate">{{ $log->detail }}</p>
            @endif
            <p class="text-xs text-white/40 mt-0.5">
                {{ $log->created_at->format('d/m/Y H:i:s') }}
                @if($log->ip) · {{ $log->ip }} @endif
            </p>
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-white/40">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">No hay logs registrados aún.</p>
    </div>
    @endforelse

    {{-- Paginación --}}
    @if($logs->hasPages())
    <div class="mt-4 flex justify-center gap-2">
        @if($logs->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg text-sm bg-white/5 text-white/30">← Anterior</span>
        @else
            <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm bg-white/10 text-white/70">← Anterior</a>
        @endif

        <span class="px-3 py-1.5 rounded-lg text-sm bg-white/10 text-white/50">
            {{ $logs->currentPage() }} / {{ $logs->lastPage() }}
        </span>

        @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm bg-white/10 text-white/70">Siguiente →</a>
        @else
            <span class="px-3 py-1.5 rounded-lg text-sm bg-white/5 text-white/30">Siguiente →</span>
        @endif
    </div>
    @endif

    {{-- Modal borrar --}}
    <div x-show="showDelete"
         class="flex items-center justify-center"
         style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background-color:rgba(0,0,0,0.6);"
         @click.self="showDelete = false">
        <div class="bg-gray-900 border border-white/20 rounded-2xl p-6 mx-4 w-full max-w-sm">
            <h3 class="text-white font-bold text-lg mb-1">Borrar logs</h3>
            <p class="text-white/60 text-sm mb-4">Esta acción eliminará todos los logs registrados.</p>

            <div class="flex items-start gap-2 bg-white/5 rounded-xl px-3 py-2.5 mb-5">
                <svg class="w-4 h-4 text-white/40 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-white/50">Los logs se eliminan automáticamente pasados 60 días.</p>
            </div>

            <form method="POST" action="{{ route('logs.destroy') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="days" value="0">

                <div class="flex gap-3">
                    <button type="button" @click="showDelete = false"
                            class="flex-1 py-2.5 rounded-xl border border-white/20 text-white/70 text-sm font-medium">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold">
                        Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
