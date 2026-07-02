<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CashMovements extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (CashMovements $cashMovement) {
            $conceptDescription = PaymentConcept::query()
                ->whereKey((int) $cashMovement->payment_concept_id)
                ->value('description');

            // La apertura crea el movimiento antes de crear la relacion de turno-caja.
            if (str_contains(mb_strtolower((string) $conceptDescription, 'UTF-8'), 'apertura')) {
                return;
            }

            $cashRegisterId = (int) $cashMovement->cash_register_id;
            $branchId = (int) $cashMovement->branch_id;

            if (!CashShiftRelation::isOpenFor($cashRegisterId, $branchId)) {
                throw new \RuntimeException(
                    'No se puede generar el movimiento de dinero porque la caja seleccionada no esta aperturada.'
                );
            }
        });
    }

    protected $fillable = [
        'payment_concept_id',
        'currency',
        'exchange_rate',
        'total',
        'cash_register_id',
        'cash_register',
        'shift_id',
        'shift_snapshot',
        'counting_snapshot',
        'movement_id',
        'branch_id',
    ];

    protected $casts = [
        'shift_snapshot' => 'array',
        'counting_snapshot' => 'array',
    ];

    public function paymentConcept()
    {
        return $this->belongsTo(PaymentConcept::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function details() 
    {
        return $this->hasMany(CashMovementDetail::class, 'cash_movement_id');
    }

    public function accountReceivablePayable()
    {
        return $this->hasOne(AccountReceivablePayable::class, 'cash_movement_id');
    }
}
