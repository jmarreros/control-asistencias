<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_id', 'start_date', 'end_date', 'class_quota', 'classes_remaining', 'price', 'promotion'];

    const PROMOTION_LABELS = [
        'promo_10'  => 'Descuento 10%',
        'promo_20'  => 'Descuento 20%',
        'promo_30'  => 'Descuento 30%',
        'promo_2x1' => 'Promoción 2x1',
    ];

    public function promotionLabel(): ?string
    {
        return self::PROMOTION_LABELS[$this->promotion] ?? null;
    }

    protected $casts = ['price' => 'decimal:2'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $today >= $this->start_date && $today <= $this->end_date;
    }

    public function classesUsed(): ?int
    {
        if (in_array($this->class_quota, ['full1', 'full2'])) return null;
        return (int) $this->class_quota - ($this->classes_remaining ?? 0);
    }

    public function classesRemaining(): ?int
    {
        if (in_array($this->class_quota, ['full1', 'full2'])) return null;
        return $this->classes_remaining ?? 0;
    }

    // 'ok' | 'exhausted' | 'expired' | 'pending' | 'no_plan'
    public function status(): string
    {
        $today = now()->toDateString();

        if ($today < $this->start_date) return 'pending';
        if ($today > $this->end_date)   return 'expired';
        if (!in_array($this->class_quota, ['full1', 'full2']) && ($this->classes_remaining ?? 0) <= 0) return 'exhausted';

        return 'ok';
    }

    public function canAttend(): bool
    {
        return $this->status() === 'ok';
    }
}
