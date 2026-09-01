<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Commerce\PluginManager\PluginStateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class PluginCliTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_list_command_shows_discovered_plugins(): void
    {
        Artisan::call('commerce:plugin:list');

        $output = Artisan::output();
        $this->assertStringContainsString('hello-world', $output);
        $this->assertStringContainsString('product-badge', $output);
    }

    public function test_plugin_enable_and_disable_persist_state(): void
    {
        $store = app(PluginStateStore::class);

        Artisan::call('commerce:plugin:disable', ['alias' => 'hello-world']);
        $this->assertFalse($store->all()['hello-world'] ?? true);

        Artisan::call('commerce:plugin:enable', ['alias' => 'hello-world']);
        $this->assertTrue($store->all()['hello-world'] ?? false);
    }

    public function test_plugin_make_scaffolds_plugin_directory(): void
    {
        $name = 'Widget'.random_int(1000, 9999);

        Artisan::call('commerce:plugin:make', ['name' => $name]);

        $this->assertDirectoryExists(base_path('plugins/'.str()->kebab($name)));
    }
}
