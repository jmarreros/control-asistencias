<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('pin_authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
