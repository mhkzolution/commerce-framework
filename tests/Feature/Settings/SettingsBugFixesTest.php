<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Models\Setting;
use Commerce\Settings\Models\SettingGroup;
use Commerce\Settings\Services\SettingQueryService;
use Commerce\Settings\Services\TranslationCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsBugFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_translation_search_save_preserves_unfiltered_keys(): void
    {
        $catalog = app(TranslationCatalogService::class);
        $original = $catalog->load('product', 'workspace', 'th');
        $firstKey = array_key_first($original);

        $this->assertNotNull($firstKey);
        $this->assertArrayHasKey('new_product', $original);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.translations.update', ['namespace' => 'product', 'file' => 'workspace']), [
                'locale' => 'th',
                'translations' => [
                    'new_product' => 'ค่าที่แก้ผ่านการค้นหา',
                ],
            ])
            ->assertRedirect();

        $after = $catalog->load('product', 'workspace', 'th');
        $this->assertSame('ค่าที่แก้ผ่านการค้นหา', $after['new_product']);
        $this->assertSame($original[$firstKey], $after[$firstKey]);

        $catalog->save('product', 'workspace', 'th', $original);
    }

    public function test_generic_settings_index_excludes_site_and_theme_groups(): void
    {
        $structure = app(SettingQueryService::class)->getAdminStructure();
        $codes = array_map(static fn (array $section): string => $section['group']->code, $structure);

        $this->assertNotContains('site', $codes);
        $this->assertNotContains('theme', $codes);
        $this->assertContains('store', $codes);
    }

    public function test_generic_settings_update_validates_per_key_rules(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.update', ['group' => 'store']), [
                'settings' => [
                    'name' => 'Commerce Store',
                    'currency' => 'THB',
                    'timezone' => 'Asia/Bangkok',
                    'locale' => 'th',
                    'email' => 'not-an-email',
                ],
            ])
            ->assertSessionHasErrors('settings.email');
    }

    public function test_public_settings_api_returns_is_public_keys(): void
    {
        $response = $this->getJson(route('api.v1.settings.public'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'store.name',
                    'store.currency',
                    'store.locale',
                    'site',
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame('Commerce Store', $data['store.name']);
        $this->assertIsArray($data['site']);
        $this->assertArrayHasKey('name', $data['site']);
    }

    public function test_get_public_settings_includes_theme_colors_when_marked_public(): void
    {
        $public = app(SettingQueryService::class)->getPublicSettings();

        $this->assertArrayHasKey('theme.primary', $public);
        $this->assertArrayHasKey('site.name', $public);
    }

    public function test_store_settings_update_persists_valid_email(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.update', ['group' => 'store']), [
                'settings' => [
                    'name' => 'Commerce Store',
                    'currency' => 'THB',
                    'timezone' => 'Asia/Bangkok',
                    'locale' => 'th',
                    'email' => 'store@example.com',
                ],
            ])
            ->assertRedirect(route('admin.settings.index'));

        $settings = app(SettingQueryServiceInterface::class);
        $this->assertSame('store@example.com', $settings->get('store.email'));
    }

    public function test_settings_resolve_per_tenant_when_tenancy_enabled(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $group = SettingGroup::query()->where('code', 'store')->firstOrFail();
        $template = Setting::query()
            ->withoutGlobalScopes()
            ->where('group_id', $group->id)
            ->where('key', 'name')
            ->whereNull('tenant_id')
            ->firstOrFail();

        Setting::query()->create([
            'group_id' => $group->id,
            'tenant_id' => $tenant->id,
            'key' => 'name',
            'type' => $template->type,
            'value' => 'Tenant Store',
            'default_value' => $template->default_value,
            'validation' => $template->validation,
            'is_public' => $template->is_public,
            'meta' => $template->meta,
        ]);

        $context = app(TenantContext::class);
        $query = app(SettingQueryService::class);

        $context->set($tenant);
        $query->clearCache('store.name');
        $this->assertSame('Tenant Store', $query->get('store.name'));

        $context->clear();
        $query->clearCache('store.name');
        $this->assertSame('Commerce Store', $query->get('store.name'));
    }
}
