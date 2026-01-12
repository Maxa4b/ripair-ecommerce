<?php

namespace App\Models\Support;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'brand_id',
        'min_quantity',
        'min_subtotal',
        'discount_type',
        'discount_value',
        'is_stackable',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'is_stackable' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
