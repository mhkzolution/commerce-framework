<?php

declare(strict_types=1);

namespace Commerce\Product\Database\Seeders;

use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\Database\Seeders\CatalogMockupSeeder;
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Support\CatalogMockupImageFactory;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Media\Models\Media;
use Commerce\Product\DTO\SaveProductWorkspaceData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductWorkspaceSaveService;
use Illuminate\Database\Seeder;

/**
 * สร้างสินค้าตัวอย่าง 2 รายการผ่าน Product Workspace:
 * 1) สินค้าธรรมดา (simple) — variant เดียว
 * 2) สินค้าแบบ Variants (variable) — หลาย variant จาก Color × Size
 *
 * รันซ้ำได้: ข้ามถ้ามี slug อยู่แล้ว
 */
final class ProductMockupSeeder extends Seeder
{
    private const SIMPLE_SLUG = 'mockup-organic-tee';

    private const VARIABLE_SLUG = 'mockup-minimal-hoodie';

    public function run(
        ProductWorkspaceSaveService $workspaceSaveService,
        InventoryServiceInterface $inventoryService,
        AttributeServiceInterface $attributeService,
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
    ): void {
        $this->call(CatalogMockupSeeder::class);

        $clothingCategory = $this->resolveClothingCategory();
        $minimalBrand = Brand::query()->where('slug', 'minimal-co')->first();
        $urbanBrand = Brand::query()->where('slug', 'urban-line')->first();

        if ($clothingCategory === null || $minimalBrand === null || $urbanBrand === null) {
            return;
        }

        $genderAttribute = $this->ensureAttribute(
            $attributeService,
            code: 'gender',
            name: 'เพศ',
            options: ['Men', 'Women', 'Unisex'],
        );

        $this->seedSimpleProduct(
            $workspaceSaveService,
            $inventoryService,
            $mediaUploadService,
            $mockupImageFactory,
            $clothingCategory->id,
            $minimalBrand->uuid,
            $genderAttribute->id,
        );

        $this->seedVariableProduct(
            $workspaceSaveService,
            $inventoryService,
            $mediaUploadService,
            $mockupImageFactory,
            $clothingCategory->id,
            $urbanBrand->uuid,
        );
    }

