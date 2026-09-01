<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->uuid('variant_uuid')->index();
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedBigInteger('price');
            $table->timestamps();

            $table->unique(['variant_uuid', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_price_tiers');
    }
};
