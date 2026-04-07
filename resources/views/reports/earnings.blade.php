@extends('layouts.app')

@section('content')
<div class="bg-emerald-600 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('reports.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-white">Ganancias</h1>
            <p class="text-emerald-200 text-sm">Planes registrados en el período</p>
        </div>
    </div>

    <form method="GET" class="flex gap-2 items-end">
        <div class="flex-1">
            <label class="text-xs text-emerald-200 block mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-emerald-700 text-white border border-emerald-500 focus:outline-none">
        </div>
        <div class="flex-1">
            <label class="text-xs text-emerald-200 block mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full text-sm rounded-lg px-3 py-2 bg-emerald-700 text-white border border-emerald-500 focus:outline-none">
        </div>
        <button type="submit"
                class="bg-white text-emerald-700 font-semibold text-sm px-4 py-2 rounded-lg whitespace-nowrap">
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
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total del período</p>
        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($total, 2) }}</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $plans->count() }} {{ Str::plural('plan', $plans->count()) }} con precio registrado</p>
    </div>

    {{-- Desglose por tipo de plan --}}
    @if($byQuota->isNotEmpty())
        <div>
            <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Por tipo de plan</h2>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['8', '12', '16', 'full'] as $quota)
                    @if(isset($byQuota[$quota]))
                        @php $q = $byQuota[$quota]; @endphp
                        <div class="bg-white dark:bg-gray-800 rounded-xl px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $quota === 'full' ? 'Full' : $quota . ' clases' }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">S/ {{ number_format($q['total'], 2) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $q['count'] }} {{ Str::plural('alumno', $q['count']) }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Por curso --}}
    @if($byClase->isNotEmpty())
        <div>
            <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Por curso</h2>
            <div class="space-y-3">
                @foreach($byClase as $row)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $row['clase']->name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $row['students']->count() }} {{ Str::plural('alumno', $row['students']->count()) }}</p>
                            </div>
                            <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($row['total'], 2) }}</p>
                        </div>
                        <div class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($row['students'] as $entry)
                                <div class="flex items-center justify-between px-4 py-2.5">
                                    <div>
                                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $entry['student']->name }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $entry['plan']->class_quota === 'full' ? 'Full' : $entry['plan']->class_quota . ' clases' }}
                                            · {{ \Carbon\Carbon::parse($entry['plan']->start_date)->locale('es')->isoFormat('D MMM YY') }}
                                        </p>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">S/ {{ number_format($entry['plan']->price, 2) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Lista de planes --}}
    @if($plans->isNotEmpty())
        <div>
            <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Todos los planes</h2>
            <div class="space-y-2">
                @foreach($plans as $plan)
                    <div class="bg-white dark:bg-gray-800 rounded-xl px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $plan->student->name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $plan->class_quota === 'full' ? 'Full' : $plan->class_quota . ' clases' }}
                                · {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM YY') }}
                                → {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YY') }}
                            </p>
                        </div>
                        <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">S/ {{ number_format($plan->price, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-10 text-gray-400 dark:text-gray-500">
            <p class="text-sm">No hay planes con precio registrado en este período.</p>
        </div>
    @endif

</div>
@endsection
