<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\Services\BarcodeLabelExpansionService;
use Commerce\Barcode\Services\BarcodeOwnerResolver;
use Commerce\Barcode\Services\BarcodeQueueItemNormalizer;
use Commerce\Barcode\Services\ExpandedLabelMapper;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Core\Barcode\BarcodeValueGenerator;
use Commerce\Core\Barcode\Strategies\PrefixBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\RandomBarcodeStrategy;
use Commerce\Core\Barcode\Strategies\TimestampBarcodeStrategy;
use PHPUnit\Framework\TestCase;

final class BarcodeQueueArchitectureTest extends TestCase
{
    private function normalizer(string $siteName = 'Acme'): BarcodeQueueItemNormalizer
    {
        $siteIdentity = $this->createMock(SiteIdentityServiceInterface::class);
        $siteIdentity->method('name')->willReturn($siteName);

        return new BarcodeQueueItemNormalizer(new BarcodeOwnerResolver($siteIdentity));
    }

    public function test_legacy_payload_normalizes_to_canonical_queue_item(): void
    {
        $item = $this->normalizer()->normalize([
            'variant_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'sku' => 'SKU-100',
            'product_name' => 'Widget',
            'owner_name' => 'Acme',
            'quantity' => 2,
        ]);

        $this->assertSame('PRODUCT', $item->source->value);
        $this->assertSame('SKU-100', $item->barcode);
        $this->assertSame('SKU-100', $item->displayText);
    }

    public function test_manual_legacy_payload_uses_variant_name_as_display_text(): void
    {
        $item = $this->normalizer()->normalize([
            'sku' => 'MANUAL-001',
            'product_name' => 'Manual Label',
            'variant_name' => 'SHOW-ME',
            'owner_name' => 'Acme',
            'quantity' => 1,
        ]);

        $this->assertSame('MANUAL', $item->source->value);
        $this->assertSame('SHOW-ME', $item->displayText);
    }

    public function test_expanded_labels_do_not_branch_on_source(): void
    {
        $service = new BarcodeLabelExpansionService(
            $this->normalizer(),
            new ExpandedLabelMapper,
        );

        $labels = $service->expand([
            [
                'source' => 'MANUAL',
                'title' => 'Manual',
                'barcode' => 'BC-1',
                'display_text' => 'Display',
                'owner_name' => 'Acme',
                'quantity' => 1,
            ],
            [
                'variant_uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'sku' => 'BC-2',
                'product_name' => 'Product',
                'owner_name' => 'Acme',
                'quantity' => 1,
            ],
        ]);

        $this->assertCount(2, $labels);
        $this->assertSame('Display', $labels[0]['display_text']);
        $this->assertSame('BC-2', $labels[1]['display_text']);
    }

    public function test_barcode_value_generator_supports_multiple_strategies(): void
    {
        $generator = new BarcodeValueGenerator([
            new RandomBarcodeStrategy,
            new TimestampBarcodeStrategy,
            new PrefixBarcodeStrategy,
        ]);

        $random = $generator->generate('random', ['length' => 8]);
        $this->assertSame(8, strlen($random));
        $this->assertStringStartsWith('BC-', $generator->generate('prefix', ['prefix' => 'BC-', 'length' => 4]));
        $this->assertContains('random', $generator->strategies());
    }
}
