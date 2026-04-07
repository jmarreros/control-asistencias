<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $prices = [
            'price_8h'   => Setting::get('price_8h',   120),
            'price_12h'  => Setting::get('price_12h',  150),
            'price_16h'  => Setting::get('price_16h',  170),
            'price_full' => Setting::get('price_full', 190),
        ];

        return view('settings.edit', compact('prices'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'price_8h'   => 'required|numeric|min:0',
            'price_12h'  => 'required|numeric|min:0',
            'price_16h'  => 'required|numeric|min:0',
            'price_full' => 'required|numeric|min:0',
        ]);

        Setting::set('price_8h',   $request->price_8h);
        Setting::set('price_12h',  $request->price_12h);
        Setting::set('price_16h',  $request->price_16h);
        Setting::set('price_full', $request->price_full);

        return redirect()->route('settings.edit')->with('success', 'Configuración guardada.');
    }
}
