<?php

declare(strict_types=1);

namespace Tests\Feature\Barcode;

use Commerce\Barcode\BarcodeServiceProvider;
use Commerce\Contracts\Barcode\BarcodeValueGeneratorInterface;
use Commerce\Core\Barcode\BarcodeValueGenerator;
use Commerce\Core\Barcode\Strategies\PrefixBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\RandomBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\TimestampBarcodeStrategy;
use Commerce\Core\CommerceServiceProvider;
use Commerce\Catalog\CatalogServiceProvider;
use Commerce\ModuleManager\ModuleManagerServiceProvider;
use Commerce\Iam\IamServiceProvider;
use Commerce\Inventory\InventoryServiceProvider;
use Commerce\Marketplace\MarketplaceServiceProvider;
use Commerce\Media\MediaServiceProvider;
use Commerce\Product\ProductServiceProvider;
use Commerce\Settings\SettingsServiceProvider;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class BarcodeFeatureTestCase extends TestCase
{
    use RefreshDatabase;
    protected function beforeRefreshingDatabase()
    {
        $this->registerBarcodeModule();
    }

    protected function afterRefreshingDatabase()
    {
        $this->runModuleMigrations();
    }

    protected function runModuleMigrations(): void
    {
        $migrator = $this->app->make('migrator');

        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        foreach ([
            'modules/Iam/database/migrations',
            'modules/Settings/database/migrations',
            'modules/Media/database/migrations',
            'modules/Catalog/database/migrations',
            'modules/Product/database/migrations',
            'modules/Inventory/database/migrations',
            'modules/Marketplace/database/migrations',
            'modules/Barcode/database/migrations',
        ] as $relative) {
            $path = base_path($relative);
            if (is_dir($path)) {
                $migrator->run([$path]);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->registerBarcodeModule();
    }

    protected function registerBarcodeModule(): void
    {
        foreach ([
            ModuleManagerServiceProvider::class,
            CommerceServiceProvider::class,
            IamServiceProvider::class,
            SettingsServiceProvider::class,
            MediaServiceProvider::class,
            CatalogServiceProvider::class,
            ProductServiceProvider::class,
            InventoryServiceProvider::class,
            MarketplaceServiceProvider::class,
            BarcodeServiceProvider::class,
        ] as $provider) {
            if ($this->app->getProvider($provider) === null) {
                $this->app->register($provider);
            }
        }

        $this->app['router']->getRoutes()->refreshNameLookups();

        if (! $this->app->bound(BarcodeValueGeneratorInterface::class)) {
            $this->app->singleton(BarcodeValueGeneratorInterface::class, static function (): BarcodeValueGenerator {
                return new BarcodeValueGenerator([
                    new RandomBarcodeStrategy,
                    new TimestampBarcodeStrategy,
                    new PrefixBarcodeStrategy,
                ]);
            });
        }
    }

    protected function superAdmin(): User
    {
        return User::query()->firstOrFail();
    }

    /**
     * @param  list<string>  $permissionNames
     */
    protected function userWithBarcodePermissions(string $email, array $permissionNames): User
    {
        $role = Role::query()->create([
            'name' => $email,
            'code' => str_replace(['@', '.'], '-', $email),
            'is_system' => false,
        ]);

        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissionNames)->pluck('id'),
        );

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: $email,
            email: $email,
            password: 'password',
            roleCodes: [$role->code],
        ));

        app(AuthorizationService::class)->clearCacheForUser($user->id);

        return $user;
    }

    protected function operatorUser(): User
    {
        return $this->userWithBarcodePermissions('barcode-operator@example.test', [
            'barcode.print',
            'barcode.history.view',
        ]);
    }

    protected function barcodeAdminUser(): User
    {
        return $this->userWithBarcodePermissions('barcode-admin@example.test', [
            'barcode.print',
            'barcode.history.view',
            'barcode.template.manage',
            'barcode.history.reprint',
        ]);
    }

    protected function userWithoutBarcodePermissions(): User
    {
        return $this->userWithBarcodePermissions('barcode-none@example.test', []);
    }
}
