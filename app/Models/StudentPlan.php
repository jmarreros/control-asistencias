<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'start_date', 'end_date', 'class_quota', 'price', 'promotion'];

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

    public function classesUsed(): int
    {
        return Attendance::where('student_id', $this->student_id)
            ->where('present', true)
            ->where('date', '>=', $this->start_date)
            ->where('date', '<=', $this->end_date)
            ->count();
    }

    public function classesRemaining(): ?int
    {
        if ($this->class_quota === 'full') return null;
        return max(0, (int) $this->class_quota - $this->classesUsed());
    }

    // 'ok' | 'exhausted' | 'expired' | 'pending' | 'no_plan'
    public function status(): string
    {
        $today = now()->toDateString();

        if ($today < $this->start_date) return 'pending';
        if ($today > $this->end_date)   return 'expired';
        if ($this->class_quota !== 'full' && $this->classesRemaining() <= 0) return 'exhausted';

        return 'ok';
    }

    public function canAttend(): bool
    {
        return $this->status() === 'ok';
    }
}
