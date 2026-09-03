<?php

declare(strict_types=1);

namespace Commerce\Navigation\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NavigationMenuItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'menu_id',
        'label',
        'url',
        'position',
        'is_visible',
        'footer_enabled',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_visible' => 'boolean',
            'footer_enabled' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }
}
