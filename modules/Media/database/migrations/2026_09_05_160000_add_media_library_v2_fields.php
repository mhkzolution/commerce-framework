<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('caption')->nullable()->after('alt_text');
            $table->text('description')->nullable()->after('caption');
        });

        Schema::create('media_tags', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'name']);
        });

        Schema::create('media_tag_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['media_id', 'media_tag_id']);
        });

        foreach (['Products', 'Homepage', 'Blog', 'Banners', 'Seasonal'] as $name) {
            DB::table('media_tags')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_tag_media');
        Schema::dropIfExists('media_tags');

        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn(['caption', 'description']);
        });
    }
};
