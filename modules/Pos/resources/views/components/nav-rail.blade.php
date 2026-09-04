@props([
    'active' => 'pos',
])

<nav class="pos-nav-rail" aria-label="เมนู POS">
    <div class="pos-nav-rail__brand" title="POS">POS</div>

    <a
        href="{{ route('pos.index') }}"
        class="pos-nav-rail__item {{ $active === 'pos' ? 'is-active' : '' }}"
        title="ขายหน้าร้าน"
        aria-current="{{ $active === 'pos' ? 'page' : 'false' }}"
    >
        <x-admin.icon name="credit-card" class="h-5 w-5" />
        ขาย
    </a>

    <a
        href="{{ route('pos.orders.index') }}"
        class="pos-nav-rail__item {{ $active === 'orders' ? 'is-active' : '' }}"
        title="ออเดอร์"
        @if ($active === 'orders') aria-current="page" @endif
    >
        <x-admin.icon name="shopping-bag" class="h-5 w-5" />
        ออเดอร์
    </a>

    @if (feature('pos-returns'))
        <a
            href="{{ route('pos.returns.index') }}"
            class="pos-nav-rail__item {{ $active === 'returns' ? 'is-active' : '' }}"
            title="คืนสินค้า"
            @if ($active === 'returns') aria-current="page" @endif
        >
            <x-admin.icon name="arrow-down-tray" class="h-5 w-5" />
            คืน
        </a>
    @endif

    <div class="pos-nav-rail__spacer"></div>

    @can('pos.register.view')
        <a
            href="{{ route('admin.pos.registers.index') }}"
            class="pos-nav-rail__item"
            title="เครื่อง POS"
        >
            <x-admin.icon name="device-tablet" class="h-5 w-5" />
            เครื่อง
        </a>
    @endcan

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="pos-nav-rail__item" title="ออกจากระบบ">
            <x-admin.icon name="x-mark" class="h-5 w-5" />
            ออก
        </button>
    </form>
</nav>
