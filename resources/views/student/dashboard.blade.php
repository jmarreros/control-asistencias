@extends('layouts.student')

@section('content')

{{-- Header --}}
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-9 h-9 object-contain rounded-full shrink-0" alt="Logo"></a>
            <div>
                <h1 class="text-lg font-bold text-white leading-tight">{{ $student->name }}</h1>
                <p class="text-white/50 text-xs">Mis asistencias</p>
            </div>
        </div>
        <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button type="submit" class="text-white/40 p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>

    @if($plan)
        @php
            $used      = $plan->classesUsed();
            $remaining = $plan->classesRemaining();
            $isFull    = $plan->class_quota === 'full';
            $cardBg    = match($planStatus) {
                'ok'        => 'bg-emerald-500/20 border-emerald-400/40',
                'pending'   => 'bg-blue-500/15 border-blue-400/30',
                'exhausted' => 'bg-orange-500/15 border-orange-400/30',
                default     => 'bg-white/10 border-white/15',
            };
            $labelColor = match($planStatus) {
                'ok'        => 'text-emerald-300',
                'pending'   => 'text-blue-300',
                'exhausted' => 'text-orange-300',
                default     => 'text-white/50',
            };
            $statusText = match($planStatus) {
                'ok'        => 'Activo',
                'pending'   => 'Por iniciar',
                'exhausted' => 'Clases agotadas',
                default     => 'Vencido',
            };
        @endphp
        <div class="mt-3 rounded-xl border px-4 py-3 backdrop-blur-sm {{ $cardBg }}">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-bold {{ $labelColor }}">
                    Plan {{ $isFull ? 'Full (ilimitado)' : $plan->class_quota . ' clases' }}
                </span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $cardBg }} {{ $labelColor }} border {{ str_replace('bg-', 'border-', explode(' ', $cardBg)[1] ?? '') }}">
                    {{ $statusText }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-xs text-white/50">
                    {{ \Carbon\Carbon::parse($plan->start_date)->locale('es')->isoFormat('D MMM') }}
                    –
                    {{ \Carbon\Carbon::parse($plan->end_date)->locale('es')->isoFormat('D MMM YYYY') }}
                </p>
                @if(!$isFull)
                    <div class="flex items-center gap-3 text-xs">
                        <span class="{{ $labelColor }} font-semibold">{{ $used }} usadas</span>
                        <span class="text-white/30">·</span>
                        <span class="text-white/60">{{ $remaining }} restantes</span>
                    </div>
                @else
                    <span class="text-xs {{ $labelColor }} font-semibold">{{ $used }} clases asistidas</span>
                @endif
            </div>
            @if(!$isFull && $remaining !== null)
                @php $pct = $plan->class_quota > 0 ? round($used / $plan->class_quota * 100) : 0; @endphp
                <div class="mt-2 h-1.5 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $planStatus === 'ok' ? 'bg-emerald-400' : 'bg-white/30' }}"
                         style="width: {{ min($pct, 100) }}%"></div>
                </div>
            @endif
        </div>
    @endif
</div>

<div class="p-4">

    @if($planStatus === 'expired')
        <div class="mb-4 bg-amber-500/15 border border-amber-400/30 rounded-xl px-4 py-4 backdrop-blur-sm">
            <div class="flex gap-3 items-start">
                <span class="text-2xl leading-none mt-0.5">🎵</span>
                <div>
                    <p class="font-semibold text-amber-300 text-sm">¡Tu plan ha vencido!</p>
                    <p class="text-amber-200/80 text-sm mt-1 leading-relaxed">
                        No te pierdas la oportunidad de seguir practicando y mejorando tu ritmo.
                        Renueva tu plan y sigue disfrutando de la danza. 💃🕺
                        <br>
                        <span class="font-medium">Ponte en contacto con la academia para renovarlo.</span>
                    </p>
                </div>
            </div>
        </div>
    @elseif($planStatus === 'no_plan')
        <div class="mb-4 bg-indigo-500/15 border border-indigo-400/30 rounded-xl px-4 py-4 backdrop-blur-sm">
            <div class="flex gap-3 items-start">
                <span class="text-2xl leading-none mt-0.5">🎶</span>
                <div>
                    <p class="font-semibold text-indigo-300 text-sm">¡Aún no tienes un plan activo!</p>
                    <p class="text-indigo-200/80 text-sm mt-1 leading-relaxed">
                        Habla con nosotros y elige el plan que mejor se adapte a ti.
                        <span class="font-medium">¡Te esperamos en la pista!</span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($stats->isEmpty())
        <div class="text-center py-16 text-white/40">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <p class="text-sm">No estás inscrito en ningún curso.</p>
        </div>
    @else
        <h2 class="text-xs font-semibold text-white/50 uppercase tracking-wide mb-3">Mis cursos</h2>
        <div class="space-y-3">
            @foreach($stats as $row)
                @php
                    $rate = $row['rate'];
                    if ($rate === null) {
                        $badgeClass = 'bg-white/10 text-white/40';
                        $badgeText  = 'Sin clases';
                    } elseif ($rate >= 80) {
                        $badgeClass = 'bg-green-500/20 text-green-300';
                        $badgeText  = $rate . '%';
                    } elseif ($rate >= 60) {
                        $badgeClass = 'bg-yellow-500/20 text-yellow-300';
                        $badgeText  = $rate . '%';
                    } else {
                        $badgeClass = 'bg-red-500/20 text-red-300';
                        $badgeText  = $rate . '%';
                    }
                @endphp
                <a href="{{ route('student.clase', $row['clase']->id) }}"
                   class="block bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl p-4 active:bg-white/20">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-white">{{ $row['clase']->name }}</p>
                            @if($row['clase']->schedule)
                                <p class="text-xs text-white/50 mt-0.5">{!! $row['clase']->scheduleText() !!}</p>
                            @endif
                            <p class="text-xs text-white/40 mt-1">
                                {{ $row['present'] }} presentes de {{ $row['total'] }} clases
                            </p>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <span class="text-sm font-bold px-3 py-1 rounded-lg {{ $badgeClass }}">
                                {{ $badgeText }}
                            </span>
                            <svg class="w-4 h-4 text-white/30 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
@endsection
