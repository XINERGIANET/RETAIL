<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleOrderPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_order_id',
        'amount',
        'payment_method_id',
        'payment_method',
        'digital_wallet_id',
        'digital_wallet',
        'card_id',
        'card',
        'payment_gateway_id',
        'payment_gateway',
        'reference',
        'paid_at',
        'notes',
        'created_by',
        'created_by_name',
        'cash_movement_id',
    ];

    protected $casts = [
        'amount'   => 'decimal:6',
        'paid_at'  => 'datetime',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function digitalWallet()
    {
        return $this->belongsTo(DigitalWallet::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateways::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
