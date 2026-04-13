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
            'price_24h'   => Setting::get('price_24h',   200),
            'price_full1' => Setting::get('price_full1', 190),
            'price_full2' => Setting::get('price_full2', 210),
        ];

        $promos = [
            'promo_10'  => (bool) Setting::get('promo_10',  0),
            'promo_20'  => (bool) Setting::get('promo_20',  0),
            'promo_30'  => (bool) Setting::get('promo_30',  0),
            'promo_2x1' => (bool) Setting::get('promo_2x1', 0),
        ];

        $notify = [
            'notify_days_before'       => (int) Setting::get('notify_days_before', 3),
            'notify_classes_remaining' => (int) Setting::get('notify_classes_remaining', 1),
            'notify_message'           => Setting::get('notify_message', ''),
            'notify_expired_message'   => Setting::get('notify_expired_message', ''),
        ];

        return view('settings.edit', compact('prices', 'promos', 'notify'));
    }

    public function update(Request $request)
    {
        $rules = [
            'price_8h'                 => 'required|numeric|min:0',
            'price_12h'                => 'required|numeric|min:0',
            'price_16h'                => 'required|numeric|min:0',
            'price_24h'                => 'required|numeric|min:0',
            'price_full1'              => 'required|numeric|min:0',
            'price_full2'              => 'required|numeric|min:0',
            'notify_days_before'       => 'required|integer|min:0|max:30',
            'notify_classes_remaining' => 'required|integer|min:0|max:10',
            'notify_message'           => 'required|string|max:255',
            'notify_expired_message'   => 'required|string|max:255',
        ];

        if ($request->filled('new_pin')) {
            $rules['current_pin'] = 'required';
            $rules['new_pin']     = 'required|digits_between:4,8|confirmed';
        }

        $request->validate($rules);

        if ($request->filled('new_pin')) {
            $currentPin = Setting::get('app_pin') ?? env('APP_PIN', '1234');
            if ($request->current_pin !== $currentPin) {
                return back()->withErrors(['current_pin' => 'El PIN actual no es correcto.'])->withInput();
            }
            Setting::set('app_pin', $request->new_pin);
        }

        Setting::set('price_8h',   $request->price_8h);
        Setting::set('price_12h',  $request->price_12h);
        Setting::set('price_16h',  $request->price_16h);
        Setting::set('price_24h',   $request->price_24h);
        Setting::set('price_full1', $request->price_full1);
        Setting::set('price_full2', $request->price_full2);

        Setting::set('promo_10',  $request->boolean('promo_10')  ? 1 : 0);
        Setting::set('promo_20',  $request->boolean('promo_20')  ? 1 : 0);
        Setting::set('promo_30',  $request->boolean('promo_30')  ? 1 : 0);
        Setting::set('promo_2x1', $request->boolean('promo_2x1') ? 1 : 0);

        Setting::set('notify_days_before',       $request->notify_days_before);
        Setting::set('notify_classes_remaining', $request->notify_classes_remaining);
        Setting::set('notify_message',           $request->notify_message);
        Setting::set('notify_expired_message',   $request->notify_expired_message);

        return redirect()->route('settings.edit')->with('success', 'Configuración guardada.');
    }
}
