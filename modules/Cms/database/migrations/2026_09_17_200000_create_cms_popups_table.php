<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_popups', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('headline')->nullable();
            $table->text('subheadline')->nullable();
            $table->uuid('image_media_uuid')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('button_target', 16)->default('self');
            $table->string('popup_type', 20)->default('image');
            $table->unsignedInteger('show_delay')->default(0);
            $table->unsignedInteger('auto_close')->nullable();
            $table->boolean('closable')->default(true);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('timezone', 64)->default('Asia/Bangkok');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index(['tenant_id', 'status', 'is_active', 'priority']);
            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_popups');
    }
};
