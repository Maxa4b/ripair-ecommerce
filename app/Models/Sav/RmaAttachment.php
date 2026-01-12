<?php

namespace App\Models\Sav;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmaAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rma_request_id',
        'path',
        'type',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RmaRequest::class, 'rma_request_id');
    }
}
