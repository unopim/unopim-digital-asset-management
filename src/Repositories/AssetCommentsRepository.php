<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\AssetComments;

class AssetCommentsRepository extends Repository
{
    protected $assets = [];

    public function model(): string
    {
        return AssetComments::class;
    }

    /** Create comment. */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /** Update comment. */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }
}
