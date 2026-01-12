<?php

namespace App\Models\Sav;

use App\Models\Commerce\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rma_request_id',
        'order_item_id',
        'quantity',
        'evaluation_status',
        'notes',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RmaRequest::class, 'rma_request_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
