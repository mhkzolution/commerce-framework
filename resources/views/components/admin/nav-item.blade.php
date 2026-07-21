@props(['item', 'depth' => 0])

@php
    use App\Support\Admin\AdminUi;
    use Illuminate\Support\Facades\Route;

    $isGroup = ($item['type'] ?? 'link') === 'group';
    $isActive = AdminUi::navIsActive($item['route'] ?? null);
    $href = null;

    if (! $isGroup) {
        $href = $item['url'] ?? (($item['route'] ?? null) && Route::has($item['route']) ? route($item['route']) : null);
    }
@endphp

@if ($isGroup)
    @php
        $groupId = $item['id'];
        $isOpen = AdminUi::groupIsOpen($item);
    @endphp
    <div data-nav-item data-nav-label="{{ $item['label'] }}">
        <button
            type="button"
            class="admin-nav-item w-full"
            data-nav-group-trigger="{{ $groupId }}"
            data-default-open="{{ $isOpen ? 'true' : 'false' }}"
            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
        >
            @if (!empty($item['icon']))
                <x-admin.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
            @endif
            <span class="admin-nav-label flex-1 text-left">{{ $item['label'] }}</span>
            @if (!empty($item['badge']))
                <x-admin.badge :variant="$item['badge_variant'] ?? 'default'">{{ $item['badge'] }}</x-admin.badge>
            @endif
            <x-admin.icon name="chevron-down" class="admin-nav-chevron h-4 w-4 shrink-0 transition-transform [[aria-expanded=true]_&]:rotate-180" />
        </button>
        <div class="admin-nav-children mt-1 space-y-1" data-nav-group-panel="{{ $groupId }}" @unless($isOpen) hidden @endunless>
            @foreach ($item['children'] ?? [] as $child)
                <x-admin.nav-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </div>
@elseif ($href)
    <a
        href="{{ $href }}"
        data-nav-item
        data-nav-label="{{ $item['label'] }}"
        @class([
            'admin-nav-item',
            'is-active' => $isActive,
            'is-child' => $depth > 0,
        ])
    >
        @if (!empty($item['icon']))
            <x-admin.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
        @endif
        <span class="admin-nav-label flex-1">{{ $item['label'] }}</span>
        @if (!empty($item['badge']))
            <x-admin.badge :variant="$item['badge_variant'] ?? 'default'">{{ $item['badge'] }}</x-admin.badge>
        @endif
    </a>
@endif
