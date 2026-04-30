<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class PinController extends Controller
{
    public function show()
    {
        if (session('pin_authenticated')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate(['pin' => 'required']);

        $currentPin = Setting::get('app_pin') ?? env('APP_PIN', '1234');

        if ($request->pin === $currentPin) {
            session()->forget('student_id');
            session(['pin_authenticated' => true]);
            AccessLog::record('admin', 'login', 'Acceso correcto');

            return redirect()->route('dashboard');
        }

        AccessLog::record('admin', 'login_failed', 'PIN incorrecto');

        return back()->withErrors(['pin' => 'PIN incorrecto.']);
    }

    public function logout()
    {
        AccessLog::record('admin', 'logout', 'Sesión cerrada');
        session()->forget('pin_authenticated');

        return redirect()->route('login');
    }
}
