<?php

namespace Webkul\DAM\ApiDataSource\Catalog;

use Webkul\AdminApi\ApiDataSource\Catalog\SimpleProductDataSource as BaseSimpleProductDataSource;
use Webkul\DAM\Traits\ResolvesAssetPathsInValues;

class SimpleProductDataSource extends BaseSimpleProductDataSource
{
    use ResolvesAssetPathsInValues;

    /**
     * Rewrite asset ids in the response `values` to their paths.
     */
    protected function normalizeProduct(array $product, bool $withCompleteness = false, ?array $completenessByProduct = null, bool $withAssociations = false, ?array $mergedValues = null): array
    {
        $responseData = parent::normalizeProduct($product, $withCompleteness, $completenessByProduct, $withAssociations, $mergedValues);

        if (! empty($responseData['values']) && is_array($responseData['values'])) {
            $this->resolveAssetPathsInValues($responseData['values']);
        }

        return $responseData;
    }
}
