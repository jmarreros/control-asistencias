<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckStudentAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('student_id')) {
            return redirect()->route('student.login');
        }

        return $next($request);
    }
}
