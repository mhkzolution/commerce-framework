<?php

declare(strict_types=1);

namespace Commerce\Contracts\Tax;

interface TaxCalculatorInterface
{
    /**
     * @return list<\Commerce\Contracts\Tax\TaxLineInterface>
     */
    public function calculate(\Commerce\Contracts\Tax\TaxContextInterface $context): array;
}
