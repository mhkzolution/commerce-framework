<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collections', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->primary(['product_id', 'collection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collections');
    }
};
