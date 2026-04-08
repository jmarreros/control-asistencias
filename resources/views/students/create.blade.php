@extends('layouts.app')

@section('content')
<div class="bg-black/30 backdrop-blur-sm border-b border-white/10 px-4 pt-6 pb-4 flex items-center gap-3">
    <a href="{{ route('students.index') }}" class="text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-xs.jpg') }}" class="w-8 h-8 object-contain rounded-full shrink-0" alt="Logo"></a>
    <h1 class="text-xl font-bold text-white">Nuevo Alumno</h1>
</div>

<form method="POST" action="{{ route('students.store') }}" class="p-4 space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Nombre completo *</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
               class="w-full border rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 backdrop-blur-sm border-white/20
                      focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('name') border-red-400 @enderror">
        @error('name')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">DNI</label>
        <input type="text" name="dni" value="{{ old('dni') }}" maxlength="20" inputmode="numeric"
               placeholder="Ej. 12345678"
               class="w-full border rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 backdrop-blur-sm border-white/20
                      focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('dni') border-red-400 @enderror">
        @error('dni')
            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Teléfono / WhatsApp</label>
        <input type="tel" name="phone" value="{{ old('phone') }}"
               class="w-full border border-white/20 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                      bg-white/10 backdrop-blur-sm
                      focus:outline-none focus:ring-2 focus:ring-indigo-400">
    </div>

    <div>
        <label class="block text-sm font-medium text-white/80 mb-1">Notas</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-white/20 rounded-xl px-4 py-3 text-base text-white placeholder-white/40
                         bg-white/10 backdrop-blur-sm
                         focus:outline-none focus:ring-2 focus:ring-indigo-400">{{ old('notes') }}</textarea>
    </div>

    <div class="pt-2">
        <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-lg">
            Registrar alumno
        </button>
    </div>
</form>
@endsection
