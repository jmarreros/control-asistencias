<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'dni', 'phone', 'notes', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function clases(): BelongsToMany
    {
        return $this->belongsToMany(Clase::class)
            ->withPivot('enrolled_at');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(StudentPlan::class)->orderByDesc('start_date');
    }

    public function currentPlan(): HasOne
    {
        return $this->hasOne(StudentPlan::class)->latestOfMany('start_date');
    }
}
