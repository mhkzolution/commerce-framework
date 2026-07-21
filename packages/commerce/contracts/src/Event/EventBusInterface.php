<?php

declare(strict_types=1);

namespace Commerce\Contracts\Event;

interface EventBusInterface
{
    public function dispatch(object $event): void;

    public function dispatchAsync(object $event): void;

    /**
     * @param  callable|class-string  $listener
     */
    public function listen(string $event, callable|string $listener, bool $async = false): void;
}
