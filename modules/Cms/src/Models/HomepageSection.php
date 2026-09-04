<?php

declare(strict_types=1);

namespace Commerce\Cms\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageSection extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    public const KEY_HERO = 'hero';

    public const KEY_PROMOTIONS = 'promotions';

    public const KEY_CATEGORIES = 'categories';

    public const KEY_ARRIVALS = 'arrivals';

    public const KEY_ARTICLES = 'articles';

    public const KEY_FAQ = 'faq';

    public const LAYOUT_SLIDER = 'slider';

    public const LAYOUT_GRID = 'grid';

    public const LAYOUT_FULL_WIDTH = 'full_width';

    protected $table = 'cms_homepage_sections';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'key',
        'type',
        'layout',
        'sort_order',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @return list<array{key: string, type: string, layout: string, sort_order: int, is_active: bool, settings: array<string, mixed>}>
     */
    public static function defaultBlueprint(): array
    {
        return [
            ['key' => self::KEY_HERO, 'type' => self::KEY_HERO, 'layout' => self::LAYOUT_FULL_WIDTH, 'sort_order' => 10, 'is_active' => true, 'settings' => ['autoplay' => true]],
            ['key' => self::KEY_PROMOTIONS, 'type' => self::KEY_PROMOTIONS, 'layout' => self::LAYOUT_SLIDER, 'sort_order' => 20, 'is_active' => true, 'settings' => ['autoplay' => true, 'columns' => 2]],
            ['key' => self::KEY_CATEGORIES, 'type' => self::KEY_CATEGORIES, 'layout' => self::LAYOUT_GRID, 'sort_order' => 25, 'is_active' => true, 'settings' => []],
            ['key' => self::KEY_ARRIVALS, 'type' => self::KEY_ARRIVALS, 'layout' => self::LAYOUT_SLIDER, 'sort_order' => 30, 'is_active' => true, 'settings' => []],
            ['key' => self::KEY_ARTICLES, 'type' => self::KEY_ARTICLES, 'layout' => self::LAYOUT_SLIDER, 'sort_order' => 40, 'is_active' => true, 'settings' => []],
            ['key' => self::KEY_FAQ, 'type' => self::KEY_FAQ, 'layout' => self::LAYOUT_FULL_WIDTH, 'sort_order' => 50, 'is_active' => true, 'settings' => []],
        ];
    }
}
