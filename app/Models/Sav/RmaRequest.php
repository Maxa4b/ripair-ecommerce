<?php

namespace App\Models\Sav;

use App\Enums\RmaStatus;
use App\Models\Commerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RmaRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'rma_number',
        'order_id',
        'user_id',
        'status',
        'reason',
        'description',
        'conditions_confirmed',
        'metadata',
    ];

    protected $casts = [
        'status' => RmaStatus::class,
        'conditions_confirmed' => 'boolean',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RmaItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RmaAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RmaComment::class);
    }
}
