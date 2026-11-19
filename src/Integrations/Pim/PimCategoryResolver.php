<?php

declare(strict_types=1);

namespace Webkul\DAM\Integrations\Pim;

use Webkul\Category\Models\CategoryProxy;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DAM\Contracts\Adapters\CategoryResolver;

class PimCategoryResolver implements CategoryResolver
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function modelClass(): ?string
    {
        return CategoryProxy::class;
    }

    public function find(int|string $id): ?object
    {
        return CategoryProxy::find($id);
    }

    public function findAssetField(string $code): ?object
    {
        return resolve(CategoryFieldRepository::class)->findOneWhere(['code' => $code]);
    }
}
