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
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
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
