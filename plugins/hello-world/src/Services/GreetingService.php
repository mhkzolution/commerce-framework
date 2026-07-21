<?php

declare(strict_types=1);

namespace Plugins\HelloWorld\Services;

use Plugins\HelloWorld\Contracts\GreetingServiceInterface;

final class GreetingService implements GreetingServiceInterface
{
    public function greet(string $name): string
    {
        return "Hello from plugin, {$name}!";
    }
}
