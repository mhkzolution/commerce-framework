@props([
    'action',
    'method' => 'POST',
    'product' => null,
    'mode' => 'create',
    'initialState' => [],
])

@php
    $workspaceId = 'product-workspace-' . ($product?->uuid ?? uniqid());
@endphp

<div
    id="{{ $workspaceId }}"
    class="cf-product-workspace"
    data-product-workspace
    data-workspace-mode="{{ $mode }}"
>
    @if ($mode === 'edit' && $product)
        <form id="{{ $workspaceId }}-publish" method="POST" action="{{ route('admin.products.publish', $product) }}" class="hidden" hidden>
            @csrf
        </form>
        <form id="{{ $workspaceId }}-archive" method="POST" action="{{ route('admin.products.archive', $product) }}" class="hidden" hidden>
            @csrf
        </form>
    @endif

    <x-admin.form.shell
        :action="$action"
        method="POST"
        class="cf-product-workspace__form"
        data-product-workspace-form
    >
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif

        <input type="hidden" name="workspace_payload" value="" data-workspace-payload>

        {{ $header ?? '' }}

        <div class="cf-product-workspace__body">
            {{ $tabs ?? '' }}
        </div>

        <x-product::workspace.save-bar />
    </x-admin.form.shell>

    <script type="application/json" data-product-workspace-state>
        @json($initialState)
    </script>
</div>
