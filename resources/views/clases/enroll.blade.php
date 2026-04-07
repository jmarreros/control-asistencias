@extends('layouts.app')

@section('content')
<div class="bg-purple-600 px-4 pt-6 pb-4">
    <div class="flex items-center gap-3 mb-3">
        <a href="{{ route('clases.index') }}" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-white">Matrícula</h1>
            <p class="text-purple-200 text-sm">{{ $clase->name }}</p>
        </div>
    </div>
    <div class="relative">
        <svg class="w-4 h-4 text-purple-300 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
        </svg>
        <input type="search" id="search-enroll" placeholder="Buscar alumno..."
               autocomplete="off" oninput="filterStudents(this.value)"
               class="w-full pl-9 pr-4 py-2.5 rounded-xl text-sm bg-purple-700 text-white placeholder-purple-300
                      focus:outline-none focus:bg-white focus:text-gray-900 focus:placeholder-gray-400 transition-colors">
    </div>
</div>

<form method="POST" action="{{ route('clases.enroll.update', $clase) }}" class="pb-24">
    @csrf

    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-100 dark:border-gray-600 flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            <span id="count">{{ count($enrolledIds) }}</span> seleccionado(s)
        </p>
        <div class="flex gap-2">
            <button type="button" onclick="toggleAll(true)"
                    class="text-xs text-indigo-600 dark:text-indigo-400 font-medium px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                Todos
            </button>
            <button type="button" onclick="toggleAll(false)"
                    class="text-xs text-gray-500 dark:text-gray-400 font-medium px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-lg">
                Ninguno
            </button>
        </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-700" id="student-list">
        @forelse($allStudents as $student)
            <label data-name="{{ strtolower($student->name) }} {{ strtolower($student->phone ?? '') }}"
                   class="student-row flex items-center px-4 py-4 bg-white dark:bg-gray-800 cursor-pointer active:bg-gray-50 dark:active:bg-gray-700">
                <input type="checkbox"
                       name="student_ids[]"
                       value="{{ $student->id }}"
                       class="student-check w-5 h-5 rounded text-purple-600 mr-4 cursor-pointer"
                       {{ in_array($student->id, $enrolledIds) ? 'checked' : '' }}
                       onchange="updateCount()">
                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $student->name }}</p>
                    @if($student->phone)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $student->phone }}</p>
                    @endif
                </div>
            </label>
        @empty
            <div class="text-center py-12 text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800">
                <p class="text-sm">No hay alumnos registrados.</p>
                <a href="{{ route('students.create') }}" class="text-indigo-600 dark:text-indigo-400 text-sm font-medium mt-1 inline-block">
                    Registrar alumno →
                </a>
            </div>
        @endforelse
    </div>

    <div class="fixed bottom-16 left-0 right-0 p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg max-w-lg mx-auto">
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
