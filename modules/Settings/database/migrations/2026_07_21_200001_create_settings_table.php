<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('group_id')->constrained('setting_groups')->cascadeOnDelete();
            $table->string('key', 150);
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->text('default_value')->nullable();
            $table->json('validation')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'group_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
