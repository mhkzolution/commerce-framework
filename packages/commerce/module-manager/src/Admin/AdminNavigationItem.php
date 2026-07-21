<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

final readonly class AdminNavigationItem
{
    /**
     * @param  list<AdminNavigationItem>  $children
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $type,
        public ?string $icon = null,
        public ?string $route = null,
        public ?string $url = null,
        public ?string $permission = null,
        public ?string $badge = null,
        public ?string $badgeVariant = 'default',
        public int $order = 100,
        public bool $collapsible = true,
        public bool $defaultOpen = false,
        public array $children = [],
        public ?string $module = null,
    ) {}

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'permission' => $this->permission,
            'badge' => $this->badge,
            'badge_variant' => $this->badgeVariant,
            'order' => $this->order,
            'collapsible' => $this->collapsible,
            'default_open' => $this->defaultOpen,
            'module' => $this->module,
            'children' => array_map(
                static fn (AdminNavigationItem $child): array => $child->toArray(),
                $this->children,
            ),
        ];
    }
}
