<?php

namespace Database\Seeders;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Category;
use App\Models\Catalog\DeviceModel;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductImage;
use App\Models\Catalog\ProductType;
use App\Models\Catalog\ProductVariant;
use App\Models\Logistics\ShippingMethod;
use App\Models\Logistics\ShippingRate;
use App\Models\Logistics\Transporter;
use App\Models\Support\ProPricingRule;
use App\Models\Support\PromoCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'Smartphones',
            'Tablettes',
            'Consoles',
            'PC portables',
            'Accessoires & Outils',
        ])->map(fn ($name) => Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'display_order' => 1, 'is_active' => true],
        ));

        $brands = collect(['Apple', 'Samsung', 'Sony', 'Nintendo'])->map(fn ($name) => Brand::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => "{$name} - pièces détachées"],
        ));

        $types = collect(['Écran', 'Batterie', 'Connecteur', 'Caméra'])->map(fn ($name) => ProductType::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        ));

        $iphone = DeviceModel::firstOrCreate([
            'slug' => 'iphone-13',
        ], [
            'name' => 'iPhone 13',
            'brand_id' => $brands->firstWhere('slug', 'apple')->id,
            'category_id' => $categories->firstWhere('slug', 'smartphones')->id,
        ]);

        $galaxy = DeviceModel::firstOrCreate([
            'slug' => 'galaxy-s22',
        ], [
            'name' => 'Galaxy S22',
            'brand_id' => $brands->firstWhere('slug', 'samsung')->id,
            'category_id' => $categories->firstWhere('slug', 'smartphones')->id,
        ]);

        $product = Product::firstOrCreate([
            'slug' => 'ecran-oled-iphone-13',
        ], [
            'name' => 'Écran OLED Service Pack - iPhone 13',
            'category_id' => $categories->firstWhere('slug', 'smartphones')->id,
            'brand_id' => $brands->firstWhere('slug', 'apple')->id,
            'product_type_id' => $types->firstWhere('slug', 'ecran')->id ?? null,
            'internal_reference' => 'RP-SP-IP13-OLED',
            'quality' => 'premium',
            'short_description' => 'Écran OLED Service Pack avec châssis pré-monté.',
            'description' => 'Écran OLED Premium compatible iPhone 13 avec kit joints.',
        ]);

        $variant = ProductVariant::firstOrCreate([
            'product_id' => $product->id,
            'sku' => 'IP13-OLED-NOIR',
        ], [
            'price_ht' => 120,
            'price_ttc' => 144,
            'tax_rate' => 20,
            'stock_on_hand' => 25,
            'availability_status' => 'in_stock',
            'is_default' => true,
        ]);

        ProductImage::firstOrCreate([
            'product_id' => $product->id,
            'path' => 'images/products/iphone13-oled.jpg',
        ], ['is_primary' => true]);

        $product->deviceModels()->sync([$iphone->id]);

        PromoCode::firstOrCreate(['code' => 'BIENVENUE10'], [
            'description' => 'Réduction bienvenue',
            'discount_type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        ProPricingRule::firstOrCreate(['name' => 'Remise PRO écrans'], [
            'category_id' => $categories->firstWhere('slug', 'smartphones')->id,
            'discount_type' => 'percentage',
            'discount_value' => 8,
            'is_active' => true,
        ]);

        $transporter = Transporter::firstOrCreate(['code' => 'chronopost'], [
            'name' => 'Chronopost',
            'supports_pickup_points' => true,
            'is_active' => true,
        ]);

        $method = ShippingMethod::firstOrCreate(['code' => 'chrono13'], [
            'name' => 'Chronopost 13H',
            'transporter_id' => $transporter->id,
            'type' => 'home',
            'base_price' => 9.90,
        ]);

        ShippingRate::firstOrCreate([
            'shipping_method_id' => $method->id,
            'zone' => 'FR',
            'min_weight' => 0,
            'max_weight' => 5,
            'is_default' => true,
        ], [
            'price' => 9.90,
        ]);

        $method->rates()->firstOrCreate([
            'zone' => 'FR',
            'min_weight' => 5,
            'max_weight' => 30,
        ], ['price' => 19.90]);
    }
}
