@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
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
                        {{ ['full1' => 'Full-1 (ilimitado)', 'full2' => 'Full-2 (ilimitado)'][$currentPlan->class_quota] ?? ($currentPlan->class_quota . ' clases') }}
                    </p>
                </div>
                <div>
                    <p class="text-white/50 text-xs">Monto</p>
                    <p class="font-medium text-white">
                        {{ $currentPlan->price !== null ? 'S/ ' . number_format($currentPlan->price, 2) : '—' }}
                    </p>
                </div>
                @if(!in_array($currentPlan->class_quota, ['full1', 'full2']))
                    <div>
                        <p class="text-white/50 text-xs">Restantes</p>
                        <p class="font-bold text-white">
                            {{ $currentPlan->classesRemaining() }} / {{ $currentPlan->class_quota }}
                            <span class="text-xs font-normal text-white/50">usadas: {{ $currentPlan->classesUsed() }}</span>
                        </p>
                    </div>
                @endif
            </div>

            @if($currentPlan->promotion)
                <div class="mt-3 pt-3 border-t border-white/10 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-white/40 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z"/>
                    </svg>
                    <span class="text-xs text-white/50">Promoción:</span>
                    <span class="text-xs font-semibold text-emerald-300">{{ $currentPlan->promotionLabel() }}</span>
                </div>
            @endif

            @if(in_array($currentPlan->status(), ['ok', 'pending']))
                <div class="mt-3 pt-3 border-t border-white/10 flex justify-end">
                    <form method="POST"
                          action="{{ route('students.plans.destroy', [$student, $currentPlan]) }}"
                          onsubmit="return confirm('¿Cancelar el plan actual? Quedará registrado en el historial.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-400/70 hover:text-red-400 transition-colors">
                            Cancelar plan
                        </button>
                    </form>
                </div>
            @endif
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
                  quota: '{{ old('class_quota', '8') }}',
                  prices: {{ json_encode($prices) }},
                  promos: {{ json_encode($promos->map(fn($p, $k) => $p + ['key' => $k])->values()) }},
                  promoColors: {
                      'promo_10':  'border-blue-400 bg-blue-500/25 text-blue-300',
                      'promo_20':  'border-violet-400 bg-violet-500/25 text-violet-300',
                      'promo_30':  'border-orange-400 bg-orange-500/25 text-orange-300',
                      'promo_2x1': 'border-pink-400 bg-pink-500/25 text-pink-300',
                  },
                  discount: 0,
                  promoKey: '',
                  price: '{{ old('price', $prices['8']) }}',
                  get dateError() {
                      return this.startDate && this.endDate && this.endDate < this.startDate;
                  },
                  calcEndDate() {
                      if (!this.startDate) return;
                      var days = ['16', '24', 'full2'].includes(this.quota) ? 40 : 20;
                      var d = new Date(this.startDate + 'T00:00:00');
                      var count = 0;
                      while (true) {
                          var dow = d.getDay();
                          if (dow !== 0 && dow !== 6) count++;
                          if (count === days) break;
                          d.setDate(d.getDate() + 1);
                      }
                      this.endDate = d.toISOString().slice(0, 10);
                  },
                  updatePrice() {
                      var base = this.prices[this.quota];
                      this.price = this.discount > 0
                          ? Math.round(base * (1 - this.discount / 100) * 100) / 100
                          : base;
                  },
                  selectDiscount(promo) {
                      if (this.discount === promo.discount) {
                          this.discount = 0;
                          this.promoKey = '';
                      } else {
                          this.discount = promo.discount;
                          this.promoKey = promo.key;
                      }
                      this.updatePrice();
                  }
              }"
              @submit.prevent="if (!dateError) $el.submit()">
            @csrf

            <div>
                <label class="block text-xs font-medium text-white/70 mb-1">Cantidad de clases *</label>
                <div class="flex gap-2 w-full">
                    @foreach(['8', '12', 'full1', '16', '24', 'full2'] as $quota)
                        <label class="cursor-pointer flex-1" @click="quota = '{{ $quota }}'; updatePrice(); calcEndDate()">
                            <input type="radio" name="class_quota" value="{{ $quota }}"
                                   x-model="quota" class="sr-only">
                            <div class="text-center px-4 py-2 rounded-xl border-2 text-sm font-semibold transition-colors cursor-pointer whitespace-nowrap"
                                 :class="quota === '{{ $quota }}'
                                     ? 'border-indigo-400 bg-indigo-500/30 text-indigo-300'
                                     : 'border-white/20 bg-white/10 text-white/60'">
                                {{ ['full1' => 'Full-1', 'full2' => 'Full-2'][$quota] ?? $quota }}
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('class_quota')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="block text-xs font-medium text-white/70 mb-1">Fecha inicio *</label>
                        <input type="date" name="start_date" required
                               x-model="startDate" @change="calcEndDate()"
                               :class="dateError ? 'border-red-400' : 'border-white/50'"
                               class="w-full rounded-xl px-3 py-2.5 text-sm border
                                      bg-white/10 text-white
                                      focus:outline-none focus:border-indigo-400 focus:bg-white/15">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/70 mb-1">Fecha fin *</label>
                        <input type="date" name="end_date" required
                               x-model="endDate"
                               :class="dateError ? 'border-red-400' : 'border-white/50'"
                               class="w-full rounded-xl px-3 py-2.5 text-sm border
                                      bg-white/10 text-white
                                      focus:outline-none focus:border-indigo-400 focus:bg-white/15">
                    </div>
                </div>
                <p x-show="dateError" class="text-red-400 text-xs mt-1">
                    La fecha fin debe ser igual o posterior a la fecha inicio.
                </p>

                {{-- Promociones activas --}}
                <template x-if="promos.length > 0">
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-white/70 mb-2">Promoción</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="promo in promos" :key="promo.discount">
                                <button type="button"
                                        @click="selectDiscount(promo)"
                                        :class="discount === promo.discount
                                            ? (promoColors[promo.key] || 'border-emerald-400 bg-emerald-500/25 text-emerald-300')
                                            : 'border-white/20 bg-white/10 text-white/60'"
                                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition-colors">
                                    <span x-text="promo.label"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="discount > 0" class="text-xs text-emerald-400 mt-1.5">
                            Descuento aplicado: <span x-text="discount + '%'"></span>
                        </p>
                    </div>
                </template>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-white/70 mb-1">Monto (S/)</label>
                    <div class="relative">
                        <input type="number" name="price" step="0.01" min="0"
                               x-model="price"
                               class="w-full rounded-xl px-3 py-2.5 text-sm border border-white/50
                                      bg-white/10 text-white
                                      focus:outline-none focus:border-indigo-400 focus:bg-white/15">
                        <template x-if="discount > 0">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                <span class="text-xs text-white/40 line-through"
                                      x-text="'S/ ' + prices[quota]"></span>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-white/40 mt-1">El precio se ajusta automáticamente al seleccionar una promoción. Puedes modificarlo manualmente.</p>
                    @error('price')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <input type="hidden" name="promotion" :value="promoKey">
            </div>

            <button type="submit"
                    :disabled="dateError"
                    :class="dateError ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl text-sm">
                Guardar plan
            </button>
        </form>
    </div>
