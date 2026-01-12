<?php

namespace App\Models\Legacy;

use App\Enums\AvailabilityStatus;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Repair extends Model
{
    protected $table = 'repairs';

    public $timestamps = false;

    protected $fillable = [
        'category',
        'brand',
        'model',
        'problem',
        'price',
        'duration',
        'supplier',
        'supplier_ref',
        'supplier_url',
        'supplier_price',
        'supplier_stock',
        'image_url',
        'component_brand',
        'color',
        'update_done',
    ];

    protected $casts = [
        'price' => 'float',
        'supplier_price' => 'float',
        'created_at' => 'datetime',
        'update_done' => 'boolean',
    ];

    protected $appends = ['slug'];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('brand', 'like', "%{$term}%")
                ->orWhere('model', 'like', "%{$term}%")
                ->orWhere('problem', 'like', "%{$term}%")
                ->orWhere('supplier_ref', 'like', "%{$term}%");
        });
    }

    public function getTitleAttribute(): string
    {
        return trim("{$this->brand} {$this->model} — {$this->problem}");
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->title.'-'.$this->id);
    }

    public function getComputedPriceAttribute(): float
    {
        return $this->computePublicPrice();
    }

    public function getDisplayPriceAttribute(): string
    {
        return number_format($this->computed_price, 2, ',', ' ');
    }

    public function ensureVariant(): ProductVariant
    {
        return DB::transaction(function () {
            $reference = 'LEGACY-'.$this->id;
            $slug = Str::slug(($this->brand ? $this->brand.' ' : '').($this->model ? $this->model.' ' : '').($this->problem ?? 'piece').' legacy-'.$this->id);
            $vatRate = config('pricing.vat_rate', 0.2);
            $priceTtc = $this->computed_price;
            $priceHt = $priceTtc / max(1 + $vatRate, 1);
            $stock = max(0, (int) ($this->supplier_stock ?? 0));

            $brandId = null;
            if ($this->brand) {
                $brand = Brand::firstOrCreate(
                    ['slug' => Str::slug($this->brand)],
                    ['name' => $this->brand]
                );
                $brandId = $brand->id;
            }

            $categoryId = null;
            if ($this->category) {
                $category = Category::firstOrCreate(
                    ['slug' => Str::slug($this->category)],
                    ['name' => $this->category]
                );
                $categoryId = $category->id;
            }

            $product = Product::firstOrCreate(
                ['internal_reference' => $reference],
                [
                    'name' => $this->problem ?: $this->title,
                    'slug' => $slug,
                ]
            );

            $product->fill([
                'name' => $this->problem ?: $this->title,
                'slug' => $slug,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'supplier_reference' => $this->supplier_ref,
                'short_description' => trim($this->brand.' '.$this->model),
                'description' => $this->problem,
                'quality' => $this->resolveQuality(),
                'tax_rate' => $vatRate * 100,
                'is_published' => true,
                'attributes' => [
                    'legacy_repair_id' => $this->id,
                    'category' => $this->category,
                    'problem' => $this->problem,
                ],
                'meta' => [
                    'legacy' => true,
                ],
            ])->save();

            $availability = match (true) {
                $stock <= 0 => AvailabilityStatus::OutOfStock,
                $stock < 3 => AvailabilityStatus::LowStock,
                default => AvailabilityStatus::InStock,
            };

            $variant = ProductVariant::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => $reference,
                ],
                [
                    'price_ht' => round($priceHt, 2),
                    'price_ttc' => round($priceTtc, 2),
                    'tax_rate' => $vatRate * 100,
                    'stock_on_hand' => $stock,
                    'stock_reserved' => 0,
                    'workshop_reserved' => 0,
                    'stock_threshold' => 0,
                    'availability_status' => $availability->value,
                    'is_default' => true,
                    'color' => $this->color,
                    'quality' => $this->resolveQuality(),
                    'attributes' => [
                        'legacy_repair_id' => $this->id,
                        'model' => $this->model,
                    ],
                ]
            );

            return $variant->load('product');
        });
    }

    protected function computePublicPrice(): float
    {
        $pricing = config('pricing');
        $vatRate = $pricing['vat_rate'] ?? 0.0;

        $baseCost = (float) ($this->supplier_price ?? 0);
        if ($baseCost <= 0) {
            $priceTtc = max((float) ($this->price ?? 0), 0);
            $baseCost = $priceTtc / max(1 + $vatRate, 1.0);
        }

        $shippingInbound = $pricing['shipping_inbound'] ?? 0.0;
        $shippingOutbound = $pricing['shipping_outbound'] ?? 0.0;
        $packaging = $pricing['packaging'] ?? 0.0;
        $failureRate = $pricing['failure_rate'] ?? 0.0;
        $marginRate = $pricing['margin_rate'] ?? 0.0;
        $microRate = $pricing['micro_social_rate'] ?? 0.0;
        $maxMultiplier = $pricing['max_multiplier'] ?? 2.0;

        // Provision panne appliquée uniquement sur le coût produit
        $failureProvision = $failureRate * $baseCost;
        $productCostWithRisk = $baseCost + $failureProvision;

        // Marge uniquement sur le produit (hors frais logistiques)
        $margin = $productCostWithRisk * $marginRate;

        // Les frais logistiques (inbound) et packaging ne sont pas margés; outbound reste facturé via frais de port
        $nonMargedCharges = $shippingInbound + $packaging;

        // Provision micro uniquement sur la marge (pas sur le coût ni les frais)
        $marginWithSocial = $margin / max(1 - $microRate, 0.01);

        $htBeforeCharges = $productCostWithRisk + $marginWithSocial + $nonMargedCharges;

        // Plafonner le HT à un multiplicateur du coût base pour éviter des prix explosifs
        $boundedHt = min($htBeforeCharges, $baseCost * $maxMultiplier);
        $publicPrice = $boundedHt * (1 + $vatRate);

        return $this->applyPsychologicalPricing($publicPrice);
    }

    protected function applyPsychologicalPricing(float $price): float
    {
        $allowedUnits = [2, 4, 5, 6, 7, 9];
        $decade = floor($price / 10) * 10;
        $target = null;

        foreach ($allowedUnits as $unit) {
            $candidate = $decade + $unit + 0.9;
            if ($candidate + 0.0001 >= $price) {
                $target = $candidate;
                break;
            }
        }

        if ($target === null) {
            $decade += 10;
            $target = $decade + $allowedUnits[0] + 0.9;
        }

        return round($target, 2);
    }

    protected function resolveQuality(): string
    {
        $token = strtolower((string) $this->component_brand);

        return str_contains($token, 'oem') || str_contains($token, 'orig')
            ? 'origine'
            : 'standard';
    }

    public function getImageFallbackAttribute(): string
    {
        return $this->image_url ?: asset('assets/img/service1.webp');
    }
}
