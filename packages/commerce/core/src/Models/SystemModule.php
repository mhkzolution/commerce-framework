<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    protected $table = 'system_modules';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'sort_order',
        'is_core',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModuleStatus::class,
            'sort_order' => 'integer',
            'is_core' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            ModuleService::clearCache();
        });

        static::deleted(static function (): void {
            ModuleService::clearCache();
        });
    }
}
