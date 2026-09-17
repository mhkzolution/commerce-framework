<?php

declare(strict_types=1);

namespace Commerce\Cms\Models;

use Commerce\Core\Concerns\HasUuid;
use Commerce\Core\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Popup extends Model
{
    use BelongsToTenant;
    use HasUuid;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const TYPE_IMAGE = 'image';

    public const TYPE_CONTENT = 'content';

    public const TYPE_PROMOTION = 'promotion';

    public const TARGET_SELF = 'self';

    public const TARGET_BLANK = 'blank';

    protected $table = 'cms_popups';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'slug',
        'status',
        'priority',
        'is_active',
        'headline',
        'subheadline',
        'image_media_uuid',
        'button_text',
        'button_url',
        'button_target',
        'popup_type',
        'show_delay',
        'auto_close',
        'closable',
        'start_at',
        'end_at',
        'timezone',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'show_delay' => 'integer',
            'auto_close' => 'integer',
            'closable' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->where(static function (Builder $inner) use ($now): void {
                $inner->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(static function (Builder $inner) use ($now): void {
                $inner->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('priority')
            ->orderBy('id');
    }

    public function scheduleTimezone(): string
    {
        $timezone = is_string($this->timezone) && $this->timezone !== ''
            ? $this->timezone
            : 'Asia/Bangkok';

        return $timezone;
    }

    public function localStartAt(): ?Carbon
    {
        return $this->start_at?->clone()->timezone($this->scheduleTimezone());
    }

    public function localEndAt(): ?Carbon
    {
        return $this->end_at?->clone()->timezone($this->scheduleTimezone());
    }
}
