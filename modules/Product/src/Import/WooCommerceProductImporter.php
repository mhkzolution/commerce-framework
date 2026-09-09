<?php

declare(strict_types=1);

namespace Commerce\Product\Import;

use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\CreateAttributeSetData;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\DTO\CreateCollectionData;
use Commerce\Catalog\DTO\CreateTagData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Models\Tag;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Catalog\Services\AttributeSetService;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Catalog\Services\CollectionService;
use Commerce\Catalog\Services\TagService;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Media\Services\MediaUploadService;
use Commerce\Product\DTO\SaveProductWorkspaceData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductWorkspaceSaveService;
use Commerce\Product\Support\ProductCsvSellerResolver;
use Commerce\Product\Support\ProductPrice;
use Commerce\Support\SearchReservedParams;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;

final class WooCommerceProductImporter
{
    /** @var array<string, int> */
    private array $categoryCache = [];

    /** @var array<string, int> */
    private array $tagCache = [];

    /** @var array<string, int> */
    private array $collectionCache = [];

    /** @var array<string, int> */
    private array $attributeCache = [];

    /** @var array<string, string> */
    private array $brandCache = [];

    /** @var list<string> */
    private array $pendingAttributeWarnings = [];

    /** @var array<string, true> */
    private array $seenSkus = [];

    private ?int $attributeSetId = null;

    public function __construct(
        private readonly WooCommerceCsvReader $reader,
        private readonly ProductWorkspaceSaveService $workspaceSaveService,
        private readonly CategoryService $categoryService,
        private readonly TagService $tagService,
        private readonly CollectionService $collectionService,
        private readonly BrandService $brandService,
        private readonly AttributeService $attributeService,
        private readonly AttributeSetService $attributeSetService,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly MediaUploadService $mediaUploadService,
        private readonly ProductCsvSellerResolver $sellerResolver,
    ) {}

