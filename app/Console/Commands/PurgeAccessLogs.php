<?php

namespace App\Console\Commands;

use App\Models\AccessLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('logs:purge {--days=30 : Días de antigüedad mínima para eliminar}')]
#[Description('Elimina logs de acceso con más de N días de antigüedad')]
class PurgeAccessLogs extends Command
{
    public function handle(): void
    {
        $days = (int) $this->option('days');

        $deleted = AccessLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Eliminados {$deleted} logs con más de {$days} días de antigüedad.");
    }
}
