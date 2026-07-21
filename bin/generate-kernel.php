<?php

declare(strict_types=1);

/**
 * One-time kernel scaffold generator. Run: php bin/generate-kernel.php
 * Generates package and module structure files (no business logic).
 */

$root = dirname(__DIR__);

$files = [];

$write = static function (string $path, string $content) use (&$files, $root): void {
    $files[$root . '/' . $path] = $content;
};

// ---------------------------------------------------------------------------
// Package composer.json files
// ---------------------------------------------------------------------------

$packageComposer = static function (string $name, string $description, array $requires = ['commerce/contracts' => '*']): string {
    $req = array_merge(['php' => '^8.4'], $requires);
    $reqJson = json_encode($req, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $reqJson = preg_replace('/^/m', '        ', trim($reqJson));

    return <<<JSON
{
    "name": "commerce/{$name}",
    "description": "{$description}",
    "type": "library",
    "license": "MIT",
    "require": {$reqJson},
    "autoload": {
        "psr-4": {
            "Commerce\\\\" . ucfirst(str_replace('-', '', ucwords($name, '-'))) . "\\\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": []
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}

JSON;
};

// Fix namespace mapping manually per package
$write('packages/commerce/contracts/composer.json', <<<'JSON'
{
    "name": "commerce/contracts",
    "description": "Shared contracts and interfaces for Commerce Framework",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Contracts\\": "src/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('packages/commerce/support/composer.json', <<<'JSON'
{
    "name": "commerce/support",
    "description": "Shared support utilities for Commerce Framework",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/contracts": "*"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Support\\": "src/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('packages/commerce/core/composer.json', <<<'JSON'
{
    "name": "commerce/core",
    "description": "Commerce Framework kernel — base classes, value objects, event bus",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/contracts": "*",
        "commerce/support": "*",
        "illuminate/support": "^13.0",
        "illuminate/events": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Core\\": "src/"
        },
        "files": [
            "src/helpers.php"
        ]
    },
    "extra": {
        "laravel": {
            "providers": [
                "Commerce\\Core\\CommerceServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('packages/commerce/module-manager/composer.json', <<<'JSON'
{
    "name": "commerce/module-manager",
    "description": "Module discovery, lifecycle, and dependency resolution",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/core": "*",
        "commerce/contracts": "*",
        "illuminate/support": "^13.0",
        "illuminate/filesystem": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\ModuleManager\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Commerce\\ModuleManager\\ModuleManagerServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('packages/commerce/plugin-manager/composer.json', <<<'JSON'
{
    "name": "commerce/plugin-manager",
    "description": "Plugin discovery, hooks, and binding overrides",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/core": "*",
        "commerce/contracts": "*",
        "illuminate/support": "^13.0",
        "illuminate/filesystem": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\PluginManager\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Commerce\\PluginManager\\PluginManagerServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('packages/commerce/api/composer.json', <<<'JSON'
{
    "name": "commerce/api",
    "description": "API versioning, response envelope, and middleware",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/core": "*",
        "commerce/contracts": "*",
        "illuminate/support": "^13.0",
        "illuminate/http": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Api\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Commerce\\Api\\ApiServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('modules/Iam/composer.json', <<<'JSON'
{
    "name": "commerce/iam",
    "description": "Identity & Access Management module",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/core": "*",
        "commerce/contracts": "*",
        "commerce/module-manager": "*",
        "illuminate/support": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Iam\\": "src/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

$write('modules/Settings/composer.json', <<<'JSON'
{
    "name": "commerce/settings",
    "description": "Settings and configuration module",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "commerce/core": "*",
        "commerce/contracts": "*",
        "commerce/module-manager": "*",
        "illuminate/support": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Commerce\\Settings\\": "src/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON);

// ---------------------------------------------------------------------------
// Contracts
// ---------------------------------------------------------------------------

$iface = static function (string $namespace, string $name, string $methods = ''): string {
    $methods = $methods !== '' ? "\n{$methods}\n" : "\n";

    return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

interface {$name}
{{$methods}}

PHP;
};

$write('packages/commerce/contracts/src/Identifiable/IdentifiableInterface.php', $iface(
    'Commerce\\Contracts\\Identifiable',
    'IdentifiableInterface',
    <<<'METHODS'
    public function getUuid(): string;

    public function getTenantId(): ?int;
METHODS
));

$write('packages/commerce/contracts/src/Purchasable/PurchasableInterface.php', $iface(
    'Commerce\\Contracts\\Purchasable',
    'PurchasableInterface',
    <<<'METHODS'
    public function getPurchasableUuid(): string;

    public function getSku(): ?string;

    public function isPurchasable(): bool;
METHODS
));

$write('packages/commerce/contracts/src/Repository/RepositoryInterface.php', $iface(
    'Commerce\\Contracts\\Repository',
    'RepositoryInterface',
    <<<'METHODS'
    public function findByUuid(string $uuid): ?object;

    public function findById(int $id): ?object;
METHODS
));

$write('packages/commerce/contracts/src/Service/ServiceInterface.php', $iface(
    'Commerce\\Contracts\\Service',
    'ServiceInterface'
));

$write('packages/commerce/contracts/src/Query/QueryServiceInterface.php', $iface(
    'Commerce\\Contracts\\Query',
    'QueryServiceInterface',
    '    // Marker interface for read-only query services.'
));

$write('packages/commerce/contracts/src/Event/DomainEventInterface.php', $iface(
    'Commerce\\Contracts\\Event',
    'DomainEventInterface',
    <<<'METHODS'
    public function getEventName(): string;

    public function getOccurredAt(): \DateTimeImmutable;

    public function getTenantId(): ?int;

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array;
METHODS
));

$write('packages/commerce/contracts/src/Event/EventBusInterface.php', $iface(
    'Commerce\\Contracts\\Event',
    'EventBusInterface',
    <<<'METHODS'
    public function dispatch(object $event): void;

    public function dispatchAsync(object $event): void;

    /**
     * @param  callable|class-string  $listener
     */
    public function listen(string $event, callable|string $listener, bool $async = false): void;
METHODS
));

$write('packages/commerce/contracts/src/Authorization/AuthorizationServiceInterface.php', $iface(
    'Commerce\\Contracts\\Authorization',
    'AuthorizationServiceInterface',
    <<<'METHODS'
    public function can(?object $user, string $permission, mixed $resource = null): bool;

    public function hasRole(?object $user, string $roleCode): bool;

    /**
     * @return list<string>
     */
    public function getPermissionsForUser(int $userId): array;
METHODS
));

$write('packages/commerce/contracts/src/Authorization/PermissionRegistryInterface.php', $iface(
    'Commerce\\Contracts\\Authorization',
    'PermissionRegistryInterface',
    <<<'METHODS'
    /**
     * @param  array{module: string, group?: string, label: string, guard?: string}  $meta
     */
    public function register(string $permission, array $meta): void;

    /**
     * @return list<array{name: string, module: string, group: ?string, label: string}>
     */
    public function all(): array;
METHODS
));

$write('packages/commerce/contracts/src/Notification/NotificationDispatcherInterface.php', $iface(
    'Commerce\\Contracts\\Notification',
    'NotificationDispatcherInterface',
    <<<'METHODS'
    /**
     * @param  array<string, mixed>  $variables
     */
    public function send(string $templateCode, object $recipient, array $variables = []): void;
METHODS
));

$write('packages/commerce/contracts/src/Settings/SettingQueryServiceInterface.php', $iface(
    'Commerce\\Contracts\\Settings',
    'SettingQueryServiceInterface',
    <<<'METHODS'
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;
METHODS
));

$write('packages/commerce/contracts/src/Settings/SettingRegistryServiceInterface.php', $iface(
    'Commerce\\Contracts\\Settings',
    'SettingRegistryServiceInterface',
    <<<'METHODS'
    /**
     * @param  array{type: string, default?: mixed, label: string, group: string, is_public?: bool}  $schema
     */
    public function register(string $key, array $schema): void;

    /**
     * @return list<array{key: string, schema: array<string, mixed>}>
     */
    public function all(): array;
METHODS
));

$write('packages/commerce/contracts/src/Media/MediaQueryServiceInterface.php', $iface(
    'Commerce\\Contracts\\Media',
    'MediaQueryServiceInterface',
    <<<'METHODS'
    public function findByUuid(string $uuid): ?object;

    public function getUrl(string $uuid, ?string $variant = null): ?string;

    /**
     * @param  list<string>  $uuids
     * @return array<string, object>
     */
    public function findByUuids(array $uuids): array;
METHODS
));

$write('packages/commerce/contracts/src/Media/MediaUploadServiceInterface.php', $iface(
    'Commerce\\Contracts\\Media',
    'MediaUploadServiceInterface',
    <<<'METHODS'
    /**
     * @param  resource|\Illuminate\Http\UploadedFile|string  $file
     */
    public function upload(mixed $file, ?string $folderUuid = null): object;
METHODS
));

$write('packages/commerce/contracts/src/Seo/SeoServiceInterface.php', $iface(
    'Commerce\\Contracts\\Seo',
    'SeoServiceInterface',
    <<<'METHODS'
    public function getForEntity(string $entityType, string $entityUuid): ?object;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setForEntity(string $entityType, string $entityUuid, array $data): void;
METHODS
));

$write('packages/commerce/contracts/src/Seo/SlugServiceInterface.php', $iface(
    'Commerce\\Contracts\\Seo',
    'SlugServiceInterface',
    <<<'METHODS'
    public function generate(string $source, string $entityType, ?string $tenantScope = null): string;

    public function isAvailable(string $slug, string $entityType, ?string $tenantScope = null): bool;
METHODS
));

$write('packages/commerce/contracts/src/Seo/UrlRedirectServiceInterface.php', $iface(
    'Commerce\\Contracts\\Seo',
    'UrlRedirectServiceInterface',
    <<<'METHODS'
    public function createRedirect(string $fromPath, string $toPath, int $type = 301): void;

    public function resolve(string $path): ?string;
METHODS
));

$write('packages/commerce/contracts/src/Pricing/PriceResolverInterface.php', $iface(
    'Commerce\\Contracts\\Pricing',
    'PriceResolverInterface',
    <<<'METHODS'
    public function resolve(PurchasableInterface $purchasable, PricingContextInterface $context): PriceQuoteInterface;
METHODS
));

$write('packages/commerce/contracts/src/Pricing/PricingContextInterface.php', $iface(
    'Commerce\\Contracts\\Pricing',
    'PricingContextInterface',
    <<<'METHODS'
    public function getChannel(): string;

    public function getCurrency(): string;

    public function getQuantity(): int;

    public function getCustomerUuid(): ?string;
METHODS
));

$write('packages/commerce/contracts/src/Pricing/PriceQuoteInterface.php', $iface(
    'Commerce\\Contracts\\Pricing',
    'PriceQuoteInterface',
    <<<'METHODS'
    public function getAmount(): int;

    public function getCurrency(): string;

    /**
     * @return array<string, mixed>
     */
    public function getBreakdown(): array;
METHODS
));

// Add use statement fix for PriceResolver
$files[$root . '/packages/commerce/contracts/src/Pricing/PriceResolverInterface.php'] = str_replace(
    'PurchasableInterface $purchasable, PricingContextInterface $context',
    '\\Commerce\\Contracts\\Purchasable\\PurchasableInterface $purchasable, PricingContextInterface $context',
    $files[$root . '/packages/commerce/contracts/src/Pricing/PriceResolverInterface.php']
);

$write('packages/commerce/contracts/src/Search/SearchIndexInterface.php', $iface(
    'Commerce\\Contracts\\Search',
    'SearchIndexInterface',
    <<<'METHODS'
    /**
     * @param  array<string, mixed>  $document
     */
    public function index(string $index, string $id, array $document): void;

    public function delete(string $index, string $id): void;

    public function flush(string $index): void;
METHODS
));

$write('packages/commerce/contracts/src/Search/SearchQueryInterface.php', $iface(
    'Commerce\\Contracts\\Search',
    'SearchQueryInterface',
    <<<'METHODS'
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(string $index, string $query, array $filters = [], int $page = 1, int $perPage = 25): SearchResultInterface;
METHODS
));

$write('packages/commerce/contracts/src/Search/SearchResultInterface.php', $iface(
    'Commerce\\Contracts\\Search',
    'SearchResultInterface',
    <<<'METHODS'
    /**
     * @return list<array<string, mixed>>
     */
    public function getHits(): array;

    public function getTotal(): int;

    public function getPage(): int;

    public function getPerPage(): int;
METHODS
));

$files[$root . '/packages/commerce/contracts/src/Search/SearchQueryInterface.php'] = str_replace(
    'SearchResultInterface',
    '\\Commerce\\Contracts\\Search\\SearchResultInterface',
    $files[$root . '/packages/commerce/contracts/src/Search/SearchQueryInterface.php']
);

$write('packages/commerce/contracts/src/Tax/TaxCalculatorInterface.php', $iface(
    'Commerce\\Contracts\\Tax',
    'TaxCalculatorInterface',
    <<<'METHODS'
    /**
     * @return list<TaxLineInterface>
     */
    public function calculate(TaxContextInterface $context): array;
METHODS
));

$write('packages/commerce/contracts/src/Tax/TaxContextInterface.php', $iface(
    'Commerce\\Contracts\\Tax',
    'TaxContextInterface',
    <<<'METHODS'
    public function getCurrency(): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function getLineItems(): array;

    /**
     * @return array<string, mixed>
     */
    public function getShippingAddress(): array;

    /**
     * @return array<string, mixed>
     */
    public function getBillingAddress(): array;
METHODS
));

$write('packages/commerce/contracts/src/Tax/TaxLineInterface.php', $iface(
    'Commerce\\Contracts\\Tax',
    'TaxLineInterface',
    <<<'METHODS'
    public function getLabel(): string;

    public function getRate(): float;

    public function getAmount(): int;

    public function getCurrency(): string;
METHODS
));

$files[$root . '/packages/commerce/contracts/src/Tax/TaxCalculatorInterface.php'] = str_replace(
    'TaxContextInterface $context): array',
    '\\Commerce\\Contracts\\Tax\\TaxContextInterface $context): array',
    $files[$root . '/packages/commerce/contracts/src/Tax/TaxCalculatorInterface.php']
);

$files[$root . '/packages/commerce/contracts/src/Tax/TaxCalculatorInterface.php'] = str_replace(
    '@return list<TaxLineInterface>',
    '@return list<\\Commerce\\Contracts\\Tax\\TaxLineInterface>',
    $files[$root . '/packages/commerce/contracts/src/Tax/TaxCalculatorInterface.php']
);

$write('packages/commerce/contracts/src/Module/ModuleInterface.php', $iface(
    'Commerce\\Contracts\\Module',
    'ModuleInterface',
    <<<'METHODS'
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    public function getPriority(): int;

    /**
     * @return list<string>
     */
    public function getDependencies(): array;

    /**
     * @return list<string>
     */
    public function getSoftDependencies(): array;
METHODS
));

$write('packages/commerce/contracts/src/Module/ModuleServiceProviderInterface.php', $iface(
    'Commerce\\Contracts\\Module',
    'ModuleServiceProviderInterface',
    <<<'METHODS'
    public function getModule(): ModuleInterface;
METHODS
));

$write('packages/commerce/contracts/src/Plugin/PluginInterface.php', $iface(
    'Commerce\\Contracts\\Plugin',
    'PluginInterface',
    <<<'METHODS'
    public function getName(): string;

    public function getAlias(): string;

    public function getVersion(): string;

    /**
     * @return list<string>
     */
    public function getRequiredModules(): array;

    /**
     * @return array<string, string>
     */
    public function getBindings(): array;

    /**
     * @return list<string>
     */
    public function getHooks(): array;
METHODS
));

$write('packages/commerce/contracts/src/Hook/HookRegistryInterface.php', $iface(
    'Commerce\\Contracts\\Hook',
    'HookRegistryInterface',
    <<<'METHODS'
  public function register(string $hook, callable $callback, int $priority = 10): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(string $hook, array $context = []): void;

    /**
     * @param  mixed  $value
     * @param  array<string, mixed>  $context
     * @return mixed
     */
    public function filter(string $hook, mixed $value, array $context = []): mixed;
METHODS
));

$write('packages/commerce/contracts/src/Hook/HookableInterface.php', $iface(
    'Commerce\\Contracts\\Hook',
    'HookableInterface',
    <<<'METHODS'
    public function registerHooks(HookRegistryInterface $hooks): void;
METHODS
));

$files[$root . '/packages/commerce/contracts/src/Hook/HookableInterface.php'] = str_replace(
    'HookRegistryInterface $hooks',
    '\\Commerce\\Contracts\\Hook\\HookRegistryInterface $hooks',
    $files[$root . '/packages/commerce/contracts/src/Hook/HookableInterface.php']
);

$write('packages/commerce/contracts/src/ValueObject/MoneyInterface.php', $iface(
    'Commerce\\Contracts\\ValueObject',
    'MoneyInterface',
    <<<'METHODS'
    public function getAmount(): int;

    public function getCurrency(): string;
METHODS
));

$write('packages/commerce/contracts/src/ValueObject/AddressInterface.php', $iface(
    'Commerce\\Contracts\\ValueObject',
    'AddressInterface',
    <<<'METHODS'
    public function getLine1(): string;

    public function getLine2(): ?string;

    public function getCity(): string;

    public function getState(): ?string;

    public function getPostalCode(): string;

    public function getCountryCode(): string;
METHODS
));

$write('packages/commerce/contracts/src/ValueObject/SlugInterface.php', $iface(
    'Commerce\\Contracts\\ValueObject',
    'SlugInterface',
    <<<'METHODS'
    public function toString(): string;
METHODS
));

$write('packages/commerce/contracts/src/Channel/ChannelContextInterface.php', $iface(
    'Commerce\\Contracts\\Channel',
    'ChannelContextInterface',
    <<<'METHODS'
    public function getChannel(): string;

    public function getLocale(): string;

    public function getCurrency(): string;
METHODS
));

$write('packages/commerce/contracts/src/Tenant/TenantAwareInterface.php', $iface(
    'Commerce\\Contracts\\Tenant',
    'TenantAwareInterface',
    <<<'METHODS'
    public function getTenantId(): ?int;

    public function setTenantId(?int $tenantId): void;
METHODS
));

// ---------------------------------------------------------------------------
// Support
// ---------------------------------------------------------------------------

$write('packages/commerce/support/src/DTO/DataTransferObject.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Support\DTO;

abstract readonly class DataTransferObject
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

PHP);

// ---------------------------------------------------------------------------
// Core
// ---------------------------------------------------------------------------

$write('packages/commerce/core/src/helpers.php', <<<'PHP'
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

PHP);

$write('packages/commerce/core/src/CommerceServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Hook\HookRegistryInterface;
use Commerce\Core\Events\EventBus;
use Commerce\Core\Hooks\HookRegistry;
use Illuminate\Support\ServiceProvider;

class CommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/commerce.php', 'commerce');

        $this->app->singleton(EventBusInterface::class, EventBus::class);
        $this->app->singleton(HookRegistryInterface::class, HookRegistry::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/commerce.php' => config_path('commerce.php'),
        ], 'commerce-config');
    }
}

PHP);

$write('packages/commerce/core/config/commerce.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => 'Commerce Framework',
    'version' => '1.0.0-alpha',

    'modules' => [
        'iam' => true,
        'settings' => true,
    ],

    'plugins' => [],

    'tenant' => [
        'enabled' => false,
    ],

    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
    ],
];

PHP);

$write('packages/commerce/core/src/Base/BaseService.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Commerce\Contracts\Service\ServiceInterface;

abstract class BaseService implements ServiceInterface
{
}

PHP);

$write('packages/commerce/core/src/Base/BaseRepository.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Commerce\Contracts\Repository\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return null;
    }

    public function findById(int $id): ?object
    {
        return null;
    }
}

PHP);

$write('packages/commerce/core/src/Base/BaseQueryService.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Commerce\Contracts\Query\QueryServiceInterface;

abstract class BaseQueryService implements QueryServiceInterface
{
}

PHP);

$write('packages/commerce/core/src/Base/BaseModuleServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

use Illuminate\Support\ServiceProvider;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    abstract public function getModuleAlias(): string;

    protected function modulePath(string $path = ''): string
    {
        $base = $this->getModuleRoot();

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    protected function getModuleRoot(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName(), 2);
    }
}

PHP);

$write('packages/commerce/core/src/Base/BasePolicy.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Base;

abstract class BasePolicy
{
}

PHP);

$write('packages/commerce/core/src/Enums/Channel.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Enums;

enum Channel: string
{
    case Web = 'web';
    case Pos = 'pos';
    case Api = 'api';
    case Marketplace = 'marketplace';
}

PHP);

$write('packages/commerce/core/src/Support/Money.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Support;

use Commerce\Contracts\ValueObject\MoneyInterface;

final readonly class Money implements MoneyInterface
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {}

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}

PHP);

$write('packages/commerce/core/src/Support/Slug.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Support;

use Commerce\Contracts\ValueObject\SlugInterface;
use Illuminate\Support\Str;

final readonly class Slug implements SlugInterface
{
    public function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        return new self(Str::slug($value));
    }

    public function toString(): string
    {
        return $this->value;
    }
}

PHP);

$write('packages/commerce/core/src/Exceptions/DomainException.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
}

PHP);

$write('packages/commerce/core/src/Exceptions/EntityNotFoundException.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Exceptions;

final class EntityNotFoundException extends DomainException
{
}

PHP);

$write('packages/commerce/core/src/Events/EventBus.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Events;

use Commerce\Contracts\Event\EventBusInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

final class EventBus implements EventBusInterface
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function dispatchAsync(object $event): void
    {
        if ($event instanceof ShouldQueue) {
            $this->dispatcher->dispatch($event);

            return;
        }

        $this->dispatcher->dispatch($event);
    }

    public function listen(string $event, callable|string $listener, bool $async = false): void
    {
        $this->dispatcher->listen($event, $listener);
    }
}

PHP);

$write('packages/commerce/core/src/Hooks/HookRegistry.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Core\Hooks;

use Commerce\Contracts\Hook\HookRegistryInterface;

final class HookRegistry implements HookRegistryInterface
{
    /** @var array<string, list<array{callable, int}>> */
    private array $actions = [];

    /** @var array<string, list<array{callable, int}>> */
    private array $filters = [];

    public function register(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = [$callback, $priority];
        usort($this->actions[$hook], static fn (array $a, array $b): int => $a[1] <=> $b[1]);
    }

    public function execute(string $hook, array $context = []): void
    {
        foreach ($this->actions[$hook] ?? [] as [$callback]) {
            $callback($context);
        }
    }

    public function filter(string $hook, mixed $value, array $context = []): mixed
    {
        foreach ($this->filters[$hook] ?? [] as [$callback]) {
            $value = $callback($value, $context);
        }

        return $value;
    }

    public function registerFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][] = [$callback, $priority];
        usort($this->filters[$hook], static fn (array $a, array $b): int => $a[1] <=> $b[1]);
    }
}

PHP);

// ---------------------------------------------------------------------------
// Module Manager
// ---------------------------------------------------------------------------

$write('packages/commerce/module-manager/src/ModuleManagerServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Illuminate\Support\ServiceProvider;

class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(ModuleDependencyResolver::class);
        $this->app->singleton(ModuleActivator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\ModuleListCommand::class,
            ]);
        }
    }
}

