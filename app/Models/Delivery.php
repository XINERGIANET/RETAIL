<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'deliverable_type',
        'deliverable_id',
        'status',
        'delivered_at',
        'delivered_by',
        'pickup_at',
        'pickup_responsible',
        'notes',
        'tracking_number',
        'evidence_photo',
        'payment_confirmed',
    ];

    protected $casts = [
        'delivered_at'      => 'datetime',
        'pickup_at'         => 'datetime',
        'payment_confirmed' => 'boolean',
    ];

    public function deliverable()
    {
        return $this->morphTo();
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
