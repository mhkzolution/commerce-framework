<?php

declare(strict_types=1);

namespace Commerce\Contracts\Channel;

interface ChannelContextInterface
{
    public function getChannel(): string;

    public function getLocale(): string;

    public function getCurrency(): string;
}