PHP);

$write('packages/commerce/module-manager/src/ModuleManager.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

final class ModuleManager
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleDependencyResolver $resolver,
        private readonly ModuleActivator $activator,
    ) {}

    public function boot(): void
    {
        $modules = $this->resolver->resolve($this->registry->all());
        $this->activator->boot($modules);
    }

    public function registry(): ModuleRegistry
    {
        return $this->registry;
    }
}

PHP);

$write('packages/commerce/module-manager/src/ModuleRegistry.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

final class ModuleRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $modules = [];

    public function register(string $alias, array $manifest): void
    {
        $this->modules[$alias] = $manifest;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $alias): ?array
    {
        return $this->modules[$alias] ?? null;
    }

    public function isEnabled(string $alias): bool
    {
        return (bool) config("commerce.modules.{$alias}", false);
    }
}

PHP);

$write('packages/commerce/module-manager/src/ModuleDependencyResolver.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Commerce\Core\Exceptions\DomainException;

final class ModuleDependencyResolver
{
    /**
     * @param  array<string, array<string, mixed>>  $modules
     * @return list<array<string, mixed>>
     */
    public function resolve(array $modules): array
    {
        $sorted = [];
        $visited = [];

        $visit = function (string $alias) use (&$visit, &$sorted, &$visited, $modules): void {
            if (isset($visited[$alias])) {
                return;
            }

            $visited[$alias] = true;
            $manifest = $modules[$alias] ?? null;

            if ($manifest === null) {
                throw new DomainException("Module [{$alias}] is not registered.");
            }

            $deps = $manifest['dependencies']['hard'] ?? $manifest['dependencies'] ?? [];

            if (is_array($deps)) {
                foreach ($deps as $dep) {
                    if (is_string($dep)) {
                        $visit($dep);
                    }
                }
            }

            $sorted[$alias] = $manifest;
        };

        foreach (array_keys($modules) as $alias) {
            $visit($alias);
        }

        uasort($sorted, static fn (array $a, array $b): int => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        return array_values($sorted);
    }
}

PHP);

$write('packages/commerce/module-manager/src/ModuleActivator.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Illuminate\Contracts\Foundation\Application;

final class ModuleActivator
{
    public function __construct(private readonly Application $app) {}

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    public function boot(array $modules): void
    {
        foreach ($modules as $manifest) {
            $alias = $manifest['alias'] ?? null;

            if (! is_string($alias) || ! config("commerce.modules.{$alias}", false)) {
                continue;
            }

            foreach ($manifest['providers'] ?? [] as $provider) {
                if (is_string($provider) && class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }
}

PHP);

$write('packages/commerce/module-manager/src/Commands/ModuleListCommand.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Commands;

use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Console\Command;

final class ModuleListCommand extends Command
{
    protected $signature = 'commerce:modules';

    protected $description = 'List registered Commerce Framework modules';

    public function handle(ModuleRegistry $registry): int
    {
        $rows = [];

        foreach ($registry->all() as $alias => $manifest) {
            $rows[] = [
                $alias,
                $manifest['name'] ?? $alias,
                $manifest['version'] ?? 'n/a',
                $registry->isEnabled($alias) ? 'enabled' : 'disabled',
                (string) ($manifest['priority'] ?? 100),
            ];
        }

        $this->table(['Alias', 'Name', 'Version', 'Status', 'Priority'], $rows);

        return self::SUCCESS;
    }
}

PHP);

// ---------------------------------------------------------------------------
// Plugin Manager
// ---------------------------------------------------------------------------

$write('packages/commerce/plugin-manager/src/PluginManagerServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Support\ServiceProvider;

class PluginManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(PluginLoader::class);
    }

    public function boot(): void
    {
        $this->app->make(PluginManager::class)->boot();
    }
}

PHP);

$write('packages/commerce/plugin-manager/src/PluginManager.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Contracts\Foundation\Application;

final class PluginManager
{
    public function __construct(
        private readonly Application $app,
        private readonly PluginLoader $loader,
    ) {}

    public function boot(): void
    {
        foreach ($this->loader->discover() as $manifest) {
            $alias = $manifest['alias'] ?? null;

            if (! is_string($alias) || ! config("commerce.plugins.{$alias}", false)) {
                continue;
            }

            foreach ($manifest['bindings'] ?? [] as $abstract => $concrete) {
                if (is_string($abstract) && is_string($concrete)) {
                    $this->app->bind($abstract, $concrete);
                }
            }

            foreach ($manifest['providers'] ?? [] as $provider) {
                if (is_string($provider) && class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }
}

PHP);

$write('packages/commerce/plugin-manager/src/PluginLoader.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\PluginManager;

use Illuminate\Filesystem\Filesystem;

final class PluginLoader
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function discover(): array
    {
        $path = base_path('plugins');
        $manifests = [];

        if (! $this->files->isDirectory($path)) {
            return [];
        }

        foreach ($this->files->directories($path) as $directory) {
            $manifestFile = $directory . '/plugin.json';

            if ($this->files->exists($manifestFile)) {
                $manifests[] = json_decode($this->files->get($manifestFile), true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $manifests;
    }
}

PHP);

// ---------------------------------------------------------------------------
// API Package
// ---------------------------------------------------------------------------

$write('packages/commerce/api/src/ApiServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Api;

use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }
}

PHP);

$write('packages/commerce/api/src/Responses/ApiResponse.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Api\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>|list<mixed>|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'links' => [],
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function error(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}

PHP);

$write('packages/commerce/api/src/Middleware/ApiVersionMiddleware.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        $request->attributes->set('api_version', $version);

        return $next($request);
    }
}

PHP);

$write('packages/commerce/api/src/Middleware/ForceJsonResponse.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

PHP);

$write('packages/commerce/api/src/Resources/ApiResource.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Api\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiResource extends JsonResource
{
}

PHP);

// ---------------------------------------------------------------------------
// IAM Module
// ---------------------------------------------------------------------------

$write('modules/Iam/module.json', json_encode([
    'name' => 'IAM',
    'alias' => 'iam',
    'description' => 'Identity & Access Management',
    'version' => '1.0.0',
    'priority' => 10,
    'providers' => ['Commerce\\Iam\\IamServiceProvider'],
    'dependencies' => ['hard' => [], 'soft' => ['settings']],
    'permissions' => [
        'iam.user.view',
        'iam.role.view',
        'iam.permission.view',
    ],
    'settings' => [],
    'admin_menu' => [
        'label' => 'Users & Access',
        'icon' => 'shield',
        'route' => 'admin.iam.users.index',
        'order' => 5,
        'permission' => 'iam.user.view',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$write('modules/Iam/src/IamServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Iam;

use Commerce\Core\Base\BaseModuleServiceProvider;

final class IamServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'iam';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/iam.php'), 'iam');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'iam');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'iam');
    }
}

PHP);

$write('modules/Iam/src/IamModule.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Iam;

use Commerce\Contracts\Module\ModuleInterface;

final class IamModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'IAM';
    }

    public function getAlias(): string
    {
        return 'iam';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['settings'];
    }
}

PHP);

$write('modules/Iam/config/iam.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'registration_enabled' => false,
    'email_verification_required' => true,
    'two_factor' => [
        'enabled' => false,
        'required' => false,
    ],
    'teams' => [
        'enabled' => false,
    ],
    'impersonation' => [
        'enabled' => true,
        'require_reason' => true,
    ],
];

PHP);

$write('modules/Iam/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('admin/iam')->name('admin.iam.')->group(function (): void {
    Route::view('/users', 'iam::admin.placeholder')->name('users.index');
});

PHP);

