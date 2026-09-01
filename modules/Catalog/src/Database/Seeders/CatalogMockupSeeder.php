<?php

declare(strict_types=1);

namespace Commerce\Catalog\Database\Seeders;

use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Catalog\Support\CatalogMockupImageFactory;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Media\Models\Media;
use Illuminate\Database\Seeder;

final class CatalogMockupSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string}>
     */
    private const DEFAULT_CATEGORIES = [
        ['name' => 'เสื้อผ้า', 'slug' => 'clothing'],
        ['name' => 'รองเท้า', 'slug' => 'shoes'],
        ['name' => 'กระเป๋า', 'slug' => 'bags'],
        ['name' => 'เครื่องประดับ', 'slug' => 'accessories'],
    ];

    /**
     * @var list<array{name: string, slug: string}>
     */
    private const DEFAULT_BRANDS = [
        ['name' => 'Minimal Co.', 'slug' => 'minimal-co'],
        ['name' => 'Urban Line', 'slug' => 'urban-line'],
        ['name' => 'Craft Studio', 'slug' => 'craft-studio'],
        ['name' => 'North Peak', 'slug' => 'north-peak'],
    ];

    public function run(
        CategoryService $categoryService,
        BrandService $brandService,
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
    ): void {
        $this->seedDefaultsIfEmpty($categoryService, $brandService);
        $this->attachCategoryMockups($mediaUploadService, $mockupImageFactory);
        $this->attachBrandMockups($mediaUploadService, $mockupImageFactory);
    }

    private function seedDefaultsIfEmpty(CategoryService $categoryService, BrandService $brandService): void
    {
        if (Category::query()->count() === 0) {
            foreach (self::DEFAULT_CATEGORIES as $index => $item) {
                $categoryService->create(new CreateCategoryData(
                    name: $item['name'],
                    slug: $item['slug'],
                    isActive: true,
                    position: $index,
                ));
            }
        }

        if (Brand::query()->count() === 0) {
            foreach (self::DEFAULT_BRANDS as $item) {
                $brandService->create(new CreateBrandData(
                    name: $item['name'],
                    slug: $item['slug'],
                    isActive: true,
                ));
            }
        }
    }

    private function attachCategoryMockups(
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
    ): void {
        Category::query()
            ->whereNull('image_media_uuid')
            ->orderBy('position')
            ->orderBy('name')
            ->each(function (Category $category) use ($mediaUploadService, $mockupImageFactory): void {
                $file = $mockupImageFactory->makeCategoryImage($category->name);
                /** @var Media $media */
                $media = $mediaUploadService->upload($file);

                $category->update(['image_media_uuid' => $media->uuid]);
            });
    }

    private function attachBrandMockups(
        MediaUploadServiceInterface $mediaUploadService,
        CatalogMockupImageFactory $mockupImageFactory,
    ): void {
        Brand::query()
            ->whereNull('logo_media_uuid')
            ->orderBy('name')
            ->each(function (Brand $brand) use ($mediaUploadService, $mockupImageFactory): void {
                $file = $mockupImageFactory->makeBrandLogo($brand->name);
                /** @var Media $media */
                $media = $mediaUploadService->upload($file);

                $brand->update(['logo_media_uuid' => $media->uuid]);
            });
    }
}
