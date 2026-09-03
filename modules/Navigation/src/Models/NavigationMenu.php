<?php

declare(strict_types=1);

namespace Commerce\Navigation\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NavigationMenu extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'handle',
        'name',
    ];

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function items(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'menu_id')->orderBy('position');
    }
}
