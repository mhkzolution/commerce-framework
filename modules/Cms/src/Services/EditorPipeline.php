<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

final class EditorPipeline
{
    private const ALLOWED_TAGS = '<p><br><h1><h2><h3><h4><h5><h6><strong><b><em><i><a><img><ul><ol><li><blockquote><pre><code><table><thead><tbody><tr><th><td><hr>';

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        if (trim($html) === '') {
            return '';
        }

        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = $this->sanitizeAnchors($html);
        $html = $this->sanitizeImages($html);

        return $html;
    }

    private function sanitizeAnchors(string $html): string
    {
        return preg_replace_callback('/<a\s+([^>]*?)>/i', function (array $match): string {
            $attrs = $this->allowedAttributes($match[1], ['href', 'title', 'target', 'rel']);
            $href = $attrs['href'] ?? '';

            if ($href === '' || preg_match('/^\s*javascript:/i', $href) === 1) {
                $attrs['href'] = '#';
            }

            if (($attrs['target'] ?? '') === '_blank') {
                $attrs['rel'] = trim(($attrs['rel'] ?? '').' noopener noreferrer');
            }

            return '<a '.$this->attributeString($attrs).'>';
        }, $html) ?? $html;
    }

    private function sanitizeImages(string $html): string
    {
        return preg_replace_callback('/<img\s+([^>]*?)>/i', function (array $match): string {
            $attrs = $this->allowedAttributes($match[1], ['src', 'alt', 'title', 'width', 'height']);
            $src = $attrs['src'] ?? '';

            if ($src === '' || preg_match('/^\s*javascript:/i', $src) === 1) {
                return '';
            }

            $attrs['alt'] ??= '';

            return '<img '.$this->attributeString($attrs).'>';
        }, $html) ?? $html;
    }

    /**
     * @param  list<string>  $allowed
     * @return array<string, string>
     */
    private function allowedAttributes(string $raw, array $allowed): array
    {
        $attrs = [];

        if (preg_match_all('/([a-zA-Z_:][\w:.-]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/', $raw, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            if (! in_array($name, $allowed, true)) {
                continue;
            }

            $attrs[$name] = html_entity_decode($match[3] !== '' ? $match[3] : ($match[4] !== '' ? $match[4] : $match[5]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $attrs;
    }

    /**
     * @param  array<string, string>  $attrs
     */
    private function attributeString(array $attrs): string
    {
        $parts = [];

        foreach ($attrs as $name => $value) {
            $parts[] = $name.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8').'"';
        }

        return implode(' ', $parts);
    }
}
