<?php

declare(strict_types=1);

if (! function_exists('commerce_channel')) {
    function commerce_channel(): ?\Commerce\Contracts\Channel\ChannelContextInterface
    {
        return app()->bound(\Commerce\Contracts\Channel\ChannelContextInterface::class)
            ? app(\Commerce\Contracts\Channel\ChannelContextInterface::class)
            : null;
    }
}
