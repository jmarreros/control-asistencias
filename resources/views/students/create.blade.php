@extends('layouts.app')

@section('content')
<div class="bg-indigo-600 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-xl font-bold text-white">Nuevo Alumno</h1>
</div>

<form method="POST" action="{{ route('students.store') }}" class="p-4 space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo *</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
               class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono / WhatsApp</label>
        <input type="tel" name="phone" value="{{ old('phone') }}"
               class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                      focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3 text-base
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                         focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
    </div>

    <div class="pt-2">
        <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-lg">
            Registrar alumno
        </button>
    </div>
</form>
@endsection
