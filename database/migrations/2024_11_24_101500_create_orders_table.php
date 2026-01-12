<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->enum('status', ['pending_payment', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled'])->default('pending_payment');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('fulfillment_status', ['unfulfilled', 'partial', 'fulfilled'])->default('unfulfilled');
            $table->enum('delivery_type', ['shipping', 'relay', 'workshop_pickup'])->default('shipping');
            $table->string('currency', 3)->default('EUR');
            $table->decimal('subtotal_ht', 12, 2)->default(0);
            $table->decimal('subtotal_ttc', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->decimal('total_ttc', 12, 2)->default(0);
            $table->json('billing_address');
            $table->json('shipping_address');
            $table->string('tracking_number')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('withdrawal_slot')->nullable();
            $table->string('workshop_reference')->nullable();
            $table->boolean('is_pro')->default(false);
            $table->string('pro_po_number')->nullable();
            $table->date('due_at')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->json('variant_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_ht', 10, 2);
            $table->decimal('unit_price_ttc', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('total_ht', 12, 2);
            $table->decimal('total_ttc', 12, 2);
            $table->unsignedTinyInteger('warranty_months')->default(6);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('from_status', ['pending_payment', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled'])->nullable();
            $table->enum('to_status', ['pending_payment', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled']);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