    private function seedSimpleProduct(
        ProductWorkspaceSaveService $workspaceSaveService,
        InventoryServiceInterface $inventoryService,
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
        int $categoryId,
        string $brandUuid,
        int $genderAttributeId,
    ): void {
        if (Product::query()->where('slug', self::SIMPLE_SLUG)->exists()) {
            return;
        }

        /** @var Media $media */
        $media = $mediaUploadService->upload(
            $mockupImageFactory->makeProductImage('เสื้อยืดคอกลม Organic Cotton'),
        );

        $product = $workspaceSaveService->create(new SaveProductWorkspaceData(
            name: 'เสื้อยืดคอกลม Organic Cotton',
            slug: self::SIMPLE_SLUG,
            description: <<<'HTML'
<p><strong>ตัวอย่างสินค้าแบบธรรมดา (Simple)</strong> — มี variant เดียว ใช้เป็นข้อมูลอ้างอิงเมื่อเพิ่มสินค้าใหม่</p>
<ul>
<li>กรอกข้อมูลหลัก: ชื่อ, รายละเอียด, แบรนด์, หมวดหมู่</li>
<li>ตั้งราคา / SKU / บาร์โค้ด / ต้นทุน / น้ำหนัก ที่ variant เดียว</li>
<li>ใส่ attribute ระดับสินค้า (เช่น เพศ) ได้โดยตรง</li>
<li>อัปโหลดรูปและรับสต็อกเข้าคลัง</li>
</ul>
HTML,
            status: 'published',
            visibility: 'public',
            brandUuid: $brandUuid,
            categoryIds: [$categoryId],
            mediaUuids: [$media->uuid],
            attributeValues: [
                $genderAttributeId => 'Unisex',
            ],
            seo: new SeoData(
                metaTitle: 'เสื้อยืดคอกลม Organic Cotton | ตัวอย่างสินค้า',
                metaDescription: 'ตัวอย่างสินค้าแบบธรรมดา (simple) สำหรับอ้างอิงการเพิ่มสินค้าใน Product Workspace',
            ),
            variants: [
                [
                    'name' => 'เสื้อยืดคอกลม Organic Cotton',
                    'sku' => 'MOCK-TEE-001',
                    'price' => '590',
                    'comparePrice' => '790',
                    'cost' => '280',
                    'weight' => '220',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
            meta: [
                'notes' => 'Mockup: simple product — อ้างอิงการเพิ่มสินค้าธรรมดา',
                'external_id' => 'MOCK-SIMPLE-001',
                'rating' => 4.4,
                'review_count' => 96,
                'sold_count' => 715,
                'delivery' => [
                    'summary' => 'จัดส่งพรุ่งนี้ · ส่งฟรีเมื่อซื้อครบตามเงื่อนไข',
                ],
                'specifications' => [
                    ['label' => 'วัสดุ', 'value' => 'Organic Cotton 100%'],
                    ['label' => 'ทรง', 'value' => 'คอกลม สวมสบาย'],
                    ['label' => 'น้ำหนัก', 'value' => '220 กรัม'],
                    ['label' => 'เพศ', 'value' => 'Unisex'],
                ],
            ],
        ));

        $variant = $product->defaultVariant();

        if ($variant !== null) {
            $inventoryService->receive($variant->uuid, 50, 'Product mockup seeder');
        }
    }

    private function seedVariableProduct(
        ProductWorkspaceSaveService $workspaceSaveService,
        InventoryServiceInterface $inventoryService,
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
        int $categoryId,
        string $brandUuid,
    ): void {
        if (Product::query()->where('slug', self::VARIABLE_SLUG)->exists()) {
            return;
        }

        /** @var Media $media */
        $media = $mediaUploadService->upload(
            $mockupImageFactory->makeProductImage('ฮู้ดี้ Minimal Oversize'),
        );

        $product = $workspaceSaveService->create(new SaveProductWorkspaceData(
            name: 'ฮู้ดี้ Minimal Oversize',
            slug: self::VARIABLE_SLUG,
            description: <<<'HTML'
<p><strong>ตัวอย่างสินค้าแบบ Variants (Variable)</strong> — มีหลาย variant จากตัวเลือก Color × Size</p>
<ul>
<li>กำหนดตัวเลือก (Options) ก่อน เช่น สี และ ขนาด</li>
<li>สร้าง variant แต่ละชุด พร้อม SKU / ราคา / สต็อกแยกกัน</li>
<li>ระบบจะสร้าง attribute ของตัวเลือกให้อัตโนมัติและผูกกับแต่ละ variant</li>
<li>ลูกค้าเลือกสี/ไซส์บนหน้าร้านก่อนเพิ่มลงตะกร้า</li>
</ul>
HTML,
            status: 'published',
            visibility: 'public',
            brandUuid: $brandUuid,
            categoryIds: [$categoryId],
            mediaUuids: [$media->uuid],
            seo: new SeoData(
                metaTitle: 'ฮู้ดี้ Minimal Oversize | ตัวอย่างสินค้า Variants',
                metaDescription: 'ตัวอย่างสินค้าแบบ variable พร้อมตัวเลือกสีและขนาด สำหรับอ้างอิงใน Product Workspace',
            ),
            variantOptions: [
                ['id' => 'opt_color', 'name' => 'Color', 'values' => ['Black', 'Navy']],
                ['id' => 'opt_size', 'name' => 'Size', 'values' => ['S', 'M', 'L']],
            ],
            variants: [
                [
                    'name' => 'Black / S',
                    'sku' => 'MOCK-HOOD-BLK-S',
                    'price' => '1290',
                    'comparePrice' => '1590',
                    'cost' => '620',
                    'weight' => '480',
                    'status' => 'active',
                    'options' => ['color' => 'Black', 'size' => 'S'],
                    'isDefault' => true,
                ],
                [
                    'name' => 'Black / M',
                    'sku' => 'MOCK-HOOD-BLK-M',
                    'price' => '1290',
                    'cost' => '620',
                    'weight' => '500',
                    'status' => 'active',
                    'options' => ['color' => 'Black', 'size' => 'M'],
                ],
                [
                    'name' => 'Black / L',
                    'sku' => 'MOCK-HOOD-BLK-L',
                    'price' => '1340',
                    'cost' => '640',
                    'weight' => '520',
                    'status' => 'active',
                    'options' => ['color' => 'Black', 'size' => 'L'],
                ],
                [
                    'name' => 'Navy / S',
                    'sku' => 'MOCK-HOOD-NVY-S',
                    'price' => '1290',
                    'cost' => '620',
                    'weight' => '480',
                    'status' => 'active',
                    'options' => ['color' => 'Navy', 'size' => 'S'],
                ],
                [
                    'name' => 'Navy / M',
                    'sku' => 'MOCK-HOOD-NVY-M',
                    'price' => '1290',
                    'cost' => '620',
                    'weight' => '500',
                    'status' => 'active',
                    'options' => ['color' => 'Navy', 'size' => 'M'],
                ],
                [
                    'name' => 'Navy / L',
                    'sku' => 'MOCK-HOOD-NVY-L',
                    'price' => '1340',
                    'cost' => '640',
                    'weight' => '520',
                    'status' => 'active',
                    'options' => ['color' => 'Navy', 'size' => 'L'],
                ],
            ],
            skuPattern: 'MOCK-HOOD-{COLOR}-{SIZE}',
            meta: [
                'notes' => 'Mockup: variable product — อ้างอิงการเพิ่มสินค้าแบบ Variants',
                'external_id' => 'MOCK-VARIABLE-001',
            ],
        ));

        $stockBySku = [
            'MOCK-HOOD-BLK-S' => 12,
            'MOCK-HOOD-BLK-M' => 20,
            'MOCK-HOOD-BLK-L' => 8,
            'MOCK-HOOD-NVY-S' => 10,
            'MOCK-HOOD-NVY-M' => 18,
            'MOCK-HOOD-NVY-L' => 6,
        ];

        foreach ($product->variants as $variant) {
            $quantity = $stockBySku[$variant->sku] ?? 5;
            $inventoryService->receive($variant->uuid, $quantity, 'Product mockup seeder');
        }
    }

    /**
     * @param  list<string>  $options
     */
    private function ensureAttribute(
        AttributeServiceInterface $attributeService,
        string $code,
        string $name,
        array $options,
    ): Attribute {
        $existing = Attribute::query()->where('code', $code)->first();

        if ($existing !== null) {
            return $existing;
        }

        return $attributeService->create(new CreateAttributeData(
            code: $code,
            name: $name,
            type: 'select',
            isFilterable: true,
            options: $options,
        ));
    }

    private function resolveClothingCategory(): ?Category
    {
        return Category::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('slug', 'clothing')
                    ->orWhere('name', 'like', '%เสื้อผ้า%');
            })
            ->orderByRaw("CASE WHEN slug = 'clothing' THEN 0 ELSE 1 END")
            ->orderBy('position')
            ->orderBy('name')
            ->first();
    }
}
