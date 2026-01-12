<?php

namespace App\Models\Commerce;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'method',
        'amount',
        'currency',
        'status',
        'transaction_reference',
        'payload',
        'authorized_at',
        'captured_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'payload' => 'array',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
