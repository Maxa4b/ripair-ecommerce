<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('internal_reference')->unique();
            $table->string('supplier_reference')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('compatibility_notes')->nullable();
            $table->enum('quality', ['origine', 'premium', 'standard', 'reconditionne'])->default('standard');
            $table->unsignedTinyInteger('warranty_months')->default(6);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('shipping_weight', 8, 3)->default(0);
            $table->unsignedTinyInteger('lead_time_days')->default(2);
            $table->boolean('is_published')->default(true);
            $table->boolean('is_best_seller')->default(false);
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
