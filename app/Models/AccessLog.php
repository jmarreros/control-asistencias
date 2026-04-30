<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['type', 'action', 'detail', 'ip', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public static function record(string $type, string $action, ?string $detail = null, ?string $ip = null): void
    {
        static::create([
            'type' => $type,
            'action' => $action,
            'detail' => $detail,
            'ip' => $ip ?? request()->ip(),
        ]);
    }
}
