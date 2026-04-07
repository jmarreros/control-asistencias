@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('clases.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo">
        <div>
            <h1 class="text-xl font-bold text-white">Matrícula</h1>
            <p class="text-white/60 text-sm">{{ $clase->name }}</p>
        </div>
    </div>
    <div class="relative">
        <svg class="w-4 h-4 text-white/40 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
        </svg>
        <input type="search" id="search-enroll" placeholder="Buscar alumno..."
               autocomplete="off" oninput="filterStudents(this.value)"
               class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm bg-white/15 border border-white/20 text-white placeholder-white/40
                      focus:outline-none focus:ring-2 focus:ring-white/30">
    </div>
</div>

<form method="POST" action="{{ route('clases.enroll.update', $clase) }}" class="pb-24">
    @csrf

    <div class="px-4 py-3 bg-white/5 border-b border-white/10 flex items-center justify-between">
        <p class="text-sm text-white/70">
            <span id="count">{{ count($enrolledIds) }}</span> seleccionado(s)
        </p>
        <div class="flex gap-2">
            <button type="button" onclick="toggleAll(true)"
                    class="text-xs text-indigo-300 font-medium px-3 py-1.5 bg-indigo-500/20 border border-indigo-400/20 rounded-lg">
                Todos
            </button>
            <button type="button" onclick="toggleAll(false)"
                    class="text-xs text-white/60 font-medium px-3 py-1.5 bg-white/10 border border-white/15 rounded-lg">
                Ninguno
            </button>
        </div>
    </div>

    <div class="divide-y divide-white/10" id="student-list">
        @forelse($allStudents as $student)
            <label data-name="{{ strtolower($student->name) }} {{ strtolower($student->phone ?? '') }}"
                   class="student-row flex items-center px-4 py-4 cursor-pointer active:bg-white/10">
                <input type="checkbox"
                       name="student_ids[]"
                       value="{{ $student->id }}"
                       class="student-check w-5 h-5 rounded text-purple-600 mr-4 cursor-pointer"
                       {{ in_array($student->id, $enrolledIds) ? 'checked' : '' }}
                       onchange="updateCount()">
                <div class="flex-1">
                    <p class="font-medium text-white">{{ $student->name }}</p>
                    @if($student->phone)
                        <p class="text-sm text-white/50">{{ $student->phone }}</p>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-12 text-white/40">
                <p class="text-sm">No hay alumnos registrados.</p>
                <a href="{{ route('students.create') }}" class="text-indigo-400 text-sm font-medium mt-1 inline-block">
                    Registrar alumno →
                </a>
            </div>
        @endforelse
    </div>

    <div style="position:fixed; bottom:4rem; left:0; right:0; max-width:32rem; margin:0 auto; padding:1rem; backdrop-filter:blur(12px); background:rgba(0,0,0,0.4); border-top:1px solid rgba(255,255,255,0.1);">
        <button type="submit"
                class="w-full bg-purple-600 text-white font-bold py-4 rounded-xl text-lg">
            Guardar matrícula
        </button>
    </div>
</form>

<script>
function updateCount() {
    const count = document.querySelectorAll('.student-check:checked').length;
    document.getElementById('count').textContent = count;
}
function toggleAll(checked) {
    document.querySelectorAll('.student-row:not([style*="display: none"]) .student-check').forEach(c => c.checked = checked);
    updateCount();
}
function filterStudents(q) {
    const query = q.toLowerCase().trim();
    document.querySelectorAll('.student-row').forEach(row => {
        row.style.display = (!query || row.dataset.name.includes(query)) ? '' : 'none';
    });
}
</script>
@endsection
