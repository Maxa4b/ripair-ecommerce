<?php

namespace App\Models\Logistics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transporter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'website',
        'contact_email',
        'supports_pickup_points',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'supports_pickup_points' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }
}
