<?php

namespace App\Models\Support;

use App\Models\Commerce\Cart;
use App\Models\Commerce\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'value',
        'max_uses',
        'per_user_limit',
        'used_count',
        'min_subtotal',
        'applies_to_pro',
        'is_stackable',
        'restrictions',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'applies_to_pro' => 'boolean',
        'is_stackable' => 'boolean',
        'is_active' => 'boolean',
        'restrictions' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