$write('modules/Iam/routes/api.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function (): void {
    Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
        // Authentication endpoints — implementation phase
    });
});

PHP);

$write('modules/Iam/resources/views/admin/placeholder.blade.php', <<<'BLADE'
@extends('layouts.admin')

@section('title', 'IAM')

@section('content')
    <div class="p-6">
        <h1 class="text-xl font-semibold">Identity & Access Management</h1>
        <p class="mt-2 text-gray-600">Kernel scaffold — business logic not yet implemented.</p>
    </div>
@endsection

BLADE);

// IAM contract placeholders
foreach ([
    'Authentication/AuthenticationServiceInterface',
    'Authorization/AuthorizationServiceInterface',
    'Profile/ProfileServiceInterface',
    'Session/SessionServiceInterface',
    'Token/ApiTokenServiceInterface',
    'TwoFactor/TwoFactorServiceInterface',
    'Security/PasswordResetServiceInterface',
    'Activity/IamAuditServiceInterface',
    'Impersonation/ImpersonationServiceInterface',
    'Preferences/UserPreferenceServiceInterface',
] as $contract) {
    [$dir, $name] = explode('/', $contract);
    $ns = 'Commerce\\Iam\\Contracts\\' . $dir;
    $write("modules/Iam/src/Contracts/{$dir}/{$name}.php", $iface($ns, $name, '    // Contract placeholder — implementation phase.'));
}

