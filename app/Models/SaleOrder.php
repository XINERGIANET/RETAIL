<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'status',
        'currency',
        'exchange_rate',
        'total',
        'paid',
        'due_date',
        'notes',
        'branch_id',
        'person_id',
        'person_name',
        'movement_id',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:3',
        'total'         => 'decimal:6',
        'paid'          => 'decimal:6',
        'due_date'      => 'datetime',
    ];

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - (float) $this->paid;
    }

    public function items()
    {
        return $this->hasMany(SaleOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SaleOrderPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleOrderReturn::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
