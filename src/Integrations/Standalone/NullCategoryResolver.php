<?php

declare(strict_types=1);

namespace Webkul\DAM\Integrations\Standalone;

use Webkul\DAM\Contracts\Adapters\CategoryResolver;

class NullCategoryResolver implements CategoryResolver
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function modelClass(): ?string
    {
        return null;
    }

    public function find(int|string $id): ?object
    {
        return null;
    }

    public function findAssetField(string $code): ?object
    {
        return null;
    }
}
