<?php

namespace App\Models\Logistics;

use App\Models\Commerce\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'transporter_id',
        'name',
        'slug',
        'description',
        'zone',
        'min_weight',
        'max_weight',
        'delivery_time',
        'base_price',
        'metadata',
        'type',
        'supports_tracking',
        'is_active',
        'min_delay_days',
        'max_delay_days',
        'price_per_kg',
        'configuration',
    ];

    protected $casts = [
        'supports_tracking' => 'boolean',
        'is_active' => 'boolean',
        'configuration' => 'array',
        'metadata' => 'array',
    ];

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
