<?php

namespace App\Http\Controllers;

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

        if ($request->pin === env('APP_PIN', '1234')) {
            session()->forget('student_id');
            session(['pin_authenticated' => true]);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['pin' => 'PIN incorrecto.']);
    }

    public function logout()
    {
        session()->forget('pin_authenticated');
        return redirect()->route('login');
    }
}
