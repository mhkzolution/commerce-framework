@extends('layouts.admin')

@section('title', 'Import products')

@section('page')
  @php
      $importResult = session('import_result');
  @endphp

  <x-admin.page title="Import products" description="Import or update products from a WooCommerce-style CSV export.">
    <x-slot:breadcrumb>
      <x-admin.breadcrumb :items="[
          ['label' => 'Catalog'],
          ['label' => 'Products', 'url' => route('admin.products.index')],
          ['label' => 'Import', 'active' => true],
      ]" />
    </x-slot:breadcrumb>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
      <x-admin.card title="Upload CSV">
        <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data" class="space-y-4">
          @csrf

          <div>
            <label for="csv" class="mb-2 block text-sm font-medium text-text">CSV file</label>
            <input
              id="csv"
              name="csv"
              type="file"
              accept=".csv,text/csv"
              class="cf-input w-full"
              required
            >
            @error('csv')
              <p class="mt-2 text-sm text-danger">{{ $message }}</p>
            @enderror
          </div>

          <div class="flex flex-wrap gap-2">
            <x-admin.button type="submit" variant="primary">Import products</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.products.index')">Back to products</x-admin.button>
          </div>
        </form>
      </x-admin.card>

      <x-admin.card title="CSV format">
        <div class="space-y-3 text-sm text-muted">
          <p>Use a WooCommerce product export with columns such as SKU, Name, Type, Regular price, Sale price, Categories, Tags, Brands, Seller, Images, and Attribute columns.</p>
          <p>Existing products are matched by SKU and updated. Rows with duplicate SKUs in the same file are skipped.</p>
          <p>Imported fields include name, images, SKU, price, type, attributes, tags, brand, seller, and categories. Seller can be a name, slug, or UUID; new sellers are created automatically when needed.</p>
        </div>
      </x-admin.card>
    </div>

    @if (is_array($importResult))
      <x-admin.card title="Import result" class="mt-6">
        <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <div class="rounded-lg border border-border px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-muted">Created</div>
            <div class="text-2xl font-semibold text-text">{{ $importResult['created'] ?? 0 }}</div>
          </div>
          <div class="rounded-lg border border-border px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-muted">Updated</div>
            <div class="text-2xl font-semibold text-text">{{ $importResult['updated'] ?? 0 }}</div>
          </div>
          <div class="rounded-lg border border-border px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-muted">Duplicates</div>
            <div class="text-2xl font-semibold text-text">{{ $importResult['duplicates'] ?? 0 }}</div>
          </div>
          <div class="rounded-lg border border-border px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-muted">Skipped</div>
            <div class="text-2xl font-semibold text-text">{{ $importResult['skipped'] ?? 0 }}</div>
          </div>
          <div class="rounded-lg border border-border px-4 py-3">
            <div class="text-xs uppercase tracking-wide text-muted">Images linked</div>
            <div class="text-2xl font-semibold text-text">{{ $importResult['linked_images'] ?? 0 }}</div>
          </div>
        </div>

        @if (! empty($importResult['duplicate_skus']))
          <div class="mb-4 rounded-lg border border-warning/30 bg-warning/5 px-4 py-3 text-sm text-text">
            <div class="font-medium">Duplicate SKUs in file</div>
            <p class="mt-1 text-muted">{{ implode(', ', $importResult['duplicate_skus']) }}</p>
          </div>
        @endif

        @if (! empty($importResult['errors']))
          <div class="mb-4 rounded-lg border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-text">
            <div class="font-medium">Errors</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-muted">
              @foreach ($importResult['errors'] as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @if (! empty($importResult['messages']))
          <div class="max-h-80 overflow-y-auto rounded-lg border border-border">
            <ul class="divide-y divide-border text-sm">
              @foreach ($importResult['messages'] as $message)
                <li class="px-4 py-2 text-muted">{{ $message }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </x-admin.card>
    @endif
  </x-admin.page>
@endsection
