<?php

namespace App\Models\Commerce;

use App\Enums\DeliveryType;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Logistics\ShippingMethod;
use App\Models\Support\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_id',
        'shipping_method_id',
        'promo_code_id',
        'number',
        'status',
        'payment_status',
        'fulfillment_status',
        'delivery_type',
        'currency',
        'subtotal_ht',
        'subtotal_ttc',
        'tax_total',
        'discount_total',
        'shipping_total',
        'total_ht',
        'total_ttc',
        'billing_address',
        'shipping_address',
        'tracking_number',
        'carrier_name',
        'withdrawal_slot',
        'workshop_reference',
        'is_pro',
        'pro_po_number',
        'due_at',
        'invoice_number',
        'customer_note',
        'internal_note',
        'metadata',
        'placed_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'fulfillment_status' => FulfillmentStatus::class,
        'delivery_type' => DeliveryType::class,
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'is_pro' => 'boolean',
        'metadata' => 'array',
        'due_at' => 'date',
        'placed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
