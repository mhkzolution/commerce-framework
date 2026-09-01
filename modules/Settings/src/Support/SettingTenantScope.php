<?php

declare(strict_types=1);

namespace Commerce\Settings\Support;

use Commerce\Core\Tenant\TenantContext;
use Commerce\Core\Tenant\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class SettingTenantScope
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function scopedTenantId(): ?int
    {
        if (! $this->tenantContext->isEnabled()) {
            return null;
        }

        return $this->tenantContext->id();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>|Relation<TModel, TModel, *>  $query
     * @return Builder<TModel>|Relation<TModel, TModel, *>
     */
    public function apply(Builder|Relation $query): Builder|Relation
    {
        $builder = $query instanceof Relation ? $query->getQuery() : $query;

        $builder->withoutGlobalScope(TenantScope::class);

        $tenantId = $this->scopedTenantId();
        $table = $builder->getModel()->getTable();

        if ($tenantId !== null) {
            $builder->where("{$table}.tenant_id", $tenantId);
        } else {
            $builder->whereNull("{$table}.tenant_id");
        }

        return $query;
    }

    public function cachePrefix(): string
    {
        $tenantId = $this->scopedTenantId();

        return $tenantId !== null ? "settings.t{$tenantId}." : 'settings.';
    }
}
