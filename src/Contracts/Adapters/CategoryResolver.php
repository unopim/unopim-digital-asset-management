<?php

declare(strict_types=1);

namespace Webkul\DAM\Contracts\Adapters;

interface CategoryResolver extends PimIntegration
{
    public function modelClass(): ?string;

    public function find(int|string $id): ?object;

    public function findAssetField(string $code): ?object;
}
