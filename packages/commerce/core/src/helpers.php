<?php

declare(strict_types=1);

use Commerce\Contracts\Channel\ChannelContextInterface;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Modules\ModuleService;

if (! function_exists('commerce_channel')) {
    function commerce_channel(): ?ChannelContextInterface
    {
        return app()->bound(ChannelContextInterface::class)
            ? app(ChannelContextInterface::class)
            : null;
    }
}

if (! function_exists('module_active')) {
    function module_active(string $code): bool
    {
        return ModuleService::isActive($code);
    }
}

if (! function_exists('module_hidden')) {
    function module_hidden(string $code): bool
    {
        return ModuleService::isHidden($code);
    }
}

if (! function_exists('module_disabled')) {
    function module_disabled(string $code): bool
    {
        return ModuleService::isDisabled($code);
    }
}

if (! function_exists('feature_enabled')) {
    function feature_enabled(string $code): bool
    {
        return FeatureService::enabled($code);
    }
}

if (! function_exists('feature')) {
    function feature(string $code): bool
    {
        return feature_enabled($code);
    }
}
