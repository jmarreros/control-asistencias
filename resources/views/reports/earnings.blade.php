@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('reports.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
        <div>
            <h1 class="text-xl font-bold text-white">Ganancias</h1>
            <p class="text-white/60 text-sm">Planes registrados en el período</p>
        </div>
    </div>

    <form method="GET" class="flex gap-2 items-end">
        <div class="flex-1">
            <label class="text-xs text-white/50 block mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-white/15 text-white border border-white/20 focus:outline-none">
        </div>
        <div class="flex-1">
            <label class="text-xs text-white/50 block mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-white/15 text-white border border-white/20 focus:outline-none">
        </div>
        <button type="submit"
                class="bg-green-500 text-white font-semibold text-sm px-4 py-2 rounded-lg whitespace-nowrap">
            Ver
        </button>
    </form>
</div>

<div class="p-4 space-y-5">

    {{-- Exportar --}}
    <div class="flex justify-end">
        <a href="{{ route('reports.earnings.export', ['from' => $from, 'to' => $to]) }}"
           class="flex items-center gap-2 bg-emerald-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar Excel
        </a>
    </div>

    {{-- Total --}}
    <div class="bg-emerald-500/15 backdrop-blur-sm rounded-xl p-4 border border-emerald-400/25 text-center">
        <p class="text-xs text-white/50 uppercase tracking-wide mb-1">Total del período</p>
        <p class="text-3xl font-bold text-emerald-400">S/ {{ number_format($total, 2) }}</p>
        <p class="text-xs text-white/40 mt-1">{{ $plans->count() }} {{ Str::plural('plan', $plans->count()) }} con precio registrado</p>
    </div>

    {{-- Desglose por promoción --}}
    @php $withPromo = $plans->whereNotNull('promotion'); @endphp
    @if($withPromo->isNotEmpty())
        <div>
            <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-2">Promociones aplicadas</h2>
            <div class="space-y-1.5">
                @foreach($withPromo->groupBy('promotion') as $key => $group)
                    @php
                        $promoColors = [
                            'promo_10'  => 'border-blue-400/40 text-blue-300',
                            'promo_20'  => 'border-violet-400/40 text-violet-300',
                            'promo_30'  => 'border-orange-400/40 text-orange-300',
                            'promo_2x1' => 'border-pink-400/40 text-pink-300',
                        ];
                        $colorClass = $promoColors[$key] ?? 'border-white/20 text-white/60';
                    @endphp
                    <div class="flex items-center justify-between rounded-xl px-4 py-2.5 border {{ $colorClass }}">
                        <span class="text-sm font-semibold">{{ $group->first()->promotionLabel() }}</span>
                        <span class="text-xs opacity-70">{{ $group->count() }} {{ Str::plural('plan', $group->count()) }} · S/ {{ number_format($group->sum('price'), 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Desglose por tipo de plan --}}
    @if($byQuota->isNotEmpty())
        <div>
            <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-2">Por tipo de plan</h2>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['8', '12', 'full1', '16', '24', 'full2'] as $quota)
                    @if(isset($byQuota[$quota]))
                        @php $q = $byQuota[$quota]; @endphp
                        <div class="rounded-xl px-4 py-3 border border-white/20">
                            <p class="text-xs text-white/50">{{ ['full1' => 'Full-1', 'full2' => 'Full-2'][$quota] ?? ($quota . ' clases') }}</p>
                            <p class="text-lg font-bold text-white">S/ {{ number_format($q['total'], 2) }}</p>
                            <p class="text-xs text-white/40">{{ $q['count'] }} {{ Str::plural('alumno', $q['count']) }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Detalle por alumno --}}
    <div>
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-2">Detalle por alumno</h2>
        @if($plans->isNotEmpty())
            <div class="space-y-2">
                @foreach($plans as $plan)
                    <div class="rounded-xl px-4 py-3 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-white">{{ $plan->student->name }}</p>
                                <p class="text-xs text-white/40">
                                    {{ ['full1' => 'Full-1', 'full2' => 'Full-2'][$plan->class_quota] ?? ($plan->class_quota . ' clases') }}
                                    · {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM YY') }}
                                </p>
                                <p class="text-xs text-white/30">
                                    Registrado: {{ $plan->created_at->locale('es')->isoFormat('D MMM YYYY') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-bold text-emerald-400">S/ {{ number_format($plan->price, 2) }}</p>
                                @if($plan->promotion)
                                    @php
                                        $promoColors = [
                                            'promo_10'  => 'bg-blue-500/20 text-blue-300',
                                            'promo_20'  => 'bg-violet-500/20 text-violet-300',
                                            'promo_30'  => 'bg-orange-500/20 text-orange-300',
                                            'promo_2x1' => 'bg-pink-500/20 text-pink-300',
                                        ];
                                    @endphp
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full mt-1 inline-block {{ $promoColors[$plan->promotion] ?? 'bg-white/10 text-white/50' }}">
                                        {{ $plan->promotionLabel() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-white/40">
                <p class="text-sm">No hay planes registrados en este período.</p>
            </div>
        @endif
    </div>

</div>
@endsection
