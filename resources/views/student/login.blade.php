<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>Acceso Alumnos — Salsa Latin Motion</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-6"
      style="background-image: url('{{ asset('images/fondo.jpg') }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div style="position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:0;"></div>

    <div class="relative z-10 w-full max-w-sm">

        <div class="flex flex-col items-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="Salsa Latin Motion"
                 class="w-28 h-28 object-contain drop-shadow-lg mb-4">
            <h1 class="text-3xl font-extrabold text-white tracking-wide drop-shadow"
                style="text-shadow: 0 2px 8px rgba(0,0,0,0.7);">
                Salsa Latin Motion
            </h1>
            <p class="text-white/70 text-sm mt-1 tracking-widest uppercase">Portal de Alumnos</p>
        </div>

        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl shadow-2xl p-8">
            <p class="text-white/60 text-sm text-center mb-5">Ingresa tu DNI para ver tus asistencias</p>
            <form method="POST" action="{{ route('student.login.post') }}">
                @csrf
                <div class="mb-5">
                    <input type="text"
                           name="dni"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           maxlength="20"
                           autofocus
                           placeholder="Tu número de DNI"
                           value="{{ old('dni') }}"
                           class="w-full text-center text-2xl tracking-widest font-bold border-2 rounded-xl py-4 px-4
                                  bg-white/20 text-white placeholder-white/40
                                  @error('dni') border-red-400 @else border-white/30 @enderror
                                  focus:outline-none focus:border-white">
                    @error('dni')
                        <p class="text-red-300 text-sm text-center mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full bg-white text-gray-900 font-bold py-4 rounded-xl text-lg active:bg-gray-100 shadow-lg">
                    Ver mis asistencias
                </button>
            </form>
        </div>

        <p class="text-center mt-6 text-white/30 text-xs">
            ¿Eres administrador?
            <a href="{{ route('login') }}" class="text-white/50 underline">Acceso admin</a>
        </p>

    </div>
</body>
</html>