// ---------------------------------------------------------------------------
// Settings Module
// ---------------------------------------------------------------------------

$write('modules/Settings/module.json', json_encode([
    'name' => 'Settings',
    'alias' => 'settings',
    'description' => 'System and tenant settings',
    'version' => '1.0.0',
    'priority' => 10,
    'providers' => ['Commerce\\Settings\\SettingsServiceProvider'],
    'dependencies' => ['hard' => [], 'soft' => ['iam']],
    'permissions' => ['settings.setting.view', 'settings.setting.update'],
    'settings' => [],
    'admin_menu' => [
        'label' => 'Settings',
        'icon' => 'cog',
        'route' => 'admin.settings.index',
        'order' => 90,
        'permission' => 'settings.setting.view',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$write('modules/Settings/src/SettingsServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;

final class SettingsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'settings';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/settings.php'), 'settings');

        // Implementation phase: bind concrete services
        // $this->app->bind(SettingQueryServiceInterface::class, SettingQueryService::class);
        // $this->app->bind(SettingRegistryServiceInterface::class, SettingRegistryService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'settings');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'settings');
    }
}

PHP);

$write('modules/Settings/src/SettingsModule.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Contracts\Module\ModuleInterface;

final class SettingsModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Settings';
    }

    public function getAlias(): string
    {
        return 'settings';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam'];
    }
}

PHP);

