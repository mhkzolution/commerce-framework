<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Product\DTO\SaveProductWorkspaceData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\Http\Requests\StoreProductRequest;
use Commerce\Product\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Arr;

final class WorkspacePayload
{
    public static function fromRequest(StoreProductRequest|UpdateProductRequest $request): SaveProductWorkspaceData
    {
        return self::fromArray([
            ...$request->validated(),
            'workspace_payload' => $request->input('workspace_payload'),
            'attributes' => $request->input('attributes', []),
            'seo' => $request->input('seo', []),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): SaveProductWorkspaceData
    {
        $rawPayload = $input['workspace_payload'] ?? null;
        $payload = is_array($rawPayload) ? $rawPayload : self::decode($rawPayload);
        $product = is_array($payload['product'] ?? null) ? $payload['product'] : [];
        $seoInput = is_array($input['seo'] ?? null) ? $input['seo'] : [];

        $meta = array_filter([
            'external_id' => $input['meta']['external_id'] ?? $input['meta.external_id'] ?? null,
            'notes' => $input['meta']['notes'] ?? $input['meta.notes'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $customJson = $input['meta']['custom_json'] ?? $input['meta.custom_json'] ?? null;

        if (is_string($customJson) && trim($customJson) !== '') {
            $decoded = json_decode($customJson, true);

            if (! is_array($decoded)) {
                throw new DomainException('Custom meta JSON must be a valid JSON object.');
            }

            $meta['custom'] = $decoded;
        }

        return new SaveProductWorkspaceData(
            name: (string) ($input['name'] ?? Arr::get($product, 'name', '')),
            slug: $input['slug'] ?? Arr::get($product, 'slug'),
            description: $input['description'] ?? Arr::get($product, 'description'),
            status: (string) ($input['status'] ?? Arr::get($product, 'status', 'draft')),
            visibility: (string) ($input['visibility'] ?? Arr::get($product, 'visibility', 'public')),
            brandUuid: self::resolveUuidField($input, $product, 'brand_uuid', 'brandUuid'),
            sellerUuid: self::resolveUuidField($input, $product, 'seller_uuid', 'sellerUuid'),
            attributeSetId: self::nullableInt($input['attribute_set_id'] ?? Arr::get($product, 'attributeSetId')),
            publishAt: $input['publish_at'] ?? self::nullableString(Arr::get($product, 'publishAt')),
            categoryIds: self::intList($input['category_ids'] ?? Arr::get($product, 'categoryIds', [])),
            collectionIds: self::intList($input['collection_ids'] ?? Arr::get($product, 'collectionIds', [])),
            tagIds: self::intList($input['tag_ids'] ?? []),
            mediaUuids: self::stringList($input['media_uuids'] ?? Arr::get($payload, 'media.productUuids', [])),
            attributeValues: is_array($input['attributes'] ?? null) ? $input['attributes'] : [],
            seo: new SeoData(
                metaTitle: $seoInput['meta_title'] ?? null,
                metaDescription: $seoInput['meta_description'] ?? null,
                metaKeywords: $seoInput['meta_keywords'] ?? null,
                canonicalUrl: $seoInput['canonical_url'] ?? null,
                ogImageMediaUuid: $seoInput['og_image_media_uuid'] ?? null,
            ),
            variantOptions: is_array($payload['options'] ?? null) ? $payload['options'] : [],
            variants: is_array($payload['variants'] ?? null) ? $payload['variants'] : [],
            skuPattern: self::nullableString($payload['skuPattern'] ?? null),
            meta: $meta,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            throw new DomainException('Product workspace payload is required.');
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new DomainException('Product workspace payload is invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param  list<mixed>  $values
     * @return list<int>
     */
    private static function intList(array $values): array
    {
        return array_values(array_map('intval', array_filter($values, static fn ($value) => $value !== null && $value !== '')));
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private static function stringList(array $values): array
    {
        return array_values(array_filter(array_map('strval', $values)));
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $productPayload
     */
    private static function resolveUuidField(array $input, array $productPayload, string $requestKey, string $payloadKey): ?string
    {
        if (array_key_exists($requestKey, $input)) {
            return self::nullableString($input[$requestKey]);
        }

        return self::nullableString(Arr::get($productPayload, $payloadKey));
    }
}
