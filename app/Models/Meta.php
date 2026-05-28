<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'period_type',
        'start_date',
        'end_date',
        'target_amount',
        'target_quantity',
        'notes',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'target_amount'   => 'decimal:2',
        'target_quantity' => 'decimal:4',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** 'active' | 'upcoming' | 'finished' */
    public function getStatusAttribute(): string
    {
        $today = now()->toDateString();
        if ($today < $this->start_date->toDateString()) {
            return 'upcoming';
        }
        if ($today > $this->end_date->toDateString()) {
            return 'finished';
        }
        return 'active';
    }

    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'week'   => 'Semanal',
            'biweek' => 'Quincenal',
            'month'  => 'Mensual',
            default  => 'Personalizado',
        };
    }
}
