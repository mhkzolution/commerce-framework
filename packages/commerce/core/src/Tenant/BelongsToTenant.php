<?php

declare(strict_types=1);

namespace Commerce\Core\Tenant;

use Commerce\Core\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model): void {
            $context = app(TenantContext::class);

            if (! $context->isEnabled() || $context->id() === null) {
                return;
            }

            if ($model->tenant_id === null) {
                $model->tenant_id = $context->id();
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($this->getTable() . '.tenant_id', $tenantId);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
