<?php

namespace App\Models\Logistics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_method_id',
        'zone',
        'min_weight',
        'max_weight',
        'min_total',
        'max_total',
        'price',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
