<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'company',
        'first_name',
        'last_name',
        'line1',
        'line2',
        'postal_code',
        'city',
        'state',
        'country_code',
        'phone',
        'instructions',
        'is_default_billing',
        'is_default_shipping',
    ];

    protected $casts = [
        'is_default_billing' => 'boolean',
        'is_default_shipping' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toArrayForOrder(): array
    {
        return $this->only([
            'first_name',
            'last_name',
            'company',
            'line1',
            'line2',
            'postal_code',
            'city',
            'state',
            'country_code',
            'phone',
        ]);
    }
}
