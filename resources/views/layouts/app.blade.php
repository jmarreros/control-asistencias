<!DOCTYPE html>
<html lang="es" style="background:#0a0a14;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0f0f1a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Asistencias">
    <title>{{ $title ?? 'Asistencias' }} — Salsa Latin Motion</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="preload" as="image" href="{{ asset('images/fondo.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-white antialiased min-h-screen" style="background:#0a0a14;">

    <style>
        @keyframes slm-load {
            0%   { transform: scaleX(0);    opacity: 1; }
            60%  { transform: scaleX(0.85); opacity: 1; }
            90%  { transform: scaleX(0.97); opacity: 1; }
            100% { transform: scaleX(1);    opacity: 0; }
        }
        @keyframes slm-dot {
            0%, 100% { transform: scale(0.7); opacity: 0.3; }
            50%       { transform: scale(1.3); opacity: 1;   }
        }
    </style>

    {{-- Barra de progreso: CSS pura, corre en cada carga de página --}}
    <div style="position:fixed; top:0; left:0; right:0; z-index:99999; height:4px; pointer-events:none; overflow:hidden;">
        <div style="height:100%; width:100%; transform-origin:left center; background:linear-gradient(90deg,#6366f1,#a78bfa,#818cf8); box-shadow:0 0 10px rgba(139,92,246,0.9); border-radius:0 3px 3px 0; animation:slm-load 0.8s cubic-bezier(0.4,0,0.2,1) forwards;"></div>
    </div>

    {{-- Splash: solo en apertura en frío (sessionStorage vacío = nueva sesión PWA) --}}
    <div id="slm-splash" style="position:fixed; inset:0; z-index:99998; background:#0a0a14; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0;">
        <img src="/images/logo-xs.jpg" alt="" style="width:96px; height:96px; border-radius:50%; object-fit:cover; box-shadow:0 0 40px rgba(99,102,241,0.5); margin-bottom:20px;">
        <p style="color:#fff; font-size:21px; font-weight:700; letter-spacing:0.02em; margin:0;">Salsa Latin Motion</p>
        <p style="color:rgba(255,255,255,0.35); font-size:12px; letter-spacing:0.12em; margin:6px 0 36px; text-transform:uppercase;">Control de Asistencias</p>
        <div style="display:flex; gap:8px; align-items:center;">
            <div style="width:7px; height:7px; border-radius:50%; background:#6366f1; animation:slm-dot 1.2s ease-in-out 0s infinite;"></div>
            <div style="width:7px; height:7px; border-radius:50%; background:#818cf8; animation:slm-dot 1.2s ease-in-out 0.2s infinite;"></div>
            <div style="width:7px; height:7px; border-radius:50%; background:#a78bfa; animation:slm-dot 1.2s ease-in-out 0.4s infinite;"></div>
        </div>
    </div>
    <script>
        (function () {
            var splash = document.getElementById('slm-splash');
            if (sessionStorage.getItem('slm_s')) {
                // Navegación interna: ocultar splash de inmediato
                splash.style.display = 'none';
            } else {
                // Primera carga / apertura en frío: mostrar splash mínimo 900ms
                sessionStorage.setItem('slm_s', '1');
                var t0 = Date.now();
                window.addEventListener('DOMContentLoaded', function () {
                    var wait = Math.max(0, 900 - (Date.now() - t0));
                    setTimeout(function () {
                        splash.style.transition = 'opacity 0.45s ease';
                        splash.style.opacity = '0';
                        setTimeout(function () { splash.style.display = 'none'; }, 450);
                    }, wait);
                });
            }
        })();
    </script>

    {{-- Fondo fijo --}}
    <div style="position:fixed; inset:0; z-index:1; background-image:url('{{ asset('images/fondo.jpg') }}'); background-size:cover; background-position:center;"></div>
    <div style="position:fixed; inset:0; z-index:2; background:rgba(0,0,0,0.25);"></div>

    <main class="pb-20 min-h-screen max-w-lg mx-auto relative" style="z-index:3;">
        @yield('content')
    </main>

    {{-- Navegación inferior fija --}}
    <nav style="position:fixed; bottom:0; left:0; right:0; z-index:40; backdrop-filter:blur(12px); background:rgba(0,0,0,0.5); border-top:1px solid rgba(255,255,255,0.1); max-width:32rem; margin:0 auto;">
        <div class="flex">
            <a href="{{ route('dashboard') }}"
               class="flex-1 flex flex-col items-center py-2 text-xs {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-white/50' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <a href="{{ route('students.index') }}"
               class="flex-1 flex flex-col items-center py-2 text-xs {{ request()->routeIs('students.*') ? 'text-indigo-400' : 'text-white/50' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Alumnos
            </a>
            <a href="{{ route('attendance.index') }}"
               class="flex-1 flex flex-col items-center py-2 text-xs {{ request()->routeIs('attendance.*') ? 'text-teal-400' : 'text-white/50' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Asistencia
            </a>
            <a href="{{ route('clases.index') }}"
               class="flex-1 flex flex-col items-center py-2 text-xs {{ request()->routeIs('clases.*') ? 'text-indigo-400' : 'text-white/50' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Cursos
            </a>
            <a href="{{ route('reports.index') }}"
               class="flex-1 flex flex-col items-center py-2 text-xs {{ request()->routeIs('reports.*') ? 'text-indigo-400' : 'text-white/50' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Reportes
            </a>
        </div>
    </nav>

    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 3000)"
             style="position:fixed; bottom:5rem; left:0; right:0; z-index:50; padding:0 1rem; pointer-events:none; max-width:32rem; margin:0 auto;">
            <div class="bg-green-500 text-white px-4 py-3 text-sm font-medium text-center rounded-xl shadow-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition
             x-init="setTimeout(() => show = false, 4000)"
             style="position:fixed; bottom:5rem; left:0; right:0; z-index:50; padding:0 1rem; pointer-events:none; max-width:32rem; margin:0 auto;">
            <div class="bg-red-600 text-white px-4 py-3 text-sm font-medium text-center rounded-xl shadow-lg">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }

    </script>
</body>
</html>
