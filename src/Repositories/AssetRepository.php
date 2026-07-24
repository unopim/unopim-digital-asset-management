<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Asset;

class AssetRepository extends Repository
{
    protected $assets = [];

    public function model(): string
    {
        return Asset::class;
    }

    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }

    public function queryBuilder()
    {
        return $this;
    }
}
