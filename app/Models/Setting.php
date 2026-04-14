<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::where('key', $key)->value('value');
        }

        return static::$cache[$key] ?? $default;
    }

    /** Carga varias claves en una sola query y las mete al caché. */
    public static function preload(array $keys): void
    {
        $missing = array_filter($keys, fn($k) => !array_key_exists($k, static::$cache));

        if (empty($missing)) return;

        static::whereIn('key', $missing)
            ->pluck('value', 'key')
            ->each(fn($value, $key) => static::$cache[$key] = $value);

        // Claves no encontradas en BD → caché null para no volver a consultarlas
        foreach ($missing as $key) {
            if (!array_key_exists($key, static::$cache)) {
                static::$cache[$key] = null;
            }
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }
}
