@props([
    'cashier' => '',
    'branch' => '',
    'register' => '',
    'shift' => '',
    'networkStatus' => 'online',
    'syncStatus' => 'synced',
    'registers' => collect(),
    'currentRegisterUuid' => null,
])

<header class="pos-topbar" role="banner">
    <div class="pos-topbar__group">
        <div class="pos-topbar__item">
            <span class="pos-topbar__label">แคชเชียร์</span>
            <span class="pos-topbar__value">{{ $cashier }}</span>
        </div>
        <div class="pos-topbar__item">
            <span class="pos-topbar__label">สาขา</span>
            <span class="pos-topbar__value">{{ $branch }}</span>
        </div>
        <div class="pos-topbar__item">
            <span class="pos-topbar__label">เครื่อง POS</span>
            @if ($registers->count() > 1)
                <label class="sr-only" for="pos-register-switch">เปลี่ยนเครื่อง</label>
                <select
                    id="pos-register-switch"
                    class="pos-topbar__value pos-topbar__select"
                    onchange="if (this.value) window.location.href = this.value"
                >
                    @foreach ($registers as $item)
                        <option
                            value="{{ route('pos.index', ['register' => $item->uuid]) }}"
                            @selected($item->uuid === $currentRegisterUuid)
                        >{{ $item->code }} · {{ $item->name }}</option>
                    @endforeach
                </select>
            @else
                <span class="pos-topbar__value">{{ $register }}</span>
            @endif
        </div>
        <div class="pos-topbar__item">
            <span class="pos-topbar__label">กะ</span>
            <span class="pos-topbar__value">{{ $shift }}</span>
        </div>
    </div>

    <div class="pos-topbar__group">
        <div class="pos-topbar__item">
            <span class="pos-topbar__label">วันที่ / เวลา</span>
            <span class="pos-topbar__value" id="pos-clock" data-pos-clock>{{ now()->locale('th')->translatedFormat('j M Y · H:i') }}</span>
        </div>

        <div class="pos-topbar__status" title="สถานะเครือข่าย">
            <span class="pos-topbar__status-dot {{ $networkStatus !== 'online' ? 'pos-topbar__status-dot--offline' : '' }}"></span>
            <span>{{ $networkStatus === 'online' ? 'ออนไลน์' : 'ออฟไลน์' }}</span>
        </div>

        <div class="pos-topbar__status" title="สถานะซิงค์">
            <span class="pos-topbar__status-dot {{ $syncStatus === 'syncing' ? 'pos-topbar__status-dot--syncing' : '' }}"></span>
            <span>{{ match($syncStatus) { 'synced' => 'ซิงค์แล้ว', 'syncing' => 'กำลังซิงค์', default => $syncStatus } }}</span>
        </div>
    </div>
</header>
