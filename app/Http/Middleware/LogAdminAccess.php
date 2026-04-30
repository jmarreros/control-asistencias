<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAccess
{
    /** Solo registra visitas GET a páginas principales (no cada toggle/AJAX). */
    private const TRACKED_ROUTES = [
        'dashboard' => 'Dashboard',
        'students.index' => 'Alumnos',
        'clases.index' => 'Cursos',
        'attendance.index' => 'Asistencia',
        'reports.index' => 'Reportes',
        'settings.edit' => 'Configuración',
        'logs.index' => 'Logs de acceso',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $routeName = $request->route()?->getName();

            if ($routeName && isset(self::TRACKED_ROUTES[$routeName])) {
                AccessLog::record('admin', 'page_visit', self::TRACKED_ROUTES[$routeName]);
            }
        }

        return $response;
    }
}