    /**
     * @return array{imported: int, created: int, updated: int, skipped: int, warnings: int, linked_images: int, errors: int}
     */
    public function import(
        string $path,
        OutputStyle $output,
        bool $dryRun = false,
        bool $skipExisting = true,
        ?int $limit = null,
        bool $linkImagesOnly = false,
    ): array {
        $this->resetFileState();

        $stats = [
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'warnings' => 0,
            'linked_images' => 0,
            'errors' => 0,
        ];

        if ($linkImagesOnly) {
            return $this->linkImagesForFile($path, $output, $dryRun, $limit, $stats);
        }

        if (! $dryRun) {
            $this->ensureAttributeSet($output);
        }

        $rows = iterator_to_array($this->reader->read($path));

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        [$standaloneRows, $parentRows, $variationRows] = $this->partitionImportRows($rows);
        $variationsByParent = $this->groupVariationsByParent($variationRows);

        foreach ($standaloneRows as $row) {
            $stats = $this->importCliStandaloneRow($row, $output, $dryRun, $skipExisting, $stats);
        }

        foreach ($parentRows as $parentRow) {
            $stats = $this->importCliVariableParentRow(
                $parentRow,
                $this->variationRowsForParent($parentRow, $variationsByParent),
                $output,
                $dryRun,
                $skipExisting,
                $stats,
            );
        }

        return $stats;
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, int>
     */
    private function importCliStandaloneRow(
        array $row,
        OutputStyle $output,
        bool $dryRun,
        bool $skipExisting,
        array $stats,
    ): array {
        $type = strtolower(trim($row['Type'] ?? 'simple'));

        if ($type !== '' && ! in_array($type, ['simple', 'variable'], true)) {
            $output->writeln('<comment>Skipping unsupported product type ['.$type.'] ID '.$this->rowId($row).'</comment>');
            $stats['skipped']++;

            return $stats;
        }

        $sku = $this->normalizeSku($row);

        if (! $this->claimSku($sku)) {
            return $this->recordCliDuplicate($stats, $output, $sku, $row);
        }

        if ($skipExisting && $this->productExists($row)) {
            $stats['skipped']++;
            $output->writeln('<comment>Skipping existing SKU '.($row['SKU'] ?? 'n/a').'</comment>');

            return $stats;
        }

        try {
            if ($dryRun) {
                $output->writeln('[dry-run] Would import: '.($row['Name'] ?? 'n/a').' (SKU: '.($row['SKU'] ?? 'n/a').')');
                $stats['imported']++;
                $stats['created']++;

                return $stats;
            }

            $existing = $this->findImportedProduct($row);
            $product = $this->upsertRow($row, $existing);
            $stats['warnings'] += $this->applyPendingAttributeWarningsToCli($output);
            $linked = $this->linkProductImages($product, $row);
            $stats['linked_images'] += $linked;
            $stats['imported']++;

            if ($existing === null) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            $output->writeln("Imported: {$product->name} ({$product->uuid})");
        } catch (\Throwable $exception) {
            $this->pullPendingAttributeWarnings();
            $stats['errors']++;
            $output->writeln('<error>Row '.$this->rowId($row).': '.$exception->getMessage().'</error>');
        }

        return $stats;
    }

    /**
     * @param  list<array<string, string>>  $variationRows
     * @param  array<string, int>  $stats
     * @return array<string, int>
     */
    private function importCliVariableParentRow(
        array $parentRow,
        array $variationRows,
        OutputStyle $output,
        bool $dryRun,
        bool $skipExisting,
        array $stats,
    ): array {
        $parentSku = $this->normalizeSku($parentRow);

        if (! $this->claimSku($parentSku)) {
            return $this->recordCliDuplicate($stats, $output, $parentSku, $parentRow);
        }

        $keptVariations = [];

        foreach ($variationRows as $row) {
            $sku = $this->normalizeSku($row);

            if (! $this->claimSku($sku)) {
                $stats = $this->recordCliDuplicate($stats, $output, $sku, $row);

                continue;
            }

            $keptVariations[] = $row;
        }

        if ($skipExisting && $this->findImportedProduct($parentRow) !== null) {
            $stats['skipped']++;
            $output->writeln('<comment>Skipping existing variable product '.$this->parentKey($parentRow).'</comment>');

            return $stats;
        }

        try {
            $label = $this->parentKey($parentRow);
            $variationCount = max(1, count($keptVariations));

            if ($dryRun) {
                $output->writeln('[dry-run] Would import variable product: '.($parentRow['Name'] ?? 'n/a')." ({$label}, {$variationCount} variation(s))");
                $stats['imported']++;
                $stats['created']++;

                return $stats;
            }

            $existing = $this->findImportedProduct($parentRow);
            $product = $this->upsertVariableRow($parentRow, $keptVariations, $existing);
            $stats['warnings'] += $this->applyPendingAttributeWarningsToCli($output);
            $linked = $this->linkProductImages($product, $parentRow);
            $stats['linked_images'] += $linked;
            $stats['imported']++;

            if ($existing === null) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            $output->writeln("Imported variable product: {$product->name} ({$product->uuid}, {$product->variants()->count()} variant(s))");
        } catch (\Throwable $exception) {
            $this->pullPendingAttributeWarnings();
            $stats['errors']++;
            $output->writeln('<error>Row '.$this->rowId($parentRow).': '.$exception->getMessage().'</error>');
        }

        return $stats;
    }

    public function importForAdmin(string $path): ProductCsvImportResult
    {
        $this->resetFileState();
        $result = new ProductCsvImportResult;
        $this->ensureAttributeSetSilently();

        $rows = iterator_to_array($this->reader->read($path));
        [$standaloneRows, $parentRows, $variationRows] = $this->partitionImportRows($rows);
        $variationsByParent = $this->groupVariationsByParent($variationRows);

        foreach ($standaloneRows as $row) {
            $result = $this->importStandaloneRow($row, $result);
        }

        foreach ($parentRows as $parentRow) {
            $result = $this->importVariableParentRow(
                $parentRow,
                $this->variationRowsForParent($parentRow, $variationsByParent),
                $result,
            );
        }

        return $result;
    }

    private function importStandaloneRow(array $row, ProductCsvImportResult $result): ProductCsvImportResult
    {
        $sku = $this->normalizeSku($row);

        if ($sku === '') {
            return $this->appendError($result, 'Row '.$this->rowId($row).': SKU is required.');
        }

        if (! $this->claimSku($sku)) {
            return $this->appendDuplicate($result, $sku, $row);
        }

        $type = strtolower(trim($row['Type'] ?? 'simple'));

        if ($type !== '' && ! in_array($type, ['simple', 'variable'], true)) {
            return $result->withSkipped(
                'Row '.$this->rowId($row).': unsupported product type ['.$type.'].',
            );
        }

        try {
            $existing = $this->findImportedProduct($row);

            if ($existing === null) {
                $product = $this->upsertRow($row);
                $result = $result->withCreated("Created: {$product->name} (SKU: {$sku})");
            } else {
                $product = $this->upsertRow($row, $existing);
                $result = $result->withUpdated("Updated: {$product->name} (SKU: {$sku})");
            }

            $result = $this->applyPendingAttributeWarningsToResult($result);

            return $result->withLinkedImages($product->media()->count());
        } catch (\Throwable $exception) {
            $this->pullPendingAttributeWarnings();

            return $this->appendError($result, 'Row '.$this->rowId($row).': '.$exception->getMessage());
        }
    }

    /**
     * @param  list<array<string, string>>  $variationRows
     */
    private function importVariableParentRow(
        array $parentRow,
        array $variationRows,
        ProductCsvImportResult $result,
    ): ProductCsvImportResult {
        $parentSku = $this->normalizeSku($parentRow);

        if ($parentSku !== '' && ! $this->claimSku($parentSku)) {
            return $this->appendDuplicate($result, $parentSku, $parentRow);
        }

        $keptVariations = [];

        foreach ($variationRows as $row) {
            $sku = $this->normalizeSku($row);

            if ($sku !== '' && ! $this->claimSku($sku)) {
                $result = $this->appendDuplicate($result, $sku, $row);

                continue;
            }

            $keptVariations[] = $row;
        }

        $variationRows = $keptVariations;

        try {
            $existing = $this->findImportedProduct($parentRow);
            $product = $this->upsertVariableRow($parentRow, $variationRows, $existing);
            $label = $this->parentKey($parentRow);

            $result = $existing === null
                ? $result->withCreated("Created variable product: {$product->name} ({$label})")
                : $result->withUpdated("Updated variable product: {$product->name} ({$label})");

            $result = $this->applyPendingAttributeWarningsToResult($result);

            return $result->withLinkedImages($product->media()->count());
        } catch (\Throwable $exception) {
            $this->pullPendingAttributeWarnings();

            return $this->appendError($result, 'Row '.$this->rowId($parentRow).': '.$exception->getMessage());
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function appendDuplicate(ProductCsvImportResult $result, string $sku, array $row): ProductCsvImportResult
    {
        return $result->withDuplicateSku(
            $sku,
            'Duplicate SKU '.$sku.' on row '.$this->rowId($row).'.',
        );
    }

    private function appendError(ProductCsvImportResult $result, string $message): ProductCsvImportResult
    {
        return $result->withError($message);
    }

    private function resetFileState(): void
    {
        $this->seenSkus = [];
        $this->pendingAttributeWarnings = [];
        $this->categoryCache = [];
        $this->tagCache = [];
        $this->collectionCache = [];
        $this->attributeCache = [];
        $this->brandCache = [];
        $this->attributeSetId = null;
    }

    private function claimSku(string $sku): bool
    {
        if ($sku === '') {
            return true;
        }

        if (isset($this->seenSkus[$sku])) {
            return false;
        }

        $this->seenSkus[$sku] = true;

        return true;
    }

    /**
     * @param  array<string, int>  $stats
     * @param  array<string, string>  $row
     * @return array<string, int>
     */
    private function recordCliDuplicate(array $stats, OutputStyle $output, string $sku, array $row): array
    {
        $stats['skipped']++;
        $stats['warnings']++;
        $output->writeln('<comment>Duplicate SKU '.$sku.' on row '.$this->rowId($row).'.</comment>');

        return $stats;
    }

    /**
     * @param  list<array<string, string>>  $variationRows
     * @return array<string, list<array<string, string>>>
     */
    private function groupVariationsByParent(array $variationRows): array
    {
        $variationsByParent = [];

        foreach ($variationRows as $row) {
            $variationsByParent[$this->variationParentKey($row)][] = $row;
        }

        return $variationsByParent;
    }

    /**
     * @param  array<string, string>  $parentRow
     * @param  array<string, list<array<string, string>>>  $variationsByParent
     * @return list<array<string, string>>
     */
    private function variationRowsForParent(array $parentRow, array $variationsByParent): array
    {
        $keys = array_values(array_unique(array_filter([
            $this->parentKey($parentRow),
            ($id = trim($parentRow['ID'] ?? '')) !== '' ? 'id:'.$id : null,
        ])));

        $rows = [];
        $seen = [];

        foreach ($keys as $key) {
            foreach ($variationsByParent[$key] ?? [] as $row) {
                $sku = $this->normalizeSku($row);
                $signature = $sku !== '' ? 'sku:'.$sku : 'row:'.$this->rowId($row);

                if (isset($seen[$signature])) {
                    continue;
                }

                $seen[$signature] = true;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function ensureAttributeSetSilently(): void
    {
        if ($this->attributeSetId !== null) {
            return;
        }

        $code = (string) config('product.import.woocommerce.attribute_set_code', 'woocommerce_default');
        $name = (string) config('product.import.woocommerce.attribute_set_name', 'WooCommerce Default');

        $set = AttributeSet::query()->where('code', $code)->first();

        if ($set === null) {
            $attributeIds = $this->ensureDefaultAttributes();
            $set = $this->attributeSetService->create(new CreateAttributeSetData(
                code: $code,
                name: $name,
                attributeIds: $attributeIds,
            ));
        }

        $this->attributeSetId = $set->id;

        foreach ($set->attributes as $attribute) {
            $this->attributeCache[$attribute->name] = $attribute->id;
        }
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, int>
     */
    private function linkImagesForFile(
        string $path,
        OutputStyle $output,
        bool $dryRun,
        ?int $limit,
        array $stats,
    ): array {
        $processed = 0;

        foreach ($this->reader->read($path) as $row) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $processed++;

            $product = $this->findImportedProduct($row);

            if ($product === null) {
                $stats['skipped']++;

                continue;
            }

            if ($dryRun) {
                $output->writeln("[dry-run] Would link images for: {$product->name}");
                $stats['linked_images']++;

                continue;
            }

            $linked = $this->linkProductImages($product, $row);
            $stats['linked_images'] += $linked;

            if ($linked > 0) {
                $output->writeln("Linked {$linked} image(s) for: {$product->name}");
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function upsertRow(array $row, ?Product $existing = null): Product
    {
        $attributeValues = $this->resolveAttributeValues($row);
        $imagePaths = $this->extractImagePaths($row['Images'] ?? '');
        $prices = $this->resolvePrices($row);
        $mediaUuids = $this->resolveMediaUuids($row);
        $name = $this->decodeText($row['Name'] ?? '') ?? 'Untitled product';
        $sku = $this->normalizeSku($row);
        $variant = $existing?->defaultVariant();
        $weightKg = ($row['Weight (kg)'] ?? '') !== '' ? (float) $row['Weight (kg)'] : null;
        $barcode = trim($row['GTIN, UPC, EAN, or ISBN'] ?? '');

        $workspaceData = new SaveProductWorkspaceData(
            name: $name,
            slug: $existing?->slug ?? $this->resolveSlug($row),
            description: $this->resolveDescription($row),
            status: ($row['Published'] ?? '') === '1' ? 'published' : 'draft',
            visibility: $this->resolveVisibility($row['Visibility in catalog'] ?? ''),
            brandUuid: $this->resolveBrandUuid($row['Brands'] ?? ''),
            sellerUuid: $this->resolveSellerUuid($row),
            attributeSetId: $this->attributeSetId,
            categoryIds: $this->resolveCategoryIds($row['Categories'] ?? ''),
            collectionIds: $this->resolveCollectionIds($row['Collections'] ?? ''),
            tagIds: $this->resolveTagIds($row['Tags'] ?? ''),
            mediaUuids: $mediaUuids,
            attributeValues: $attributeValues,
            variantOptions: [],
            variants: [[
                'uuid' => $variant?->uuid,
                'name' => $name,
                'sku' => $sku !== '' ? $sku : null,
                'barcode' => $barcode !== '' ? $barcode : null,
                'price' => (string) $prices['price'],
                'comparePrice' => $prices['compare_at_price'] !== null ? (string) $prices['compare_at_price'] : '',
                'cost' => '',
                'weight' => $weightKg !== null ? (string) ($weightKg * 1000) : '',
                'status' => 'active',
                'options' => [],
                'isDefault' => true,
                'onHand' => max(0, (int) ($row['Stock'] ?? 0)),
            ]],
            meta: [
                'wordpress_id' => (int) ($row['ID'] ?? 0),
                'wordpress_images' => $imagePaths,
                'wordpress_weight_kg' => $weightKg,
            ],
            type: 'simple',
        );

        $product = $existing === null
            ? $this->workspaceSaveService->create($workspaceData)
            : $this->workspaceSaveService->update($existing->uuid, $workspaceData);

        $this->syncStock($product, $row);

        return $product->fresh(['variants', 'media', 'categories', 'tags', 'attributeValues']);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function syncStock(Product $product, array $row): void
    {
        $variant = $product->defaultVariant();

        if ($variant === null) {
            return;
        }

        $stock = max(0, (int) ($row['Stock'] ?? 0));
        $this->inventoryService->setOnHand($variant->uuid, $stock, reason: 'CSV import');
    }

    /**
     * @return list<string>
     */
    private function resolveMediaUuids(array $row): array
    {
        $mediaUuids = [];

        foreach ($this->extractImageUrls($row['Images'] ?? '') as $url) {
            try {
                $media = $this->mediaUploadService->importFromUrl($url);
            } catch (\Throwable) {
                continue;
            }

            if (! in_array($media->uuid, $mediaUuids, true)) {
                $mediaUuids[] = $media->uuid;
            }
        }

        return $mediaUuids;
    }

    private function resolveBrandUuid(string $raw): ?string
    {
        $name = trim($raw);

        if ($name === '') {
            return null;
        }

        $name = trim(explode(',', $name)[0] ?? '');

        if ($name === '') {
            return null;
        }

        if (! isset($this->brandCache[$name])) {
            $brand = Brand::query()->where('name', $name)->first();

            if ($brand === null) {
                $brand = $this->brandService->create(new CreateBrandData(name: $name));
            }

            $this->brandCache[$name] = $brand->uuid;
        }

        return $this->brandCache[$name];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveSellerUuid(array $row): ?string
    {
        $raw = trim($row['Seller'] ?? $row['Meta: seller'] ?? '');

        return $this->sellerResolver->resolveUuid($raw);
    }

    private function resolveType(string $value): string
    {
        return match (strtolower(trim($value))) {
            'variable' => 'variable',
            default => 'simple',
        };
    }

    /**
     * @param  array<string, string>  $row
     */
    private function normalizeSku(array $row): string
    {
        return trim($row['SKU'] ?? '');
    }

    private function decodeText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function linkProductImages(Product $product, array $row): int
    {
        $disk = (string) config('product.import.woocommerce.wordpress_uploads_disk', 'wordpress_uploads');
        $paths = $product->meta['wordpress_images'] ?? $this->extractImagePaths($row['Images'] ?? '');
        $paths = array_values(array_unique($paths));
        $urlByPath = $this->mapImageUrlsByPath($row['Images'] ?? '');
        $linked = 0;
        $mediaUuids = [];

        foreach ($paths as $path) {
            $media = method_exists($this->mediaUploadService, 'registerExistingFile')
                ? $this->mediaUploadService->registerExistingFile(
                    $disk,
                    $path,
                    $urlByPath[$path] ?? null,
                )
                : null;

            if ($media === null || in_array($media->uuid, $mediaUuids, true)) {
                continue;
            }

            $mediaUuids[] = $media->uuid;
            $linked++;
        }

        if ($mediaUuids === []) {
            return 0;
        }

        $product->media()->delete();

        foreach (array_values($mediaUuids) as $position => $mediaUuid) {
            ProductMedia::query()->create([
                'product_id' => $product->id,
                'media_uuid' => $mediaUuid,
                'position' => $position,
                'is_primary' => $position === 0,
            ]);
        }

        return $linked;
    }

    private function ensureAttributeSet(OutputStyle $output): void
    {
        $code = (string) config('product.import.woocommerce.attribute_set_code', 'woocommerce_default');
        $name = (string) config('product.import.woocommerce.attribute_set_name', 'WooCommerce Default');

        $set = AttributeSet::query()->where('code', $code)->first();

        if ($set === null) {
            $attributeIds = $this->ensureDefaultAttributes();
            $set = $this->attributeSetService->create(new CreateAttributeSetData(
                code: $code,
                name: $name,
                attributeIds: $attributeIds,
            ));
            $output->writeln("Created attribute set: {$name}");
        }

        $this->attributeSetId = $set->id;

        foreach ($set->attributes as $attribute) {
            $this->attributeCache[$attribute->name] = $attribute->id;
        }
    }

    /**
     * @return list<int>
     */
    private function ensureDefaultAttributes(): array
    {
        $definitions = [
            ['name' => 'สี', 'code' => 'color'],
            ['name' => 'เพศ', 'code' => 'gender'],
            ['name' => 'Size (เสื้อ)', 'code' => 'size_top'],
            ['name' => 'Size (กางเกง)', 'code' => 'size_bottom'],
            ['name' => 'อายุ', 'code' => 'age'],
            ['name' => 'สภาพ', 'code' => 'condition'],
        ];

        $ids = [];

        foreach ($definitions as $definition) {
            $attribute = Attribute::query()->where('code', $definition['code'])->first();

            if ($attribute === null) {
                $attribute = $this->attributeService->create(new CreateAttributeData(
                    code: $definition['code'],
                    name: $definition['name'],
                    type: 'select',
                    isFilterable: true,
                    isVisible: true,
                ));
            }

            $this->attributeCache[$attribute->name] = $attribute->id;
            $ids[] = $attribute->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, string>  $row
     * @return array<int, string>
     */
    private function resolveAttributeValues(array $row): array
    {
        $values = [];

        for ($index = 1; $index <= 4; $index++) {
            $name = trim($row["Attribute {$index} name"] ?? '');

            if ($name === '') {
                continue;
            }

            $code = $this->attributeCode($name);

            if (in_array($code, SearchReservedParams::KEYS, true)) {
                $this->recordReservedAttributeSkip($row, $name, $code);

                continue;
            }

            $attributeId = $this->attributeCache[$name] ?? null;

            if ($attributeId === null) {
                $attribute = $this->attributeService->create(new CreateAttributeData(
                    code: $code,
                    name: $name,
                    type: 'select',
                    isFilterable: true,
                    isVisible: true,
                ));
                $attributeId = $attribute->id;
                $this->attributeCache[$name] = $attributeId;
            }

            $value = trim($row["Attribute {$index} value(s)"] ?? '');

            if ($value !== '') {
                $values[$attributeId] = $value;
            }
        }

        $condition = trim($row['Meta: condition'] ?? '');

        if ($condition !== '') {
            $conditionAttributeId = $this->attributeCache['สภาพ'] ?? null;

            if ($conditionAttributeId !== null) {
                $values[$conditionAttributeId] = $condition;
            }
        }

        return $values;
    }

    private function recordReservedAttributeSkip(array $row, string $name, string $code): void
    {
        $this->pendingAttributeWarnings[] = 'Row '.$this->rowId($row).': skipped reserved attribute column "'.$name.'" (code "'.$code.'").';
    }

    /**
     * @return list<string>
     */
    private function pullPendingAttributeWarnings(): array
    {
        $warnings = $this->pendingAttributeWarnings;
        $this->pendingAttributeWarnings = [];

        return $warnings;
    }

    private function applyPendingAttributeWarningsToCli(OutputStyle $output): int
    {
        $messages = $this->pullPendingAttributeWarnings();

        foreach ($messages as $message) {
            $output->writeln('<comment>'.$message.'</comment>');
        }

        return count($messages);
    }

    private function applyPendingAttributeWarningsToResult(ProductCsvImportResult $result): ProductCsvImportResult
    {
        foreach ($this->pullPendingAttributeWarnings() as $message) {
            $result = $result->withMessage($message);
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    private function resolveCategoryIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ids = [];

        foreach (preg_split('/\s*>\s*/', $raw) ?: [] as $segment) {
            foreach (explode(',', $segment) as $name) {
                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $ids[] = $this->categoryId($name);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<int>
     */
    private function resolveTagIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ids = [];

        foreach (explode(',', $raw) as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            if (! isset($this->tagCache[$name])) {
                $tag = Tag::query()->where('name', $name)->first();

                if ($tag === null) {
                    $tag = $this->tagService->create(new CreateTagData(name: $name));
                }

                $this->tagCache[$name] = $tag->id;
            }

            $ids[] = $this->tagCache[$name];
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function resolveCollectionIds(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $ids = [];

        foreach (explode(',', $raw) as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            if (! isset($this->collectionCache[$name])) {
                $collection = Collection::query()->where('name', $name)->first();

                if ($collection === null) {
                    $collection = $this->collectionService->create(new CreateCollectionData(name: $name));
                }

                $this->collectionCache[$name] = $collection->id;
            }

            $ids[] = $this->collectionCache[$name];
        }

        return array_values(array_unique($ids));
    }

    private function categoryId(string $name): int
    {
        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }

        $category = Category::query()->where('name', $name)->first();

        if ($category === null) {
            $category = $this->categoryService->create(new CreateCategoryData(name: $name));
        }

        $this->categoryCache[$name] = $category->id;

        return $category->id;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function productExists(array $row): bool
    {
        if ($this->findImportedProduct($row) !== null) {
            return true;
        }

        $sku = trim($row['SKU'] ?? '');

        if ($sku === '') {
            return false;
        }

        return ProductVariant::query()->where('sku', $sku)->exists();
    }

    /**
     * @param  array<string, string>  $row
     */
    private function findImportedProduct(array $row): ?Product
    {
        $wordpressId = (int) ($row['ID'] ?? 0);

        if ($wordpressId > 0) {
            $product = Product::query()
                ->where('meta->wordpress_id', $wordpressId)
                ->first();

            if ($product !== null) {
                return $product;
            }
        }

        $sku = trim($row['SKU'] ?? '');

        if ($sku === '') {
            return null;
        }

        $variant = ProductVariant::query()->where('sku', $sku)->with('product')->first();

        return $variant?->product;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{price: float, compare_at_price: ?float}
     */
    private function resolvePrices(array $row): array
    {
        $salePrice = ProductPrice::normalize($this->parsePrice($row['Sale price'] ?? ''));
        $regularPrice = ProductPrice::normalize($this->parsePrice($row['Regular price'] ?? ''));

        if ($salePrice <= 0 && $regularPrice <= 0) {
            return ['price' => 0.0, 'compare_at_price' => null];
        }

        $price = $salePrice > 0 ? $salePrice : $regularPrice;
        $compareAt = ($salePrice > 0 && $regularPrice > $salePrice) ? $regularPrice : null;

        return [
            'price' => $price,
            'compare_at_price' => $compareAt,
        ];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveDescription(array $row): ?string
    {
        $parts = array_filter([
            $this->cleanHtml($row['Short description'] ?? null),
            $this->cleanHtml($row['Description'] ?? null),
        ]);

        return $parts === [] ? null : implode("\n\n", $parts);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveSlug(array $row): ?string
    {
        $sku = trim($row['SKU'] ?? '');
        $base = Str::slug($row['Name']);

        if ($base === '' && $sku !== '') {
            return Str::slug($sku);
        }

        if ($sku !== '') {
            return $base.'-'.Str::slug($sku);
        }

        return $base !== '' ? $base : null;
    }

    private function resolveVisibility(string $value): string
    {
        return match (strtolower(trim($value))) {
            'hidden', 'private' => 'hidden',
            default => 'public',
        };
    }

    /**
     * @return array<string, string>
     */
    private function mapImageUrlsByPath(string $raw): array
    {
        $map = [];

        foreach ($this->extractImageUrls($raw) as $url) {
            $path = $this->extractUploadPath($url);

            if ($path !== null && ! isset($map[$path])) {
                $map[$path] = $url;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function extractImagePaths(string $raw): array
    {
        $paths = [];

        foreach ($this->extractImageUrls($raw) as $url) {
            $path = $this->extractUploadPath($url);

            if ($path !== null && ! in_array($path, $paths, true)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function extractImageUrls(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function extractUploadPath(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return null;
        }

        if (preg_match('#/wp-content/uploads/(.+)$#i', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function parsePrice(string $value): float
    {
        $value = str_replace([',', ' '], '', trim($value));

        if ($value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function cleanHtml(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function attributeCode(string $name): string
    {
        $slug = Str::slug($name, '_');

        if ($slug !== '') {
            return $slug;
        }

        return 'attr_'.substr(md5($name), 0, 12);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function rowId(array $row): string
    {
        $id = trim($row['ID'] ?? '');

        if ($id !== '') {
            return $id;
        }

        $sku = trim($row['SKU'] ?? '');

        if ($sku !== '') {
            return "SKU {$sku}";
        }

        return 'unknown';
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{0: list<array<string, string>>, 1: list<array<string, string>>, 2: list<array<string, string>>}
     */
    private function partitionImportRows(array $rows): array
    {
        $standalone = [];
        $parents = [];
        $variations = [];

        foreach ($rows as $row) {
            $type = strtolower(trim($row['Type'] ?? 'simple'));

            if (in_array($type, ['variation', 'variant'], true)) {
                $variations[] = $row;

                continue;
            }

            if ($type === 'variable' && trim($row['Parent'] ?? '') === '') {
                $parents[] = $row;

                continue;
            }

            $standalone[] = $row;
        }

        return [$standalone, $parents, $variations];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function parentKey(array $row): string
    {
        $sku = $this->normalizeSku($row);

        if ($sku !== '') {
            return 'sku:'.$sku;
        }

        $id = trim($row['ID'] ?? '');

        return $id !== '' ? 'id:'.$id : 'name:'.trim($row['Name'] ?? '');
    }

    /**
     * @param  array<string, string>  $row
     */
    private function variationParentKey(array $row): string
    {
        $parent = trim($row['Parent'] ?? '');

        if ($parent === '') {
            return '';
        }

        if (str_starts_with(strtolower($parent), 'id:')) {
            return strtolower($parent);
        }

        if (ctype_digit($parent)) {
            return 'id:'.$parent;
        }

        return 'sku:'.$parent;
    }

    /**
     * @param  list<array<string, string>>  $variationRows
     */
    private function upsertVariableRow(array $parentRow, array $variationRows, ?Product $existing = null): Product
    {
        $attributeValues = $this->resolveAttributeValues($parentRow);
        $imagePaths = $this->extractImagePaths($parentRow['Images'] ?? '');
        $mediaUuids = $this->resolveMediaUuids($parentRow);
        $name = $this->decodeText($parentRow['Name'] ?? '') ?? 'Untitled product';
        $variantOptions = $this->buildVariantOptions($parentRow);
        $rows = $variationRows !== [] ? $variationRows : [$parentRow];
        $existingVariants = $existing?->variants->keyBy('sku') ?? collect();
        $variants = [];

        foreach (array_values($rows) as $index => $row) {
            $sku = $this->normalizeSku($row);
            $prices = $this->resolvePrices($row);
            $existingVariant = $sku !== ''
                ? $existingVariants->get($sku)
                : ($index === 0 ? $existing?->defaultVariant() : null);
            $weightKg = ($row['Weight (kg)'] ?? '') !== '' ? (float) $row['Weight (kg)'] : null;
            $barcode = trim($row['GTIN, UPC, EAN, or ISBN'] ?? '');

            $variants[] = [
                'uuid' => $existingVariant?->uuid,
                'name' => $this->decodeText($row['Name'] ?? '') ?: $name,
                'sku' => $sku !== '' ? $sku : null,
                'barcode' => $barcode !== '' ? $barcode : null,
                'price' => (string) $prices['price'],
                'comparePrice' => $prices['compare_at_price'] !== null ? (string) $prices['compare_at_price'] : '',
                'cost' => '',
                'weight' => $weightKg !== null ? (string) ($weightKg * 1000) : '',
                'status' => 'active',
                'options' => $this->buildVariantOptionsMap($row, $variantOptions),
                'isDefault' => $index === 0,
                'onHand' => max(0, (int) ($row['Stock'] ?? 0)),
            ];
        }

        $workspaceData = new SaveProductWorkspaceData(
            name: $name,
            slug: $existing?->slug ?? $this->resolveSlug($parentRow),
            description: $this->resolveDescription($parentRow),
            status: ($parentRow['Published'] ?? '') === '1' ? 'published' : 'draft',
            visibility: $this->resolveVisibility($parentRow['Visibility in catalog'] ?? ''),
            brandUuid: $this->resolveBrandUuid($parentRow['Brands'] ?? ''),
            sellerUuid: $this->resolveSellerUuid($parentRow),
            attributeSetId: $this->attributeSetId,
            categoryIds: $this->resolveCategoryIds($parentRow['Categories'] ?? ''),
            collectionIds: $this->resolveCollectionIds($parentRow['Collections'] ?? ''),
            tagIds: $this->resolveTagIds($parentRow['Tags'] ?? ''),
            mediaUuids: $mediaUuids,
            attributeValues: $attributeValues,
            variantOptions: $variantOptions,
            variants: $variants,
            meta: [
                'wordpress_id' => (int) ($parentRow['ID'] ?? 0),
                'wordpress_images' => $imagePaths,
                'wordpress_weight_kg' => ($parentRow['Weight (kg)'] ?? '') !== '' ? (float) $parentRow['Weight (kg)'] : null,
            ],
            type: 'variable',
        );

        $product = $existing === null
            ? $this->workspaceSaveService->create($workspaceData)
            : $this->workspaceSaveService->update($existing->uuid, $workspaceData);

        $product = $product->fresh(['variants']);

        foreach ($rows as $row) {
            $sku = $this->normalizeSku($row);

            if ($sku === '') {
                continue;
            }

            $variant = $product->variants->firstWhere('sku', $sku);

            if ($variant === null) {
                continue;
            }

            $stock = max(0, (int) ($row['Stock'] ?? 0));
            $this->inventoryService->setOnHand($variant->uuid, $stock, reason: 'CSV import');
        }

        return $product->fresh(['variants', 'media', 'categories', 'tags', 'attributeValues']);
    }

    /**
     * @param  array<string, string>  $row
     * @return list<array{id: string, name: string, values: list<string>}>
     */
    private function buildVariantOptions(array $row): array
    {
        $options = [];

        for ($index = 1; $index <= 4; $index++) {
            $name = trim($row["Attribute {$index} name"] ?? '');
            $valuesRaw = trim($row["Attribute {$index} value(s)"] ?? '');

            if ($name === '' || $valuesRaw === '') {
                continue;
            }

            $values = array_values(array_filter(array_map(
                'trim',
                preg_split('/\s*,\s*/', $valuesRaw) ?: [],
            )));

            if ($values === []) {
                continue;
            }

            $options[] = [
                'id' => 'opt_'.Str::slug($name, '_'),
                'name' => $name,
                'values' => $values,
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<array{id: string, name: string, values: list<string>}>  $variantOptions
     * @return array<string, string>
     */
    private function buildVariantOptionsMap(array $row, array $variantOptions): array
    {
        $map = [];

        foreach ($variantOptions as $option) {
            for ($index = 1; $index <= 4; $index++) {
                $attributeName = trim($row["Attribute {$index} name"] ?? '');

                if (strtolower($attributeName) !== strtolower($option['name'])) {
                    continue;
                }

                $value = trim($row["Attribute {$index} value(s)"] ?? '');

                if ($value !== '') {
                    $map[strtolower($option['name'])] = $value;
                }
            }
        }

        return $map;
    }
}
