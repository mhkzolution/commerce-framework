<?php

declare(strict_types=1);

namespace Commerce\Settings\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'group_id',
        'key',
        'value',
        'type',
        'default_value',
        'validation',
        'is_public',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'validation' => 'array',
            'is_public' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SettingGroup::class, 'group_id');
    }

    public function getFullKeyAttribute(): string
    {
        return ($this->group?->code ?? 'general') . '.' . $this->key;
    }
}
