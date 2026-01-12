<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rma_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['received', 'in_review', 'accepted', 'refused', 'refunded', 'replaced'])->default('received');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->boolean('conditions_confirmed')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('rma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rma_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('evaluation_status', ['pending', 'accepted', 'refused', 'repaired'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rma_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rma_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('type')->default('photo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rma_attachments');
        Schema::dropIfExists('rma_items');
        Schema::dropIfExists('rma_requests');
    }
};
