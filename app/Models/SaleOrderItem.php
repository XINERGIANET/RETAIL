<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_order_id',
        'product_id',
        'product_snapshot',
        'quantity',
        'unit_price',
        'subtotal',
        'returned_qty',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'quantity'         => 'decimal:6',
        'unit_price'       => 'decimal:6',
        'subtotal'         => 'decimal:6',
        'returned_qty'     => 'decimal:6',
    ];

    public function getActiveQtyAttribute(): float
    {
        return (float) $this->quantity - (float) $this->returned_qty;
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleOrderReturn::class);
    }
}
