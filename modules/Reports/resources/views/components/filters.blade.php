@props([
    'filter',
    'action',
    'channels' => [],
])

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">
    <div class="flex flex-wrap gap-2">
        @foreach (['7d' => '7 วัน', '30d' => '30 วัน', '90d' => '90 วัน'] as $key => $label)
            <x-admin.button
                :href="$action.'?'.http_build_query(array_merge($filter->toQuery(), ['range' => $key]))"
                :variant="$filter->range->preset === $key ? 'primary' : 'secondary'"
            >{{ $label }}</x-admin.button>
        @endforeach
    </div>

    <input type="hidden" name="range" value="custom">

    <label class="text-sm">
        <span class="mb-1 block text-muted">ตั้งแต่</span>
        <input type="date" name="from" value="{{ $filter->range->from->toDateString() }}" class="cf-input py-2">
    </label>

    <label class="text-sm">
        <span class="mb-1 block text-muted">ถึง</span>
        <input type="date" name="to" value="{{ $filter->range->to->toDateString() }}" class="cf-input py-2">
    </label>

    <label class="text-sm">
        <span class="mb-1 block text-muted">ช่องทาง</span>
        <select name="channel" class="cf-input py-2">
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected(($filter->channel ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <x-admin.button type="submit" variant="secondary">ใช้ตัวกรอง</x-admin.button>
</form>
