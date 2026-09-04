<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wishlist_items')) {
            return;
        }

        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wishlist_id')->constrained('wishlists')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->timestamps();

            $table->unique(['wishlist_id', 'product_id']);
            $table->index(['wishlist_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
