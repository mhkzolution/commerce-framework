<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('paper_size', 32)->default('a4');
            $table->unsignedSmallInteger('rows')->default(10);
            $table->unsignedSmallInteger('columns')->default(4);
            $table->decimal('margin_top', 8, 2)->default(10);
            $table->decimal('margin_right', 8, 2)->default(10);
            $table->decimal('margin_bottom', 8, 2)->default(10);
            $table->decimal('margin_left', 8, 2)->default(10);
            $table->decimal('spacing_horizontal', 8, 2)->default(2);
            $table->decimal('spacing_vertical', 8, 2)->default(2);
            $table->decimal('label_width', 8, 2)->default(48.5);
            $table->decimal('label_height', 8, 2)->default(25.4);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'is_favorite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_templates');
    }
};