</div>

{{-- Historial de planes --}}
@php $historial = $plans->skip($currentPlan ? 1 : 0); @endphp
@if($historial->count() > 0)
    <div class="border-t border-white/10 px-4 pt-4 pb-6">
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Historial</h2>
        <div class="space-y-2">
            @foreach($historial as $plan)
                <div @class([
                    'backdrop-blur-sm border rounded-xl px-4 py-3',
                    'bg-white/5 border-white/10 opacity-60' => $plan->trashed(),
                    'bg-white/10 border-white/15'           => !$plan->trashed(),
                ])>
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-sm">
                            <p class="text-white font-medium">
                                {{ ['full1' => 'Full-1', 'full2' => 'Full-2'][$plan->class_quota] ?? ($plan->class_quota . ' clases') }}
                                @if($plan->price !== null)
                                    <span class="text-white/40 font-normal">· S/ {{ number_format($plan->price, 2) }}</span>
                                @endif
                            </p>
                            <p class="text-white/50 text-xs">
                                {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM YY') }}
                                →
                                {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YY') }}
                            </p>
                            @if(!in_array($plan->class_quota, ['full1', 'full2']))
                                <p class="text-white/40 text-xs">
                                    {{ $plan->classesUsed() }} clases tomadas
                                </p>
                            @endif
                            @if($plan->promotion)
                                <p class="text-xs text-emerald-400/80 mt-0.5">
                                    🏷 {{ $plan->promotionLabel() }}
                                </p>
                            @endif
                        </div>
                        @if($plan->trashed())
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 border border-red-500/20 shrink-0">
                                Cancelado
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
