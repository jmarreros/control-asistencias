<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaults = [
            'notify_days_before'       => '3',
            'notify_classes_remaining' => '1',
            'notify_message'           => 'Hola {nombre}, tu plan está por vencer. Te quedan {clases} clase(s) y vence el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!',
            'notify_expired_message'   => 'Hola {nombre}, tu plan venció el {fecha}. ¡Renueva ahora y sigue bailando con nosotros!',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'notify_days_before',
            'notify_classes_remaining',
            'notify_message',
            'notify_expired_message',
        ])->delete();
    }
};
