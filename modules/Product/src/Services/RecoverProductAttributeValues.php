<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Support\AttributeTokenNormalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class RecoverProductAttributeValues
{
    /** @var array<string, string> */
    private const COVERED_ATTRIBUTES = [
        'สี' => 'color',
        'เพศ' => 'gender',
        'Size (เสื้อ)' => 'size_top',
        'Size (กางเกง)' => 'size_bottom',
        'อายุ' => 'age',
        'สภาพ' => 'condition',
        'ภาษา' => 'attr_0ebb640bb9a7',
        'Size (รองเท้า)' => 'size',
    ];

    private const NORMALIZED_CODES = ['size_top', 'size_bottom', 'size', 'age'];

    private const SET_CODE = 'woocommerce_default';

    public function __construct(
        private readonly AttributeTokenNormalizer $normalizer,
        private readonly ProductAttributeValueLinker $linker,
        private readonly ProductAttributeSetSync $attributeSetSync,
        private readonly ProductSearchIndexer $indexer,
    ) {}

    /**
     * @return array{attributes: list<array<string, mixed>>, rows: int, collapses: int}
     */
    public function audit(): array
    {
        $reports = [];
        $totalRows = 0;
        $totalCollapses = 0;

        foreach ($this->coveredAttributes()->whereIn('code', self::NORMALIZED_CODES) as $attribute) {
            $rows = ProductAttributeValue::query()
                ->where('attribute_id', $attribute->id)
                ->whereNull('attribute_value_id')
                ->get(['value']);
            $rawTokens = [];
            $canonicalTokens = [];
            $canonicalCounts = [];

            foreach ($rows as $row) {
                foreach ($this->rawTokens((string) $row->value) as $rawToken) {
                    $rawTokens[mb_strtolower($rawToken)] = $rawToken;
                    foreach ($this->normalizer->tokens($rawToken, $attribute->code) as $canonical) {
                        $folded = mb_strtolower($canonical);
                        $canonicalTokens[$folded] = $canonical;
                        $canonicalCounts[$folded] = ($canonicalCounts[$folded] ?? 0) + 1;
                    }
                }
            }

            $collapses = max(0, count($rawTokens) - count($canonicalTokens));
            $reports[] = [
                'name' => $attribute->name,
                'code' => $attribute->code,
                'rows' => $rows->count(),
                'raw_tokens' => array_values($rawTokens),
                'canonical_tokens' => array_values($canonicalTokens),
                'canonical_counts' => $canonicalCounts,
                'collapses' => $collapses,
            ];
            $totalRows += $rows->count();
            $totalCollapses += $collapses;
        }

        return [
            'attributes' => $reports,
            'rows' => $totalRows,
            'collapses' => $totalCollapses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(bool $dryRun = false, bool $force = false): array
    {
        $attributes = $this->coveredAttributes();
        $this->assertRecoveryPrerequisites($attributes);
        $attributeIds = $attributes->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $set = AttributeSet::query()->where('code', self::SET_CODE)->firstOrFail();
        $touchedProductIds = ProductAttributeValue::query()
            ->whereIn('attribute_id', $attributeIds)
            ->distinct()
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id);

        $touchedProductIds = $touchedProductIds
            ->merge(Product::query()->where('attribute_set_id', $set->id)->pluck('id'))
            ->unique()
            ->values();

        $baseSuffix = now()->format('Ymd');
        if ($this->isAlreadyApplied($attributes, $set)) {
            return [
                'suffix' => $this->latestSuffix($baseSuffix) ?? $baseSuffix,
                'dry_run' => $dryRun,
                'already_applied' => true,
                'touched_products' => 0,
                'audit' => $this->audit(),
            ];
        }

        $suffix = $this->availableSuffix($baseSuffix, $force, $dryRun);
        $result = [
            'suffix' => $suffix,
            'dry_run' => $dryRun,
            'already_applied' => false,
            'touched_products' => $touchedProductIds->count(),
            'covered_attributes' => $attributes->count(),
            'text_rows' => ProductAttributeValue::query()
                ->whereIn('attribute_id', $attributeIds)
                ->whereNull('attribute_value_id')
                ->whereNull('product_variant_id')
                ->count(),
            'audit' => $this->audit(),
        ];

        if ($dryRun) {
            return $result;
        }

        $this->createBackups($suffix, $attributes, $set);
        $this->normalizeSizeAndAgeRows($attributes);
        $this->linkTextRows($attributes);

        Attribute::query()->whereIn('id', $attributeIds)->update(['type' => 'select']);
        $this->attachMissingSetAttributes($set, $attributes);

        Product::query()
            ->where('attribute_set_id', $set->id)
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $this->attributeSetSync->syncProductAttributesFromSet($product);
                }
            });

        Product::query()
            ->whereIn('id', $touchedProductIds->all())
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $this->indexer->index($product);
                }
            });

        return $result;
    }

    public function restore(string $suffix): void
    {
        $this->assertValidSuffix($suffix);
        $tables = $this->backupTables($suffix);
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Recovery backup table [{$table}] does not exist.");
            }
        }

        $attributeBackup = DB::table($tables['attributes'])->get();
        $coveredAttributeIds = $attributeBackup->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $watermark = (int) ($attributeBackup->max('attribute_value_watermark') ?? 0);
        $pivotBackup = DB::table($tables['set'])->get();
        $attributeSetId = (int) ($pivotBackup->first()?->attribute_set_id ?? 0);
        $productAttributeBackup = $pivotBackup->where('record_type', 'product_attribute');
        $backedProductIds = $productAttributeBackup
            ->pluck('product_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->all();

        DB::transaction(function () use (
            $tables,
            $attributeBackup,
            $coveredAttributeIds,
            $watermark,
            $pivotBackup,
            $attributeSetId,
            $productAttributeBackup,
            $backedProductIds,
        ): void {
            DB::table('product_attribute_values')->delete();
            $this->insertRows('product_attribute_values', DB::table($tables['pav'])->get());

            foreach ($attributeBackup as $row) {
                DB::table('attributes')->where('id', $row->id)->update(['type' => $row->type]);
            }

            if ($attributeSetId > 0) {
                DB::table('attribute_set_attributes')->where('attribute_set_id', $attributeSetId)->delete();
                foreach ($pivotBackup->where('record_type', 'pivot') as $row) {
                    DB::table('attribute_set_attributes')->insert([
                        'id' => $row->id,
                        'attribute_set_id' => $row->attribute_set_id,
                        'attribute_id' => $row->attribute_id,
                        'position' => $row->position,
                        'is_required' => $row->is_required,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            }

            if ($backedProductIds !== []) {
                DB::table('product_attributes')->whereIn('product_id', $backedProductIds)->delete();
                foreach ($productAttributeBackup->whereNotNull('attribute_id') as $row) {
                    DB::table('product_attributes')->insert([
                        'id' => $row->id,
                        'product_id' => $row->product_id,
                        'attribute_id' => $row->attribute_id,
                        'used_for_variations' => $row->used_for_variations,
                        'position' => $row->position,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            }

            AttributeValue::query()
                ->whereIn('attribute_id', $coveredAttributeIds)
                ->where('id', '>', $watermark)
                ->delete();
        });

        $this->indexer->reindexAll();
    }

    /**
     * @return Collection<int, Attribute>
     */
    private function coveredAttributes(): Collection
    {
        return Attribute::query()
            ->whereIn('name', array_keys(self::COVERED_ATTRIBUTES))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function assertRecoveryPrerequisites(Collection $attributes): void
    {
        $attributesByName = $attributes->keyBy('name');
        $missing = [];

        foreach (self::COVERED_ATTRIBUTES as $name => $code) {
            $attribute = $attributesByName->get($name);
            if ($attribute === null || $attribute->code !== $code) {
                $missing[] = "{$name} ({$code})";
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Attribute recovery requires all covered attributes; missing or mismatched: '.implode(', ', $missing).'.',
            );
        }

        if (! AttributeSet::query()->where('code', self::SET_CODE)->exists()) {
            throw new RuntimeException('Attribute recovery requires attribute set ['.self::SET_CODE.'].');
        }
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function normalizeSizeAndAgeRows(Collection $attributes): void
    {
        $attributesById = $attributes
            ->whereIn('code', self::NORMALIZED_CODES)
            ->keyBy('id');

        ProductAttributeValue::query()
            ->whereIn('attribute_id', $attributesById->keys())
            ->whereNull('attribute_value_id')
            ->whereNull('product_variant_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($attributesById): void {
                foreach ($rows as $row) {
                    $tokens = $this->normalizer->tokens(
                        (string) $row->value,
                        $attributesById->get($row->attribute_id)->code,
                    );
                    if (count($tokens) === 1 && $row->value !== $tokens[0]) {
                        $row->update(['value' => $tokens[0]]);
                    }
                }
            });
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function linkTextRows(Collection $attributes): void
    {
        $attributeIds = $attributes->pluck('id')->all();
        $attributesById = $attributes->keyBy('id');
        ProductAttributeValue::query()
            ->whereIn('attribute_id', $attributeIds)
            ->whereNull('attribute_value_id')
            ->whereNull('product_variant_id')
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->chunkById(100, function ($productRows) use ($attributeIds, $attributesById): void {
                foreach ($productRows as $productRow) {
                    DB::transaction(function () use ($productRow, $attributeIds, $attributesById): void {
                        $product = Product::query()->findOrFail($productRow->product_id);
                        $values = ProductAttributeValue::query()
                            ->where('product_attribute_values.product_id', $product->id)
                            ->whereIn('product_attribute_values.attribute_id', $attributeIds)
                            ->whereNull('product_attribute_values.product_variant_id')
                            ->leftJoin(
                                'attribute_values',
                                'attribute_values.id',
                                '=',
                                'product_attribute_values.attribute_value_id',
                            )
                            ->get([
                                'product_attribute_values.attribute_id',
                                'product_attribute_values.attribute_value_id',
                                'product_attribute_values.value',
                                'attribute_values.label as linked_label',
                            ])
                            ->groupBy('attribute_id')
                            ->map(function ($rows, $attributeId) use ($attributesById): array {
                                $tokens = [];
                                foreach ($rows as $row) {
                                    if ($row->attribute_value_id !== null && $row->linked_label !== null) {
                                        $tokens[] = (string) $row->linked_label;

                                        continue;
                                    }

                                    if ($row->attribute_value_id === null) {
                                        foreach ($this->normalizer->tokens(
                                            (string) $row->value,
                                            $attributesById->get((int) $attributeId)->code,
                                        ) as $token) {
                                            $tokens[] = $token;
                                        }
                                    }
                                }

                                return $tokens;
                            })
                            ->filter(static fn (array $tokens): bool => $tokens !== [])
                            ->all();

                        if ($values !== []) {
                            $this->linker->syncProductLevel(
                                $product,
                                $values,
                                replaceUnusedAttributes: false,
                            );
                        }
                    });
                }
            }, 'product_id', 'product_id');
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function createBackups(
        string $suffix,
        Collection $attributes,
        ?AttributeSet $set,
    ): void {
        $tables = $this->backupTables($suffix);
        $watermark = (int) AttributeValue::query()->max('id');

        Schema::create($tables['pav'], function (Blueprint $table): void {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('attribute_value_id')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        $this->insertRows($tables['pav'], DB::table('product_attribute_values')->get());

        Schema::create($tables['attributes'], function (Blueprint $table): void {
            $table->unsignedBigInteger('id');
            $table->string('type', 30);
            $table->unsignedBigInteger('attribute_value_watermark')->default(0);
        });
        foreach ($attributes as $attribute) {
            DB::table($tables['attributes'])->insert([
                'id' => $attribute->id,
                'type' => $attribute->type,
                'attribute_value_watermark' => $watermark,
            ]);
        }

        Schema::create($tables['set'], function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->nullable();
            $table->string('record_type', 30);
            $table->unsignedBigInteger('attribute_set_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->boolean('is_required')->nullable();
            $table->boolean('used_for_variations')->nullable();
            $table->timestamps();
        });
        if ($set !== null) {
            DB::table($tables['set'])->insert([
                'record_type' => 'set',
                'attribute_set_id' => $set->id,
            ]);
            foreach (DB::table('attribute_set_attributes')->where('attribute_set_id', $set->id)->get() as $row) {
                DB::table($tables['set'])->insert([
                    'id' => $row->id,
                    'record_type' => 'pivot',
                    'attribute_set_id' => $row->attribute_set_id,
                    'attribute_id' => $row->attribute_id,
                    'position' => $row->position,
                    'is_required' => $row->is_required,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            foreach (Product::query()->where('attribute_set_id', $set->id)->pluck('id') as $productId) {
                DB::table($tables['set'])->insert([
                    'record_type' => 'product_attribute',
                    'attribute_set_id' => $set->id,
                    'product_id' => $productId,
                ]);
            }
            foreach (DB::table('product_attributes')
                ->whereIn('product_id', Product::query()->where('attribute_set_id', $set->id)->select('id'))
                ->get() as $row) {
                DB::table($tables['set'])->insert([
                    'id' => $row->id,
                    'record_type' => 'product_attribute',
                    'attribute_set_id' => $set->id,
                    'product_id' => $row->product_id,
                    'attribute_id' => $row->attribute_id,
                    'position' => $row->position,
                    'used_for_variations' => $row->used_for_variations,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::create($tables['meta'], function (Blueprint $table): void {
            $table->string('suffix', 30)->primary();
            $table->timestamp('created_at');
        });
        DB::table($tables['meta'])->insert([
            'suffix' => $suffix,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function attachMissingSetAttributes(?AttributeSet $set, Collection $attributes): void
    {
        if ($set === null) {
            return;
        }

        $position = (int) DB::table('attribute_set_attributes')
            ->where('attribute_set_id', $set->id)
            ->max('position');

        foreach (['ภาษา', 'Size (รองเท้า)'] as $name) {
            $attribute = $attributes->firstWhere('name', $name);
            if ($attribute === null) {
                continue;
            }
            $exists = DB::table('attribute_set_attributes')
                ->where('attribute_set_id', $set->id)
                ->where('attribute_id', $attribute->id)
                ->exists();
            if (! $exists) {
                $position++;
                $set->attributes()->attach($attribute->id, [
                    'position' => $position,
                    'is_required' => false,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Attribute>  $attributes
     */
    private function isAlreadyApplied(Collection $attributes, ?AttributeSet $set): bool
    {
        if ($attributes->count() !== count(self::COVERED_ATTRIBUTES)
            || $attributes->contains(static fn (Attribute $attribute): bool => $attribute->type !== 'select')) {
            return false;
        }

        if (ProductAttributeValue::query()
            ->whereIn('attribute_id', $attributes->pluck('id'))
            ->whereNull('attribute_value_id')
            ->whereNull('product_variant_id')
            ->exists()) {
            return false;
        }

        if ($set === null) {
            return true;
        }

        $requiredIds = $attributes->whereIn('name', ['ภาษา', 'Size (รองเท้า)'])->pluck('id');

        return DB::table('attribute_set_attributes')
            ->where('attribute_set_id', $set->id)
            ->whereIn('attribute_id', $requiredIds)
            ->count() === $requiredIds->count();
    }

    private function availableSuffix(string $baseSuffix, bool $force, bool $dryRun): string
    {
        $tables = $this->backupTables($baseSuffix);
        if (! Schema::hasTable($tables['pav'])) {
            return $baseSuffix;
        }

        if (! $force && ! $dryRun) {
            throw new RuntimeException(
                "Recovery backup [{$tables['pav']}] already exists; rerun with --force for a new suffix.",
            );
        }

        $sequence = 1;
        do {
            $suffix = $baseSuffix.'_'.$sequence;
            $sequence++;
        } while (Schema::hasTable($this->backupTables($suffix)['pav']));

        return $suffix;
    }

    private function latestSuffix(string $baseSuffix): ?string
    {
        $latest = null;
        $sequence = 0;

        do {
            $suffix = $sequence === 0 ? $baseSuffix : $baseSuffix.'_'.$sequence;
            $tables = $this->backupTables($suffix);
            if (! Schema::hasTable($tables['pav'])) {
                break;
            }

            $latest = $suffix;
            $sequence++;
        } while (true);

        if ($latest !== null) {
            return $latest;
        }

        return null;
    }

    /**
     * @return array{pav: string, attributes: string, set: string, meta: string}
     */
    private function backupTables(string $suffix): array
    {
        return [
            'pav' => '_bak_product_attribute_values_attr_recovery_'.$suffix,
            'attributes' => '_bak_attributes_attr_recovery_'.$suffix,
            'set' => '_bak_attribute_set_attributes_attr_recovery_'.$suffix,
            'meta' => '_bak_attr_recovery_meta_'.$suffix,
        ];
    }

    private function assertValidSuffix(string $suffix): void
    {
        if (preg_match('/^\d{8}(?:_\d+)?$/', $suffix) !== 1) {
            throw new RuntimeException('Invalid recovery backup suffix.');
        }
    }

    /**
     * @return list<string>
     */
    private function rawTokens(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\s*[,，]\s*/u', $raw) ?: []),
            static fn (string $token): bool => $token !== '',
        ));
    }

    /**
     * @param  iterable<int, object>  $rows
     * @param  list<string>  $except
     */
    private function insertRows(string $table, iterable $rows, array $except = []): void
    {
        foreach ($rows as $row) {
            $values = (array) $row;
            foreach ($except as $column) {
                unset($values[$column]);
            }
            DB::table($table)->insert($values);
        }
    }
}
