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
        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('handle')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('navigation_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('footer_enabled')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'position']);
        });

        $now = now();

        foreach ([
            ['handle' => 'main', 'name' => 'Main'],
            ['handle' => 'footer', 'name' => 'Footer'],
        ] as $menu) {
            DB::table('navigation_menus')->insert([
                'uuid' => (string) Str::uuid(),
                'handle' => $menu['handle'],
                'name' => $menu['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_items');
        Schema::dropIfExists('navigation_menus');
    }
};
