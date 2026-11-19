<?php

namespace Webkul\DAM\Contracts\Adapters;

use Illuminate\Database\Eloquent\Builder;

interface ProductResolver extends PimIntegration
{
    public function modelClass(): ?string;

    public function find(int|string $id): ?object;

    /**
     * @return Builder|null
     */
    public function newQueryWithAssetFields(): mixed;
}
