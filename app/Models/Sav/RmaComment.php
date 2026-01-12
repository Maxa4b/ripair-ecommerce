<?php

namespace App\Models\Sav;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmaComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rma_request_id',
        'user_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RmaRequest::class, 'rma_request_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
