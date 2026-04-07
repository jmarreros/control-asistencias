@extends('layouts.app')

@section('content')
<div class="bg-indigo-600 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h1 class="text-xl font-bold text-white">Plan de clases</h1>
        <p class="text-indigo-200 text-sm">{{ $student->name }}</p>
    </div>
</div>

<div class="px-4 pt-4 pb-2">

    {{-- Plan actual --}}
    <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Plan actual</h2>

    @if($currentPlan)
        @php $status = $currentPlan->status(); @endphp
        <div @class([
            'rounded-xl p-4 mb-4 border',
            'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' => $status === 'ok',
            'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800' => $status === 'exhausted',
            'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' => $status === 'expired',
            'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600' => $status === 'pending',
        ])>
            <div class="flex items-center justify-between mb-2">
                <span @class([
                    'text-xs font-semibold px-2 py-1 rounded-full',
                    'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' => $status === 'ok',
                    'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400' => $status === 'exhausted',
                    'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400' => $status === 'expired',
                    'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' => $status === 'pending',
                ])>
                    {{ match($status) {
                        'ok'        => 'Activo',
                        'exhausted' => 'Clases agotadas',
                        'expired'   => 'Vencido',
                        'pending'   => 'Por iniciar',
                        default     => ''
                    } }}
                </span>
                <form method="POST" action="{{ route('students.plans.destroy', [$student, $currentPlan]) }}"
                      onsubmit="return confirm('¿Eliminar este plan?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-400 dark:text-gray-500 text-xs">Eliminar</button>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Inicio</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($currentPlan->start_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Fin</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($currentPlan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Cuota</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $currentPlan->class_quota === 'full' ? 'Full (ilimitado)' : $currentPlan->class_quota . ' clases' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Monto</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $currentPlan->price !== null ? 'S/ ' . number_format($currentPlan->price, 2) : '—' }}
                    </p>
                </div>
                @if($currentPlan->class_quota !== 'full')
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-xs">Restantes</p>
                        <p class="font-bold text-gray-900 dark:text-white">
                            {{ $currentPlan->classesRemaining() }} / {{ $currentPlan->class_quota }}
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">usadas: {{ $currentPlan->classesUsed() }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Sin plan registrado.</p>
    @endif

    {{-- Nuevo / Renovar plan --}}
    <div x-data="{ open: {{ $currentPlan ? 'false' : 'true' }} }">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between text-sm font-semibold text-indigo-600 dark:text-indigo-400 py-2">
            <span x-text="open ? 'Cancelar' : '+ {{ $currentPlan ? 'Renovar plan' : 'Agregar plan' }}'"></span>
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <form x-show="open" x-transition
              method="POST" action="{{ route('students.plans.store', $student) }}"
              class="space-y-3 pt-2"
              x-data="{
                  startDate: '{{ old('start_date', $defaultStartDate) }}',
                  endDate: '{{ old('end_date', $defaultEndDate) }}',
                  get dateError() {
                      return this.startDate && this.endDate && this.endDate < this.startDate;
                  }
              }"
              @submit.prevent="if (!dateError) $el.submit()">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha inicio *</label>
                    <input type="date" name="start_date" required
                           x-model="startDate"
                           :class="dateError ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500'"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:outline-none focus:ring-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha fin *</label>
                    <input type="date" name="end_date" required
                           x-model="endDate"
                           :class="dateError ? 'border-red-400 focus:ring-red-400' : 'border-gray-300 dark:border-gray-600 focus:ring-indigo-500'"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:outline-none focus:ring-2">
                </div>
            </div>
            <p x-show="dateError" class="text-red-500 text-xs -mt-1">
                La fecha fin debe ser igual o posterior a la fecha inicio.
            </p>

            <div x-data="{
                    quota: '{{ old('class_quota', '8') }}',
                    prices: {{ json_encode($prices) }},
                    price: '{{ old('price', $prices['8']) }}',
                    updatePrice() { this.price = this.prices[this.quota]; }
                }">
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Cantidad de clases *</label>
                <div class="flex gap-2 w-full">
                    @foreach(['8', '12', '16', 'full'] as $quota)
                        <label class="cursor-pointer flex-1" @click="quota = '{{ $quota }}'; updatePrice()">
                            <input type="radio" name="class_quota" value="{{ $quota }}"
                                   x-model="quota" class="sr-only">
                            <div class="text-center px-4 py-2 rounded-xl border-2 text-sm font-semibold transition-colors cursor-pointer whitespace-nowrap"
                                 :class="quota === '{{ $quota }}'
                                     ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                     : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400'">
                                {{ $quota === 'full' ? 'Full' : $quota }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('class_quota')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror

                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Monto (S/)</label>
                    <input type="number" name="price" step="0.01" min="0"
                           x-model="price"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                  focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Precio por defecto según configuración. Puedes modificarlo para descuentos o promociones.</p>
                    @error('price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit"
                    :disabled="dateError"
                    :class="dateError ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl text-sm">
                Guardar plan
            </button>
        </form>
    </div>
</div>

{{-- Historial de planes --}}
@if($plans->count() > 1)
    <div class="border-t border-gray-100 dark:border-gray-700 px-4 pt-4 pb-6">
        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Historial</h2>
        <div class="space-y-2">
            @foreach($plans->skip(1) as $plan)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl px-4 py-3 flex items-center justify-between">
                    <div class="text-sm">
                        <p class="text-gray-900 dark:text-white font-medium">
                            {{ $plan->class_quota === 'full' ? 'Full' : $plan->class_quota . ' clases' }}
                            @if($plan->price !== null)
                                <span class="text-gray-400 dark:text-gray-500 font-normal">· S/ {{ number_format($plan->price, 2) }}</span>
                            @endif
                        </p>
                        <p class="text-gray-500 dark:text-gray-400 text-xs">
                            {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM YY') }}
                            →
                            {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YY') }}
                        </p>
                        @if($plan->class_quota !== 'full')
                            <p class="text-gray-400 dark:text-gray-500 text-xs">
                                {{ $plan->classesUsed() }} clases tomadas
                            </p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('students.plans.destroy', [$student, $plan]) }}"
                          onsubmit="return confirm('¿Eliminar este plan del historial?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 dark:text-gray-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
