<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\AssetProperty;

class AssetPropertyRepository extends Repository
{
    protected $assets = [];

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AssetProperty::class;
    }

    /**
     * Create asset property.
     *
     * @return \Webkul\DAM\Contracts\AssetProperty
     */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /**
     * Update asset property.
     *
     * @param  int  $id
     * @param  string  $asset
     * @return \Webkul\DAM\Contracts\AssetProperty
     */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }
}
