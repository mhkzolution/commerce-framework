<?php

declare(strict_types=1);

namespace Commerce\Core\Barcode;

use Commerce\Contracts\Barcode\BarcodeValueGeneratorInterface;
use Commerce\Contracts\Barcode\BarcodeValueGeneratorStrategyInterface;
use InvalidArgumentException;

final class BarcodeValueGenerator implements BarcodeValueGeneratorInterface
{
    /** @var array<string, BarcodeValueGeneratorStrategyInterface> */
    private array $strategies;

    /**
     * @param  iterable<BarcodeValueGeneratorStrategyInterface>  $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = [];

        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->name()] = $strategy;
        }
    }

    public function generate(string $strategy, array $options = []): string
    {
        if (! isset($this->strategies[$strategy])) {
            throw new InvalidArgumentException("Unknown barcode generation strategy [{$strategy}].");
        }

        $defaults = $this->strategyDefaults($strategy);
        $merged = array_merge($defaults, $options);

        return $this->strategies[$strategy]->generate($merged);
    }

    public function strategies(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * @return array<string, mixed>
     */
    private function strategyDefaults(string $strategy): array
    {
        if (! function_exists('app') || ! app()->bound('config')) {
            return [];
        }

        return config("barcode.generation.strategies.{$strategy}", []);
    }
}
