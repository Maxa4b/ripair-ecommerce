<?php

namespace App\Models\Catalog;

use App\Enums\AvailabilityStatus;
use App\Models\Commerce\CartItem;
use App\Models\Commerce\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'color',
        'quality',
        'revision',
        'attributes',
        'price_ht',
        'price_ttc',
        'tax_rate',
        'stock_on_hand',
        'stock_reserved',
        'stock_threshold',
        'workshop_reserved',
        'availability_status',
        'lead_time_days',
        'is_default',
    ];

    protected $casts = [
        'attributes' => 'array',
        'availability_status' => AvailabilityStatus::class,
        'is_default' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock_on_hand - $this->stock_reserved - $this->workshop_reserved);
    }
}
