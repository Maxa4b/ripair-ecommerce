<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'website',
        'country',
        'description',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    public function deviceModels(): HasMany
    {
        return $this->hasMany(DeviceModel::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
