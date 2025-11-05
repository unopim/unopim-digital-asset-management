<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Asset;

class AssetRepository extends Repository
{
    protected $assets = [];

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Asset::class;
    }

    /**
     * Create asset.
     *
     * @return \Webkul\DAM\Contracts\Asset
     */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /**
     * Update Asset.
     *
     * @param  int  $id
     * @param  string  $asset
     * @return \Webkul\DAM\Contracts\Asset
     */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }

    /**
     * This function returns a query builder instance for the Asset model.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilder()
    {
        return $this;
    }
}
