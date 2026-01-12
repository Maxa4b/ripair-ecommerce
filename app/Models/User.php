<?php

namespace App\Models;

use App\Models\Commerce\Cart;
use App\Models\Commerce\Order;
use App\Models\Sav\RmaRequest;
use App\Models\Support\Address;
use App\Models\Support\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'account_type',
        'pro_status',
        'company_name',
        'siret',
        'vat_number',
        'website',
        'pro_discount_rate',
        'can_access_ht_prices',
        'preferences',
        'pro_validated_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pro_validated_at' => 'datetime',
            'preferences' => 'array',
            'can_access_ht_prices' => 'boolean',
            'pro_discount_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function rmaRequests(): HasMany
    {
        return $this->hasMany(RmaRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isPro(): bool
    {
        return $this->account_type === 'pro' && $this->pro_status === 'approved';
    }
}
