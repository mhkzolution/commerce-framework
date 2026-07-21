<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->uuid('purchasable_uuid');
            $table->integer('on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'purchasable_uuid']);
            $table->index(['tenant_id', 'purchasable_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
