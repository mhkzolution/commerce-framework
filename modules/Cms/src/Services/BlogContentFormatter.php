<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Illuminate\Support\Str;

final class BlogContentFormatter
{
    /**
     * @return array{html: string, toc: list<array{id: string, label: string, level: int}>}
     */
    public function format(?string $content): array
    {
        if ($content === null || trim($content) === '') {
            return ['html' => '', 'toc' => []];
        }

        if (str_contains($content, '<h2') || str_contains($content, '<h3')) {
            return $this->formatHtml($content);
        }

        return $this->formatMarkdownHeadings($content);
    }

    public function readingTimeMinutes(?string $content, int $wordsPerMinute = 200): int
    {
        if ($content === null || trim($content) === '') {
            return 1;
        }

        $words = str_word_count(strip_tags($content));

        return max(1, (int) ceil($words / $wordsPerMinute));
    }

    /**
     * @return array{html: string, toc: list<array{id: string, label: string, level: int}>}
     */
    private function formatMarkdownHeadings(string $content): array
    {
        $toc = [];
        $index = 0;
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $output = [];

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $label = trim($matches[2]);
                $id = 'section-'.(++$index).'-'.Str::slug($label);
                $toc[] = ['id' => $id, 'label' => $label, 'level' => $level];
                $tag = $level === 2 ? 'h2' : 'h3';
                $output[] = "<{$tag} id=\"{$id}\">".e($label)."</{$tag}>";
            } else {
                $output[] = e($line);
            }
        }

        return ['html' => implode('<br>', $output), 'toc' => $toc];
    }

    /**
     * @return array{html: string, toc: list<array{id: string, label: string, level: int}>}
     */
    private function formatHtml(string $content): array
    {
        $toc = [];
        $index = 0;

        $html = preg_replace_callback(
            '/<h([2-3])([^>]*)>(.*?)<\/h\1>/is',
            function (array $matches) use (&$toc, &$index): string {
                $level = (int) $matches[1];
                $label = trim(strip_tags($matches[3]));
                $id = 'section-'.(++$index).'-'.Str::slug($label);
                $toc[] = ['id' => $id, 'label' => $label, 'level' => $level];

                return "<h{$level} id=\"{$id}\">{$matches[3]}</h{$level}>";
            },
            $content,
        ) ?? $content;

        return ['html' => $html, 'toc' => $toc];
    }
}
