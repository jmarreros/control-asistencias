@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo">
    <div class="flex-1">
        <h1 class="text-xl font-bold text-white">Plan de clases</h1>
        <p class="text-white/60 text-sm">{{ $student->name }}</p>
    </div>
</div>

<div class="px-4 pt-4 pb-2">

    <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Plan actual</h2>

    @if($currentPlan)
        @php $status = $currentPlan->status(); @endphp
        <div @class([
            'rounded-xl p-4 mb-4 border backdrop-blur-sm',
            'bg-green-500/15 border-green-400/30'   => $status === 'ok',
            'bg-orange-500/15 border-orange-400/30' => $status === 'exhausted',
            'bg-red-500/15 border-red-400/30'       => $status === 'expired',
            'bg-white/10 border-white/15'           => $status === 'pending',
        ])>
            <div class="mb-2">
                <span @class([
                    'text-xs font-semibold px-2 py-1 rounded-full',
                    'bg-green-500/20 text-green-300'   => $status === 'ok',
                    'bg-orange-500/20 text-orange-300' => $status === 'exhausted',
                    'bg-red-500/20 text-red-300'       => $status === 'expired',
                    'bg-white/15 text-white/70'        => $status === 'pending',
                ])>
                    {{ match($status) {
                        'ok'        => 'Activo',
                        'exhausted' => 'Clases agotadas',
                        'expired'   => 'Vencido',
                        'pending'   => 'Por iniciar',
                        default     => ''
                    } }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-white/50 text-xs">Inicio</p>
                    <p class="font-medium text-white">
                        {{ \Carbon\Carbon::parse($currentPlan->start_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    </p>
                </div>
                <div>
                    <p class="text-white/50 text-xs">Fin</p>
                    <p class="font-medium text-white">
                        {{ \Carbon\Carbon::parse($currentPlan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
                    </p>
                </div>
                <div>
                    <p class="text-white/50 text-xs">Cuota</p>
                    <p class="font-medium text-white">
                        {{ $currentPlan->class_quota === 'full' ? 'Full (ilimitado)' : $currentPlan->class_quota . ' clases' }}
                    </p>
                </div>
                <div>
                    <p class="text-white/50 text-xs">Monto</p>
                    <p class="font-medium text-white">
                        {{ $currentPlan->price !== null ? 'S/ ' . number_format($currentPlan->price, 2) : '—' }}
                    </p>
                </div>
                @if($currentPlan->class_quota !== 'full')
                    <div>
                        <p class="text-white/50 text-xs">Restantes</p>
                        <p class="font-bold text-white">
                            {{ $currentPlan->classesRemaining() }} / {{ $currentPlan->class_quota }}
                            <span class="text-xs font-normal text-white/50">usadas: {{ $currentPlan->classesUsed() }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <p class="text-sm text-white/40 mb-4">Sin plan registrado.</p>
    @endif

    {{-- Nuevo / Renovar plan --}}
    <div x-data="{ open: {{ $currentPlan ? 'false' : 'true' }} }">
        <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between text-sm font-semibold text-indigo-400 py-2">
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
                    <label class="block text-xs font-medium text-white/70 mb-1">Fecha inicio *</label>
                    <input type="date" name="start_date" required
                           x-model="startDate"
                           :class="dateError ? 'border-red-400' : 'border-white/20'"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border
                                  bg-white/10 backdrop-blur-sm text-white
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/70 mb-1">Fecha fin *</label>
                    <input type="date" name="end_date" required
                           x-model="endDate"
                           :class="dateError ? 'border-red-400' : 'border-white/20'"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border
                                  bg-white/10 backdrop-blur-sm text-white
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
            </div>
            <p x-show="dateError" class="text-red-400 text-xs -mt-1">
                La fecha fin debe ser igual o posterior a la fecha inicio.
            </p>

            <div x-data="{
                    quota: '{{ old('class_quota', '8') }}',
                    prices: {{ json_encode($prices) }},
                    price: '{{ old('price', $prices['8']) }}',
                    updatePrice() { this.price = this.prices[this.quota]; }
                }">
                <label class="block text-xs font-medium text-white/70 mb-1">Cantidad de clases *</label>
                <div class="flex gap-2 w-full">
                    @foreach(['8', '12', '16', 'full'] as $quota)
                        <label class="cursor-pointer flex-1" @click="quota = '{{ $quota }}'; updatePrice()">
                            <input type="radio" name="class_quota" value="{{ $quota }}"
                                   x-model="quota" class="sr-only">
                            <div class="text-center px-4 py-2 rounded-xl border-2 text-sm font-semibold transition-colors cursor-pointer whitespace-nowrap"
                                 :class="quota === '{{ $quota }}'
                                     ? 'border-indigo-400 bg-indigo-500/30 text-indigo-300'
                                     : 'border-white/20 bg-white/10 text-white/60'">
                                {{ $quota === 'full' ? 'Full' : $quota }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('class_quota')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror

                <div class="mt-3">
                    <label class="block text-xs font-medium text-white/70 mb-1">Monto (S/)</label>
                    <input type="number" name="price" step="0.01" min="0"
                           x-model="price"
                           class="w-full rounded-xl px-3 py-2.5 text-sm border border-white/20
                                  bg-white/10 backdrop-blur-sm text-white
                                  focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <p class="text-xs text-white/40 mt-1">Precio por defecto según configuración. Puedes modificarlo para descuentos o promociones.</p>
                    @error('price')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
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
    <div class="border-t border-white/10 px-4 pt-4 pb-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Historial</h2>
        <div class="space-y-2">
            @foreach($plans->skip(1) as $plan)
                <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl px-4 py-3">
                    <div class="text-sm">
                        <p class="text-white font-medium">
                            {{ $plan->class_quota === 'full' ? 'Full' : $plan->class_quota . ' clases' }}
                            @if($plan->price !== null)
                                <span class="text-white/40 font-normal">· S/ {{ number_format($plan->price, 2) }}</span>
                            @endif
                        </p>
                        <p class="text-white/50 text-xs">
                            {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM YY') }}
                            →
                            {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YY') }}
                        </p>
                        @if($plan->class_quota !== 'full')
                            <p class="text-white/40 text-xs">
                                {{ $plan->classesUsed() }} clases tomadas
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
