<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wishlists')) {
            return;
        }

        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
