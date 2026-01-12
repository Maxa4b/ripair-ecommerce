<?php

namespace App\Models\Catalog;

use App\Enums\InventoryMovementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'type',
        'quantity',
        'source',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => InventoryMovementType::class,
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
