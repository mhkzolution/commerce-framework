<?php

declare(strict_types=1);

namespace Commerce\Core\Models;

use Commerce\Core\Enums\FeatureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemFeature extends Model
{
    protected $table = 'system_features';

    protected $fillable = [
        'code',
        'name',
        'description',
        'module_code',
        'status',
        'is_core',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => FeatureStatus::class,
            'is_core' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            Cache::forget('commerce.system_features');
        });

        static::deleted(static function (): void {
            Cache::forget('commerce.system_features');
        });
    }
}
