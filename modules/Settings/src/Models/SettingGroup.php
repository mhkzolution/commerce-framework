<?php

declare(strict_types=1);

namespace Commerce\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettingGroup extends Model
{
    protected $fillable = [
        'module',
        'code',
        'label',
        'description',
        'position',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class, 'group_id');
    }
}
