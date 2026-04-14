<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['clase_id', 'student_id', 'plan_id', 'date', 'present', 'notes'];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudentPlan::class, 'plan_id');
    }
}
