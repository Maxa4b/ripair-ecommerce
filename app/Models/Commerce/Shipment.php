<?php

namespace App\Models\Commerce;

use App\Models\Logistics\ShippingMethod;
use App\Models\Logistics\Transporter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'transporter_id',
        'status',
        'tracking_number',
        'tracking_url',
        'label_path',
        'packages',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'packages' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
