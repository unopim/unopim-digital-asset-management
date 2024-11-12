<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Contracts\Asset;
use Webkul\DAM\Models\AssetResourceMapping;

class AssetResourceMappingRepository extends Repository
{
    const CATEGORY_TYPE_MAPPING = 'category';

    const PRODUCT_TYPE_MAPPING = 'product';

    /**
     * Define model for the repository
     */
    public function model(): string
    {
        return AssetResourceMapping::class;
    }

    /**
     * Create asset mappings for either categories or products
     */
    public function createAssetMappings(
        array|Collection $assets,
        string|int $identifier,
        string $relatedField,
        bool $isCategory = true
    ): array {
        $createdMappings = [];

        $existingMappings = $this->where($isCategory ? ['category_id' => $identifier] : ['product_id' => $identifier])
            ->where('related_field', $relatedField)
            ->get()
            ->toArray();

        foreach ($assets as $assetId) {
            if ($assetId instanceof Asset) {
                $assetId = $assetId->id;
            }

            $existing = null;

            foreach ($existingMappings as $key => $mapping) {
                if ($mapping['dam_asset_id'] === $assetId) {
                    $existing = $mapping;

                    unset($existingMappings[$key]);

                    break;
                }
            }

            if ($existing) {
                continue;
            }

            $mapping = $this->create([
                'type'          => $isCategory ? self::CATEGORY_TYPE_MAPPING : self::PRODUCT_TYPE_MAPPING,
                'dam_asset_id'  => $assetId,
                'category_id'   => $isCategory ? $identifier : null,
                'product_id'    => $isCategory ? null : $identifier,
                'related_field' => $relatedField,
            ]);

            $createdMappings[] = $mapping;
        }

        $this->deleteMappings($existingMappings);

        return $createdMappings;
    }

    /**
     * Delete asset mappings based on the provided mappings
     */
    public function deleteMappings(array $mappingsToDelete): void
    {
        if (! empty($mappingsToDelete)) {
            $idsToDelete = Arr::pluck($mappingsToDelete, 'id');

            $this->whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Create asset mappings for a specific category
     */
    public function createCategoryAssetMappings(array|Collection $assets, string|int $categoryId, string $relatedField): array
    {
        return $this->createAssetMappings($assets, $categoryId, $relatedField, true);
    }

    /**
     * Create asset mappings for a specific product
     */
    public function createProductAssetMappings(array|Collection $assets, string|int $productId, string $relatedField): array
    {
        return $this->createAssetMappings($assets, $productId, $relatedField, false);
    }

    /**
     * Delete asset mappings for a specific category
     */
    public function deleteCategoryAssetMappings(int $categoryId, string $relatedField): void
    {
        $mappingsToDelete = $this->where('category_id', $categoryId)
            ->where('related_field', $relatedField)
            ->delete();
    }

    /**
     * Delete asset mappings for a specific product
     */
    public function deleteProductAssetMappings(int $productId, string $relatedField): void
    {
        $mappingsToDelete = $this->where('product_id', $productId)
            ->where('related_field', $relatedField)
            ->delete();
    }
}
