<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#4f46e5">
    <title>Acceso — Academia</title>
    <script>
        if (!window.matchMedia('(prefers-color-scheme: light)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-indigo-600 dark:bg-gray-900 min-h-screen flex items-center justify-center p-6 transition-colors">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Control de Asistencias</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Ingresa tu PIN para continuar</p>
        </div>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="mb-6">
                <input type="password"
                       name="pin"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       autofocus
                       placeholder="••••"
                       class="w-full text-center text-3xl tracking-widest font-bold border-2 rounded-xl py-4 px-4
                              bg-white dark:bg-gray-700 dark:text-white dark:placeholder-gray-500
                              @error('pin') border-red-400 bg-red-50 dark:bg-red-900/20 @else border-gray-200 dark:border-gray-600 @enderror
                              focus:outline-none focus:border-indigo-500">
                @error('pin')
                    <p class="text-red-500 text-sm text-center mt-2">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full bg-indigo-600 text-white font-bold py-4 rounded-xl text-lg active:bg-indigo-700">
                Entrar
            </button>
        </form>
    </div>
</body>
</html>
