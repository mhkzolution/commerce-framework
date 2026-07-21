<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_line_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->uuid('purchasable_uuid');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price');
            $table->bigInteger('line_total');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['purchasable_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_line_items');
    }
};
