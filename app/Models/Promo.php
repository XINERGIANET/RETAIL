<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = ['name', 'image', 'price', 'status', 'end_date'];

    protected $casts = [
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'promo_products')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}
