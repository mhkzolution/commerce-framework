<?php

declare(strict_types=1);

namespace Plugins\HelloWorld\Contracts;

interface GreetingServiceInterface
{
    public function greet(string $name): string;
}
