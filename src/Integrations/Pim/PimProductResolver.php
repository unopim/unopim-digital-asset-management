<?php

namespace Webkul\DAM\Integrations\Pim;

use Webkul\DAM\Contracts\Adapters\ProductResolver;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Repositories\ProductRepository;

class PimProductResolver implements ProductResolver
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function modelClass(): ?string
    {
        return ProductProxy::class;
    }

    public function find(int|string $id): ?object
    {
        return resolve(ProductRepository::class)->find($id);
    }

    public function newQueryWithAssetFields(): mixed
    {
        return ProductProxy::with('attribute_family');
    }
}
