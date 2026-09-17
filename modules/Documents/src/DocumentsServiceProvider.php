<?php

declare(strict_types=1);

namespace Commerce\Documents;

use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Customers\Models\Customer;
use Commerce\Documents\Contracts\DocumentServiceInterface;
use Commerce\Documents\Handlers\TaxInvoiceHandler;
use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Documents\Registry\DocumentTypeRegistry;
use Commerce\Documents\Services\CompanyProfileService;
use Commerce\Documents\Services\CustomerTaxProfileService;
use Commerce\Documents\Services\DocumentNumberGenerator;
use Commerce\Documents\Services\DocumentPdfService;
use Commerce\Documents\Services\DocumentQueryService;
use Commerce\Documents\Services\DocumentSequenceService;
use Commerce\Documents\Services\DocumentService;
use Commerce\Documents\Services\TaxInvoiceIssueService;
use Illuminate\View\View;

final class DocumentsServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'documents';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/documents.php'), 'documents');

        $this->app->singleton(DocumentTypeRegistry::class);
        $this->app->singleton(DocumentNumberGenerator::class);
        $this->app->singleton(DocumentSequenceService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(DocumentQueryService::class);
        $this->app->singleton(DocumentPdfService::class);
        $this->app->singleton(TaxInvoiceHandler::class);
        $this->app->singleton(CompanyProfileService::class);
        $this->app->singleton(CustomerTaxProfileService::class);
        $this->app->singleton(TaxInvoiceIssueService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'documents');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'documents');

        $this->app->make(DocumentTypeRegistry::class)->register(
            $this->app->make(TaxInvoiceHandler::class),
        );

        $this->app->make('view')->composer('customers::admin.edit', function (View $view): void {
            $customer = $view->getData()['customer'] ?? null;

            $view->with(
                'taxProfile',
                $customer instanceof Customer
                    ? CustomerTaxProfile::query()->where('customer_id', $customer->getKey())->first()
                    : null,
            );
        });
    }
}
