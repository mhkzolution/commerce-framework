<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_hero_banners', function (Blueprint $table): void {
            $table->string('type', 20)->default('image')->after('tenant_id');
            $table->uuid('video_media_uuid')->nullable()->after('mobile_image_media_uuid');
            $table->uuid('mobile_video_media_uuid')->nullable()->after('video_media_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('cms_hero_banners', function (Blueprint $table): void {
            $table->dropColumn(['type', 'video_media_uuid', 'mobile_video_media_uuid']);
        });
    }
};