$write('modules/Settings/config/settings.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'cache_ttl' => 3600,
    'defaults' => [
        'store.name' => 'Commerce Store',
        'store.currency' => 'THB',
        'store.timezone' => 'Asia/Bangkok',
        'store.locale' => 'en',
    ],
];

PHP);

$write('modules/Settings/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('admin/settings')->name('admin.settings.')->group(function (): void {
    Route::view('/', 'settings::admin.placeholder')->name('index');
});

PHP);

$write('modules/Settings/routes/api.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/settings')->name('api.v1.settings.')->group(function (): void {
    // Settings API endpoints — implementation phase
});

PHP);

$write('modules/Settings/resources/views/admin/placeholder.blade.php', <<<'BLADE'
@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="p-6">
        <h1 class="text-xl font-semibold">Settings</h1>
        <p class="mt-2 text-gray-600">Kernel scaffold — business logic not yet implemented.</p>
    </div>
@endsection

BLADE);

$write('modules/Settings/src/Contracts/SettingServiceInterface.php', $iface(
    'Commerce\\Settings\\Contracts',
    'SettingServiceInterface',
    '    // Contract placeholder — implementation phase.'
));

// ---------------------------------------------------------------------------
// App integration files
// ---------------------------------------------------------------------------

$write('config/commerce.php', <<<'PHP'
<?php

declare(strict_types=1);

return require __DIR__ . '/../packages/commerce/core/config/commerce.php';

PHP);

