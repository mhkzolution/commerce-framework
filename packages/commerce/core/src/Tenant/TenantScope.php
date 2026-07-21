<?php

declare(strict_types=1);

namespace Commerce\Core\Tenant;

use Commerce\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->isEnabled()) {
            return;
        }

        $tenantId = $context->id();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', $tenantId);
    }
}
