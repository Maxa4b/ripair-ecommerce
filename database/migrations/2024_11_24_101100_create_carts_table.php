<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token')->unique();
            $table->enum('status', ['open', 'converted', 'abandoned'])->default('open');
            $table->string('currency', 3)->default('EUR');
            $table->decimal('subtotal_ht', 12, 2)->default(0);
            $table->decimal('subtotal_ttc', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->json('variant_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_ht', 10, 2);
            $table->decimal('unit_price_ttc', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
