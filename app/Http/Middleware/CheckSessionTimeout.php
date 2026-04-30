<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    private const TIMEOUT_MINUTES = 480; // 8 horas

    public function handle(Request $request, Closure $next): Response
    {
        if (session('pin_authenticated')) {
            $lastActivity = session('last_activity');

            if ($lastActivity && now()->diffInMinutes($lastActivity) > self::TIMEOUT_MINUTES) {
                session()->forget(['pin_authenticated', 'last_activity']);

                return redirect()->route('login')
                    ->withErrors(['pin' => 'Tu sesión expiró por inactividad. Vuelve a ingresar el PIN.']);
            }

            session(['last_activity' => now()]);
        }

        return $next($request);
    }
}
