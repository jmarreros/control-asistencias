<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clase extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'schedule', 'description', 'active'];

    protected $casts = ['active' => 'boolean', 'schedule' => 'array'];

    public function scheduleText(): string
    {
        if (!$this->schedule) return '';

        $labels = ['lun' => 'Lun', 'mar' => 'Mar', 'mie' => 'Mié', 'jue' => 'Jue', 'vie' => 'Vie', 'sab' => 'Sáb', 'dom' => 'Dom'];

        // Agrupar días que comparten el mismo horario
        $groups = [];
        foreach ($this->schedule as $day => $times) {
            $start = is_array($times) ? ($times['start'] ?? '') : $times;
            $end   = is_array($times) ? ($times['end']   ?? '') : '';
            $groups[$start . '|' . $end][] = $labels[$day] ?? $day;
        }

        return collect($groups)->map(function ($days, $timeKey) {
            [$start, $end] = explode('|', $timeKey);
            $fmt   = fn($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', $t)->format('h:ia') : '';
            $timeStr = $fmt($start) . ($end ? ' - ' . $fmt($end) : '');
            $daysStr = implode(' - ', $days);
            return '<span class="text-sm">' . $daysStr . '</span> <span class="text-xs">(' . $timeStr . ')</span>';
        })->implode(' · ');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot('enrolled_at')
            ->orderBy('students.name');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
