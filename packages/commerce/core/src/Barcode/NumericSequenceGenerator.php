<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode;

use InvalidArgumentException;

final class NumericSequenceGenerator
{
    private const int MAX_COUNT = 10000;

    /**
     * @return list<string>
     */
    public function generate(string $start, int $count): array
    {
        $start = trim($start);

        if ($start === '' || ! preg_match('/^\d+$/', $start)) {
            throw new InvalidArgumentException('Start value must be a numeric barcode.');
        }

        if ($count < 1 || $count > self::MAX_COUNT) {
            throw new InvalidArgumentException('Count must be between 1 and '.self::MAX_COUNT.'.');
        }

        $values = [];
        $current = $start;
        $padLength = strlen($start);

        for ($i = 0; $i < $count; $i++) {
            $values[] = $current;
            $current = $this->increment($current, $padLength);
        }

        return $values;
    }

    private function increment(string $value, int $padLength): string
    {
        $next = (string) ((int) $value + 1);
        $length = max($padLength, strlen($next));

        return str_pad($next, $length, '0', STR_PAD_LEFT);
    }
}
