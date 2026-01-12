<?php

namespace App\Models\Catalog;

use App\Models\Sav\RmaItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'product_type_id',
        'name',
        'slug',
        'internal_reference',
        'supplier_reference',
        'short_description',
        'description',
        'compatibility_notes',
        'quality',
        'warranty_months',
        'tax_rate',
        'shipping_weight',
        'lead_time_days',
        'is_published',
        'is_best_seller',
        'attributes',
        'meta',
        'rhc_status',
        'rhc_attempts',
        'rhc_last_run_at',
        'rhc_generated_title',
        'rhc_generated_description',
        'rhc_generated_image',
        'rhc_last_error',
        'rhc_last_payload',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_best_seller' => 'boolean',
        'attributes' => 'array',
        'meta' => 'array',
        'rhc_last_payload' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function deviceModels(): BelongsToMany
    {
        return $this->belongsToMany(DeviceModel::class)->withTimestamps();
    }

    public function rmaItems(): HasMany
    {
        return $this->hasMany(RmaItem::class);
    }
}
