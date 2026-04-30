<?php

use App\Http\Middleware\CheckPin;
use App\Http\Middleware\CheckStudentAuth;
use App\Http\Middleware\LogAdminAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.pin' => CheckPin::class,
            'check.student' => CheckStudentAuth::class,
            'log.access' => LogAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
