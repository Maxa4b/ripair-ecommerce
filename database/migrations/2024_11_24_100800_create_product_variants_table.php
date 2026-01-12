<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->string('color')->nullable();
            $table->string('quality')->nullable();
            $table->string('revision')->nullable();
            $table->json('attributes')->nullable();
            $table->decimal('price_ht', 10, 2);
            $table->decimal('price_ttc', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->integer('stock_on_hand')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->integer('stock_threshold')->default(5);
            $table->integer('workshop_reserved')->default(0);
            $table->enum('availability_status', ['in_stock', 'low_stock', 'backorder', 'preorder', 'out_of_stock'])->default('in_stock');
            $table->unsignedTinyInteger('lead_time_days')->default(2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
