<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0f0f1a">
    <title>{{ $title ?? 'Mi Asistencia' }} — Salsa Latin Motion</title>
    <script>
        (function () {
            var t = localStorage.getItem('slm-theme') || 'dark';
            var h = document.documentElement;
            if (t === 'light') {
                h.classList.add('light');
                h.style.background = '#f0f2f7';
                document.querySelector('meta[name="color-scheme"]').content = 'light';
                document.querySelector('meta[name="theme-color"]').content = '#f0f2f7';
            } else {
                h.style.background = '#0a0a14';
            }
        })();
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preload" as="image" href="{{ asset('images/fondo.jpg') }}">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-white antialiased min-h-screen">

    {{-- Fondo fijo --}}
    <div style="position:fixed; inset:0; z-index:1; background-image:url('{{ asset('images/fondo.jpg') }}'); background-size:cover; background-position:center;"></div>
    <div class="slm-overlay" style="position:fixed; inset:0; z-index:2; background:rgba(0,0,0,0.30);"></div>

    <main class="pb-8 min-h-screen max-w-lg mx-auto relative" style="z-index:3;">
        @yield('content')
    </main>

    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 3000)"
             style="position:fixed; bottom:1.5rem; left:0; right:0; z-index:50; padding:0 1rem; pointer-events:none; max-width:32rem; margin:0 auto;">
            <div class="bg-green-500 text-white px-4 py-3 text-sm font-medium text-center rounded-xl shadow-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 4000)"
             style="position:fixed; bottom:1.5rem; left:0; right:0; z-index:50; padding:0 1rem; pointer-events:none; max-width:32rem; margin:0 auto;">
            <div class="bg-red-600 text-white px-4 py-3 text-sm font-medium text-center rounded-xl shadow-lg">
                {{ session('error') }}
            </div>
        </div>
    @endif

</body>
</html>
