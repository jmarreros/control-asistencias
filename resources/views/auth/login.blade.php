<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>Acceso — Salsa Latin Motion</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-6"
      style="background-image: url('{{ asset('images/fondo.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    {{-- Overlay oscuro sobre el fondo --}}
    <div style="position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:0;"></div>

    <div class="relative z-10 w-full max-w-sm">

        {{-- Logo y nombre --}}
        <div class="flex flex-col items-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Salsa Latin Motion"
                 class="w-28 h-28 object-contain drop-shadow-lg mb-4">
            <h1 class="text-3xl font-extrabold text-white tracking-wide drop-shadow" style="text-shadow: 0 2px 8px rgba(0,0,0,0.7);">
                Salsa Latin Motion
            </h1>
            <p class="text-white/70 text-sm mt-1 tracking-widest uppercase">Control de Asistencias</p>
        </div>

        {{-- Tarjeta de login --}}
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl p-8">
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-5">
                    <input type="password"
                           name="pin"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           autofocus
                           placeholder="••••"
                           class="w-full text-center text-3xl tracking-widest font-bold border-2 rounded-xl py-4 px-4
                                  bg-white/20 text-white placeholder-white/50
                                  @error('pin') border-red-400 @else border-white/30 @enderror
                                  focus:outline-none focus:border-white">
                    @error('pin')
                        <p class="text-red-300 text-sm text-center mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full bg-white text-gray-900 font-bold py-4 rounded-xl text-lg active:bg-gray-100 shadow-lg">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-white/30 text-xs">
            ¿Eres alumno?
            <a href="{{ route('student.search') }}" class="text-white/50 underline">Acceso alumnos</a>
        </p>

    </div>
</body>
</html>
