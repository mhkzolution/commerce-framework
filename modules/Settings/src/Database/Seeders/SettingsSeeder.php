<?php

declare(strict_types=1);

namespace Commerce\Settings\Database\Seeders;

use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Illuminate\Database\Seeder;

final class SettingsSeeder extends Seeder
{
    public function run(SettingRegistryServiceInterface $registry): void
    {
        $this->seedConfigDefaults($registry);
        $this->seedModuleManifestSettings($registry);
    }

    private function seedConfigDefaults(SettingRegistryServiceInterface $registry): void
    {
        $definitions = [
            'store.name' => ['type' => 'string', 'label' => 'Store Name', 'group' => 'store', 'default' => 'Commerce Store', 'is_public' => true],
            'store.currency' => ['type' => 'string', 'label' => 'Currency', 'group' => 'store', 'default' => 'THB', 'is_public' => true],
            'store.timezone' => ['type' => 'string', 'label' => 'Timezone', 'group' => 'store', 'default' => 'Asia/Bangkok'],
            'store.locale' => ['type' => 'string', 'label' => 'Locale', 'group' => 'store', 'default' => 'en', 'is_public' => true],
            'store.email' => ['type' => 'string', 'label' => 'Store Email', 'group' => 'store', 'default' => 'superadmin@example.com'],
            'store.logo_media_uuid' => ['type' => 'string', 'label' => 'Store Logo', 'group' => 'store', 'default' => null, 'is_public' => true],
            'store.description' => ['type' => 'string', 'label' => 'Store Description', 'group' => 'store', 'default' => null, 'is_public' => true],
            'social.facebook' => ['type' => 'string', 'label' => 'Facebook', 'group' => 'social', 'default' => null, 'is_public' => true],
            'social.instagram' => ['type' => 'string', 'label' => 'Instagram', 'group' => 'social', 'default' => null, 'is_public' => true],
            'social.tiktok' => ['type' => 'string', 'label' => 'TikTok', 'group' => 'social', 'default' => null, 'is_public' => true],
            'social.line' => ['type' => 'string', 'label' => 'LINE', 'group' => 'social', 'default' => null, 'is_public' => true],
        ];

        foreach ($definitions as $key => $schema) {
            $registry->register($key, array_merge($schema, ['module' => 'settings']));
        }
    }

    private function seedModuleManifestSettings(SettingRegistryServiceInterface $registry): void
    {
        $path = base_path('modules');

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestFile = $path.'/'.$entry.'/module.json';

            if (! is_file($manifestFile)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
            $module = $manifest['alias'] ?? strtolower($entry);

            foreach ($manifest['settings'] ?? [] as $setting) {
                if (! is_array($setting) || empty($setting['key'])) {
                    continue;
                }

                $registry->register($setting['key'], [
                    'type' => $setting['type'] ?? 'string',
                    'label' => $setting['label'] ?? $setting['key'],
                    'group' => $setting['group'] ?? explode('.', $setting['key'])[0],
                    'default' => $setting['default'] ?? null,
                    'is_public' => $setting['is_public'] ?? false,
                    'validation' => $setting['validation'] ?? [],
                    'module' => $module,
                ]);
            }
        }
    }
}
