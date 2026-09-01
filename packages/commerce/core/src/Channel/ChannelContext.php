<?php

declare(strict_types=1);

namespace Commerce\Core\Channel;

use Commerce\Contracts\Channel\ChannelContextInterface;

final class ChannelContext implements ChannelContextInterface
{
    public function __construct(
        private string $channel = 'web',
        private string $locale = 'th',
        private string $currency = 'THB',
    ) {}

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
