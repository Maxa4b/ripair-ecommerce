<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['home', 'relay', 'workshop_pickup'])->default('home');
            $table->boolean('supports_tracking')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('min_delay_days')->default(2);
            $table->unsignedTinyInteger('max_delay_days')->default(5);
            $table->decimal('base_price', 8, 2)->default(0);
            $table->decimal('price_per_kg', 8, 2)->default(0);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->string('zone')->default('FR');
            $table->decimal('min_weight', 8, 3)->default(0);
            $table->decimal('max_weight', 8, 3)->nullable();
            $table->decimal('min_total', 10, 2)->default(0);
            $table->decimal('max_total', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_methods');
    }
};
