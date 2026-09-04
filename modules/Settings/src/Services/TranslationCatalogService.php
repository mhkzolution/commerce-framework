<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Illuminate\Support\Arr;
use RuntimeException;

final class TranslationCatalogService
{
    /**
     * @return list<string>
     */
    public function availableLocales(): array
    {
        $locales = array_values(array_unique(array_merge(
            array_keys(config('admin.locale.available', [])),
            ['th', 'en'],
        )));

        sort($locales);

        return $locales;
    }

    /**
     * @return list<array{namespace: string, file: string, locale: string, label: string, path: string}>
     */
    public function files(?string $locale = null): array
    {
        $files = [];

        foreach ($this->translationRoots() as $root) {
            $langRoot = $root['path'].'/resources/lang';

            if (! is_dir($langRoot)) {
                continue;
            }

            foreach ($this->availableLocales() as $availableLocale) {
                if ($locale !== null && $locale !== $availableLocale) {
                    continue;
                }

                $localeDir = $langRoot.'/'.$availableLocale;

                if (! is_dir($localeDir)) {
                    continue;
                }

                foreach (glob($localeDir.'/*.php') ?: [] as $filePath) {
                    $file = basename($filePath, '.php');
                    $namespace = $root['namespace'];

                    $files[] = [
                        'namespace' => $namespace,
                        'file' => $file,
                        'locale' => $availableLocale,
                        'label' => $namespace.'::'.$file,
                        'path' => $filePath,
                    ];
                }
            }
        }

        usort($files, static function (array $left, array $right): int {
            return [$left['locale'], $left['namespace'], $left['file']]
                <=> [$right['locale'], $right['namespace'], $right['file']];
        });

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public function load(string $namespace, string $file, string $locale): array
    {
        $path = $this->resolvePath($namespace, $file, $locale);
        $translations = require $path;

        if (! is_array($translations)) {
            throw new RuntimeException("Translation file [{$path}] must return an array.");
        }

        return $this->flatten($translations);
    }

    /**
     * @param  array<string, string>  $translations
     */
    public function save(string $namespace, string $file, string $locale, array $translations): void
    {
        $path = $this->resolvePath($namespace, $file, $locale);
        $nested = $this->unflatten($translations);

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->exportArray($nested).";\n";

        file_put_contents($path, $content);
    }

    public function resolvePath(string $namespace, string $file, string $locale): string
    {
        foreach ($this->files($locale) as $entry) {
            if ($entry['namespace'] === $namespace && $entry['file'] === $file) {
                return $entry['path'];
            }
        }

        throw new RuntimeException("Translation file [{$namespace}::{$file} @ {$locale}] not found.");
    }

    /**
     * @return list<array{path: string, namespace: string}>
     */
    private function translationRoots(): array
    {
        $roots = [];

        $modulesPath = base_path('modules');

        foreach (scandir($modulesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $modulePath = $modulesPath.'/'.$entry;
            $manifestFile = $modulePath.'/module.json';

            if (! is_dir($modulePath) || ! is_file($manifestFile)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestFile), true);
            $namespace = is_array($manifest) ? (string) ($manifest['alias'] ?? strtolower($entry)) : strtolower($entry);

            $roots[] = [
                'path' => $modulePath,
                'namespace' => $namespace,
            ];
        }

        if (is_dir(base_path('resources/lang'))) {
            $roots[] = [
                'path' => base_path('resources'),
                'namespace' => 'app',
            ];
        }

        return $roots;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    private function flatten(array $translations, string $prefix = ''): array
    {
        $flat = [];

        foreach ($translations as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $fullKey);

                continue;
            }

            $flat[$fullKey] = is_string($value) ? $value : (string) $value;
        }

        return $flat;
    }

    /**
     * @param  array<string, string>  $translations
     * @return array<string, mixed>
     */
    private function unflatten(array $translations): array
    {
        $nested = [];

        foreach ($translations as $key => $value) {
            $trimmed = trim($value);
            Arr::set($nested, $key, $trimmed);
        }

        return $nested;
    }

    /**
     * @param  array<string, mixed>  $array
     */
    private function exportArray(array $array, int $depth = 1): string
    {
        if ($array === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $lines = ["\n"];

        foreach ($array as $key => $value) {
            $exportedKey = is_int($key) ? $key : var_export($key, true);

            if (is_array($value)) {
                $exportedValue = $this->exportArray($value, $depth + 1);
            } else {
                $exportedValue = var_export(is_string($value) ? $value : (string) $value, true);
            }

            $lines[] = $indent.$exportedKey.' => '.$exportedValue.",\n";
        }

        $lines[] = str_repeat('    ', $depth - 1).']';

        return '['.implode('', $lines);
    }
}
