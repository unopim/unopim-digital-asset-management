<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\AssetComments;

class AssetCommentsRepository extends Repository
{
    protected $assets = [];

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AssetComments::class;
    }

    /**
     * Create comment
     *
     * @return \Webkul\DAM\Contracts\AssetComments
     */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /**
     * Update comment.
     *
     * @param  int  $id
     * @param  string  $asset
     * @return \Webkul\DAM\Contracts\AssetComments
     */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }
}
