<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('manual');
            $table->json('rules')->nullable();
            $table->text('description')->nullable();
            $table->uuid('cover_media_uuid')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
