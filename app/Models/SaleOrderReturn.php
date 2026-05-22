<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrderReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_order_id',
        'sale_order_item_id',
        'quantity',
        'unit_price',
        'subtotal',
        'returned_at',
        'notes',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'quantity'    => 'decimal:6',
        'unit_price'  => 'decimal:6',
        'subtotal'    => 'decimal:6',
        'returned_at' => 'datetime',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function saleOrderItem()
    {
        return $this->belongsTo(SaleOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
