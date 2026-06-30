<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchElectronicBillingConfig extends Model
{
    protected $fillable = [
        'branch_id',
        'provider',
        'enabled',
        'api_url',
        'persona_id',
        'persona_token',
        'series_boleta',
        'series_factura',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
