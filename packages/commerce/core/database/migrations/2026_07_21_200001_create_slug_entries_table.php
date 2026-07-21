<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 200);
            $table->string('entity_type', 100);
            $table->string('entity_uuid', 36);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'slug']);
            $table->index(['entity_type', 'entity_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_entries');
    }
};
