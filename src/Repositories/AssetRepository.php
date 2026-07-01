<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Asset;

class AssetRepository extends Repository
{
    protected $assets = [];

    public function model(): string
    {
        return Asset::class;
    }

    /** Create asset. */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /** Update Asset. */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }

    /** Returns a query builder instance for the Asset model. */
    public function queryBuilder()
    {
        return $this;
    }
}
