<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $query = AccessLog::orderByDesc('created_at');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $logs = $query->paginate(50)->withQueryString();
        $total = AccessLog::count();

        return view('logs.index', compact('logs', 'type', 'total'));
    }

    public function destroy(Request $request)
    {
        $days = (int) $request->input('days', 0);

        if ($days > 0) {
            AccessLog::where('created_at', '<', now()->subDays($days))->delete();
            $message = "Logs anteriores a {$days} días eliminados.";
        } else {
            AccessLog::truncate();
            $message = 'Todos los logs han sido eliminados.';
        }

        return redirect()->route('logs.index')->with('success', $message);
    }
}
