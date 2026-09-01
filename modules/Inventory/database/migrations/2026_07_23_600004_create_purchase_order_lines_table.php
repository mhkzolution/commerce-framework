<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->uuid('purchasable_uuid');
            $table->string('sku')->nullable();
            $table->unsignedInteger('quantity_ordered');
            $table->unsignedInteger('quantity_received')->default(0);
            $table->timestamps();

            $table->index('purchasable_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
