@extends('layouts.app')

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
            <h1 class="text-xl font-bold text-white">Importar alumnos</h1>
            <p class="text-white/60 text-sm">Carga masiva desde archivo CSV</p>
        </div>
    </div>
</div>

<div class="p-4 space-y-4">

    @if(session('success'))
        <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-3 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('import_warnings'))
        <div class="bg-yellow-400/10 border border-yellow-400/30 rounded-xl px-4 py-3">
            <p class="text-yellow-300 text-sm font-semibold mb-2">Advertencias durante la importación:</p>
            <ul class="space-y-1">
                @foreach(session('import_warnings') as $w)
                    <li class="text-yellow-200/70 text-xs">• {{ $w }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Instrucciones --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Formato del archivo CSV</p>
        </div>
        <div class="px-4 py-4 space-y-3">
            <p class="text-sm text-white/70">
                El archivo debe ser <span class="text-white font-medium">.csv</span> con la primera fila como encabezado.
                Se admiten separadores <code class="text-amber-400">,</code> (coma) o <code class="text-amber-400">;</code> (punto y coma) — se detectan automáticamente.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left py-2 pr-4 text-white/50 font-semibold">Columna</th>
                            <th class="text-left py-2 pr-4 text-white/50 font-semibold">Requerida</th>
                            <th class="text-left py-2 text-white/50 font-semibold">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">name</code></td>
                            <td class="py-2 pr-4"><span class="text-red-400 font-medium">Sí</span></td>
                            <td class="py-2 text-white/60">Nombre completo del alumno</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">dni</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No</span></td>
                            <td class="py-2 text-white/60">DNI del alumno. Si existe, se usa para detectar duplicados</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">phone</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No</span></td>
                            <td class="py-2 text-white/60">Teléfono de contacto</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">start_date</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No*</span></td>
                            <td class="py-2 text-white/60">Fecha de inicio del plan. Formato: <code class="text-amber-400">DD/MM/YYYY</code> o <code class="text-amber-400">YYYY-MM-DD</code></td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">end_date</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No*</span></td>
                            <td class="py-2 text-white/60">Fecha de fin del plan. Mismos formatos que start_date</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">nombre_plan</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No*</span></td>
                            <td class="py-2 text-white/60">Tipo de plan (ver valores válidos abajo)</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">price</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No</span></td>
                            <td class="py-2 text-white/60">Precio del plan en soles. Si se omite, se registra como 0</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4"><code class="text-teal-400">clases_restantes</code></td>
                            <td class="py-2 pr-4"><span class="text-white/30">No</span></td>
                            <td class="py-2 text-white/60">Clases restantes. Si se omite, se usa el total del plan</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-white/40">* Para importar el plan, las tres columnas (start_date, end_date y nombre_plan) deben estar presentes y completas.</p>
        </div>
    </div>

    {{-- Valores válidos para nombre_plan --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Valores válidos para <code class="text-amber-400 normal-case">nombre_plan</code></p>
        </div>
        <div class="px-4 py-4">
            <div class="grid grid-cols-2 gap-2">
                @foreach(['8 horas', '12 horas', '16 horas', '24 horas', 'Full-1', 'Full-2'] as $plan)
                    <div class="bg-white/5 rounded-lg px-3 py-2 text-sm font-mono text-white/80">{{ $plan }}</div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Comportamiento --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Comportamiento de la importación</p>
        </div>
        <div class="px-4 py-4 space-y-2">
            <div class="flex gap-2 text-sm">
                <span class="text-teal-400 shrink-0">•</span>
                <span class="text-white/70">Si el alumno no existe, se crea junto con su plan.</span>
            </div>
            <div class="flex gap-2 text-sm">
                <span class="text-teal-400 shrink-0">•</span>
                <span class="text-white/70">Si el alumno ya existe (detectado por DNI o nombre), solo se crea el plan.</span>
            </div>
            <div class="flex gap-2 text-sm">
                <span class="text-yellow-400 shrink-0">•</span>
                <span class="text-white/70">Si el alumno ya tiene un plan activo, el plan de esa fila se omite.</span>
            </div>
            <div class="flex gap-2 text-sm">
                <span class="text-white/40 shrink-0">•</span>
                <span class="text-white/70">Si faltan start_date, end_date o nombre_plan, el alumno se crea pero sin plan.</span>
            </div>
        </div>
    </div>

    {{-- Ejemplo CSV --}}
    <div class="rounded-xl overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Ejemplo de archivo CSV</p>
        </div>
        <div class="px-4 py-4">
            <pre class="text-xs text-white/60 bg-white/5 rounded-lg p-3 overflow-x-auto leading-relaxed">name,dni,phone,start_date,end_date,nombre_plan,price,clases_restantes
Ana García,12345678,987654321,01/05/2025,31/05/2025,8 horas,120,8
Luis Pérez,87654321,912345678,01/05/2025,31/05/2025,12 horas,150,10
María López,,,01/05/2025,30/06/2025,Full-1,190,
Carlos Ruiz,11223344,,,,,,</pre>
        </div>
    </div>

    {{-- Formulario de carga --}}
    <form method="POST" action="{{ route('import.process') }}" enctype="multipart/form-data"
          class="rounded-xl overflow-hidden border border-white/20">
        @csrf
        <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-semibold text-white/50 uppercase tracking-wide">Cargar archivo</p>
        </div>
        <div class="px-4 py-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-white/80 mb-2">Selecciona el archivo CSV</label>
                <input type="file" name="file" accept=".csv,.txt"
                       class="w-full text-sm text-white/70 file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0 file:text-sm file:font-medium
                              file:bg-indigo-600 file:text-white hover:file:bg-indigo-500
                              cursor-pointer">
            </div>
            <button type="submit"
                    class="w-full bg-emerald-600 text-white font-bold py-4 rounded-xl text-base">
                Importar datos
            </button>
        </div>
    </form>

</div>
@endsection