$write('app/Providers/CommerceFrameworkServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Commerce\ModuleManager\ModuleManager;
use Commerce\ModuleManager\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

final class CommerceFrameworkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerModules();
        $this->app->make(ModuleManager::class)->boot();
    }

    private function registerModules(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($this->discoverModuleManifests() as $alias => $manifest) {
            $registry->register($alias, $manifest);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function discoverModuleManifests(): array
    {
        $manifests = [];
        $path = base_path('modules');

        if (! is_dir($path)) {
            return [];
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestFile = $path . '/' . $entry . '/module.json';

            if (is_file($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
                $alias = $manifest['alias'] ?? strtolower($entry);
                $manifests[$alias] = $manifest;
            }
        }

        return $manifests;
    }
}

PHP);

$write('resources/views/layouts/admin.blade.php', <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('commerce.name', 'Commerce Framework') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <span class="font-semibold">{{ config('commerce.name', 'Commerce Framework') }}</span>
            <span class="text-sm text-gray-500">v{{ config('commerce.version', '1.0.0-alpha') }}</span>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-6 py-8">
        @yield('content')
    </main>
</body>
</html>

BLADE);

$write('README.md', <<<'MD'
# Commerce Framework

Modular commerce platform kernel for ecommerce, POS, inventory, and multi-channel retail.

**Version:** 1.0.0-alpha

## Kernel Packages

| Package | Purpose |
|---|---|
| `commerce/contracts` | Shared interfaces (Pricing, Search, Tax, SEO, Media, EventBus, …) |
| `commerce/core` | Base classes, value objects, EventBus, HookRegistry |
| `commerce/support` | DTO base classes |
| `commerce/module-manager` | Module discovery and lifecycle |
| `commerce/plugin-manager` | Plugin hooks and binding overrides |
| `commerce/api` | API response envelope and middleware |

## Kernel Modules

| Module | Purpose |
|---|---|
| `Iam` | Identity & Access Management |
| `Settings` | System and tenant configuration |

## Setup

```bash
composer install
php artisan commerce:modules
```

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) and [FRAMEWORK.md](FRAMEWORK.md).

MD);

// Write all files
foreach ($files as $path => $content) {
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
}

echo 'Generated ' . count($files) . " files.\n";
